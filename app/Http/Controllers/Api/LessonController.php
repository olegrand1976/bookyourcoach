<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\User;
use App\Models\Teacher;
use App\Models\SubscriptionInstance;
use App\Models\SubscriptionRecurringSlot;
use App\Models\ClubOpenSlot;
use App\Notifications\LessonBookedNotification;
use App\Notifications\LessonCancelledNotification;
use App\Jobs\SendLessonReminderJob;
use App\Jobs\ProcessLessonPostCreationJob;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

/**
 * @OA\Tag(
 *     name="Lessons",
 *     description="Gestion des cours et réservations"
 * )
 */
class LessonController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/lessons",
     *     summary="Liste des cours",
     *     description="Récupère la liste des cours selon le rôle de l'utilisateur",
     *     operationId="getLessons",
     *     tags={"Lessons"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filtrer par statut",
     *         required=false,
     *         @OA\Schema(type="string", enum={"pending", "confirmed", "completed", "cancelled"})
     *     ),
     *     @OA\Parameter(
     *         name="date_from",
     *         in="query",
     *         description="Date de début de période",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="date_to",
     *         in="query",
     *         description="Date de fin de période",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Nombre maximum de cours à retourner (défaut: 50)",
     *         required=false,
     *         @OA\Schema(type="integer", default=50)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Liste des cours récupérée avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Lesson")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non autorisé"
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            // Optimiser les relations chargées - sélectionner uniquement les colonnes nécessaires
            // Désactiver les accessors coûteux pour améliorer les performances
            
            // Vérifier si la colonne color existe dans la table teachers
            $hasColorColumn = \Illuminate\Support\Facades\Schema::hasColumn('teachers', 'color');
            $teacherColumns = $hasColorColumn ? 'id,user_id,color' : 'id,user_id';
            
            $query = Lesson::select('lessons.id', 'lessons.teacher_id', 'lessons.student_id', 'lessons.course_type_id', 
                                   'lessons.location_id', 'lessons.club_id', 'lessons.start_time', 'lessons.end_time', 
                                   'lessons.status', 'lessons.price', 'lessons.notes', 'lessons.created_at', 'lessons.updated_at',
                                   'lessons.est_legacy', 'lessons.deduct_from_subscription')
                ->with([
                    "teacher:{$teacherColumns}",
                    'teacher.user:id,name,email',
                    'student:id,user_id,first_name,last_name',
                    'student.user:id,name,email',
                    'student.subscriptionInstances' => function ($query) {
                        $query->where('status', 'active')
                              ->where('expires_at', '>=', now())
                              ->with(['subscription.template']);
                    },
                    'students:id,user_id,first_name,last_name',
                    'students.user:id,name,email',
                    'courseType:id,name',
                    'location:id,name',
                    'club:id,name,email,phone',
                    'subscriptionInstances' => function ($query) {
                        $query->with(['subscription.template']);
                    }
                ]);

            // Filtrage selon le rôle de l'utilisateur
            if ($user->role === 'teacher') {
                $teacher = $user->teacher;
                if ($teacher) {
                    $query->where('teacher_id', $teacher->id);
                } else {
                    // Si pas de profil enseignant, ne retourner aucun cours
                    $query->whereRaw('1 = 0');
                }
            } elseif ($user->role === 'student') {
                $query->whereHas('student', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
            } elseif ($user->role === 'club') {
                // Les clubs voient uniquement les cours de leurs enseignants
                $club = $user->getFirstClub();
                if ($club) {
                    $query->whereHas('teacher', function ($q) use ($club) {
                        $q->whereHas('clubs', function ($clubQuery) use ($club) {
                            $clubQuery->where('clubs.id', $club->id);
                        });
                    });
                } else {
                    // Si le club n'existe pas, ne retourner aucun cours
                    $query->whereRaw('1 = 0');
                }
            }
            // Les admins voient tous les cours

            // Planning club/enseignant : exclure les cours annulés pour libérer la plage (réutilisable par un autre élève)
            // L'historique élève inclut les annulés (StudentController::history, getLessonHistory)
            if (in_array($user->role, ['club', 'teacher'], true)) {
                $query->where('status', '!=', 'cancelled');
            }

            // Filtres optionnels
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Gérer le filtre de période si fourni
            if ($request->has('period')) {
                $period = $request->get('period');
                $now = now();
                $dateFrom = null;
                $dateTo = null;
                
                switch ($period) {
                    case '7days':
                        $dateFrom = $now->copy()->startOfDay();
                        $dateTo = $now->copy()->addDays(7)->endOfDay();
                        break;
                    case '15days':
                        $dateFrom = $now->copy()->startOfDay();
                        $dateTo = $now->copy()->addDays(15)->endOfDay();
                        break;
                    case 'previous_month':
                        $dateFrom = $now->copy()->subMonth()->startOfMonth()->startOfDay();
                        $dateTo = $now->copy()->subMonth()->endOfMonth()->endOfDay();
                        break;
                    case 'current_month':
                        $dateFrom = $now->copy()->startOfMonth()->startOfDay();
                        $dateTo = $now->copy()->endOfMonth()->endOfDay();
                        break;
                    case 'next_month':
                        $dateFrom = $now->copy()->addMonth()->startOfMonth()->startOfDay();
                        $dateTo = $now->copy()->addMonth()->endOfMonth()->endOfDay();
                        break;
                }
                
                if ($dateFrom && $dateTo) {
                    $query->whereBetween('start_time', [$dateFrom, $dateTo]);
                }
            } elseif ($request->has('date_from') || $request->has('date_to')) {
                // Si date_from ou date_to sont fournis, les utiliser (pas de filtre par défaut)
                if ($request->has('date_from')) {
                    $query->whereDate('start_time', '>=', $request->date_from);
                }

                if ($request->has('date_to')) {
                    $query->whereDate('start_time', '<=', $request->date_to);
                }
            } else {
                // Par défaut: filtrer sur les 7 prochains jours si aucune période spécifiée
                $now = now();
                $query->whereBetween('start_time', [
                    $now->copy()->startOfDay(),
                    $now->copy()->addDays(7)->endOfDay()
                ]);
            }

            // Limiter le nombre de résultats pour éviter les chargements trop longs
            // Si date_from ET date_to sont fournis, ne pas limiter (on filtre par période)
            // Sinon, appliquer une limite par défaut
            $hasDateRange = $request->has('date_from') && $request->has('date_to');
            $offset = max($request->get('offset', 0), 0); // Support de la pagination
            
            // Compter le total avant pagination (pour l'historique complet)
            $total = $query->count();
            
            // ✅ Tri DESC (du plus récent au plus ancien) pour l'historique, ASC pour les cours à venir
            $orderDirection = $request->get('order', 'asc'); // 'asc' par défaut, 'desc' pour l'historique
            $lessonsQuery = $query->orderBy('start_time', $orderDirection);
            
            // Appliquer offset et limit seulement si nécessaire
            if ($hasDateRange) {
                // Pas de limite si on a une plage de dates complète (filtrage par période uniquement)
                // Si offset est demandé, on l'applique quand même (pour la pagination)
                if ($offset > 0) {
                    // Pour éviter les problèmes avec offset sans limit, on applique une limite très élevée
                    $lessonsQuery->offset($offset)->limit(10000);
                }
                // Sinon, pas de limite ni d'offset, on récupère tout
            } else {
                // Limite par défaut si pas de plage de dates
                $limit = min($request->get('limit', 50), 500); // Par défaut 50 cours max, max 500 pour l'historique
                $lessonsQuery->offset($offset)->limit($limit);
            }
            
            $lessons = $lessonsQuery->get()
                ->makeHidden(['teacher_name', 'student_name', 'duration', 'title']); // Désactiver les accessors coûteux

            $response = [
                'success' => true,
                'data' => $lessons
            ];
            
            // Ajouter les informations de pagination si offset est utilisé
            if ($request->has('offset') || $request->has('limit')) {
                $response['pagination'] = [
                    'total' => $total,
                    'per_page' => $limit,
                    'current_page' => floor($offset / $limit) + 1,
                    'last_page' => ceil($total / $limit),
                    'from' => $offset + 1,
                    'to' => min($offset + $limit, $total)
                ];
            }
            
            return response()->json($response);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des cours',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/lessons",
     *     summary="Créer un nouveau cours",
     *     description="Crée une nouvelle réservation de cours",
     *     operationId="createLesson",
     *     tags={"Lessons"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"teacher_id", "course_type_id", "scheduled_at"},
     *             @OA\Property(property="teacher_id", type="integer", example=1),
     *             @OA\Property(property="course_type_id", type="integer", example=1),
     *             @OA\Property(property="location_id", type="integer", example=1),
     *             @OA\Property(property="scheduled_at", type="string", format="datetime", example="2025-08-15 14:00:00"),
     *             @OA\Property(property="duration", type="integer", example=60),
     *             @OA\Property(property="price", type="number", format="float", example=50.00),
     *             @OA\Property(property="notes", type="string", example="Premier cours de dressage")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Cours créé avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/Lesson"),
     *             @OA\Property(property="message", type="string", example="Cours créé avec succès")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation"
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        try {
            Log::info('📥 [LessonController::store] Requête reçue', [
                'student_id' => $request->input('student_id'),
                'recurring_interval' => $request->input('recurring_interval'),
                'deduct_from_subscription' => $request->input('deduct_from_subscription'),
            ]);
            $user = Auth::user();

            // Validation de base - permettre les dates dans le passé pour l'encodage rétroactif
            $validated = $request->validate([
                'teacher_id' => 'required|exists:teachers,id',
                'course_type_id' => 'required|exists:course_types,id',
                'location_id' => 'nullable|exists:locations,id',
                'start_time' => 'required|date', // Permettre toutes les dates (passé et futur)
                'duration' => 'nullable|integer|min:15|max:180',
                'price' => 'nullable|numeric|min:0',
                'notes' => 'nullable|string|max:1000',
                // Champs pour le calcul des commissions
                'est_legacy' => 'nullable|boolean',      // false = DCL (Déclaré), true = NDCL (Non Déclaré)
                'date_paiement' => 'nullable|date',      // Date de paiement (détermine le mois de commission)
                'montant' => 'nullable|numeric|min:0',   // Montant réellement payé (peut différer de price)
                // Déduction d'abonnement (par défaut true)
                'deduct_from_subscription' => 'nullable|boolean',
                // Intervalle de récurrence (0 = pas de récurrence, 1 = chaque semaine, 2 = toutes les 2 semaines, etc.)
                'recurring_interval' => 'nullable|integer|min:0|max:52',
            ]);

            // 🔒 Validation : vérifier que la durée correspond au type de cours sélectionné
            $courseType = \App\Models\CourseType::find($validated['course_type_id']);
            if ($courseType && $courseType->duration_minutes) {
                // Si une durée est fournie, elle doit correspondre à celle du type de cours
                if (isset($validated['duration']) && $validated['duration'] != $courseType->duration_minutes) {
                    return response()->json([
                        'success' => false,
                        'message' => "La durée du cours ({$validated['duration']} min) ne correspond pas à celle du type de cours sélectionné ({$courseType->duration_minutes} min). Veuillez sélectionner le bon type de cours.",
                        'errors' => [
                            'duration' => ["La durée doit être de {$courseType->duration_minutes} minutes pour le type de cours '{$courseType->name}'"]
                        ]
                    ], 422);
                }
                // Si aucune durée n'est fournie, utiliser celle du type de cours
                if (!isset($validated['duration'])) {
                    $validated['duration'] = $courseType->duration_minutes;
                }
            }

            // Vérifications spécifiques selon le rôle
            if ($user->role === 'club') {
                // Pour les clubs, vérifier que le teacher appartient au club
                $club = $user->getFirstClub();
                if ($club) {
                    $teacher = Teacher::find($validated['teacher_id']);
                    if (!$teacher || !$teacher->clubs()->where('clubs.id', $club->id)->exists()) {
                        return response()->json([
                            'success' => false,
                            'message' => 'L\'enseignant sélectionné n\'appartient pas à votre club'
                        ], 422);
                    }
                    
                    // 🔧 CORRECTION : Ajouter automatiquement le club_id
                    $validated['club_id'] = $club->id;
                }
                // Pour les clubs, student_id peut être fourni
                $validated = array_merge($validated, $request->validate([
                    'student_id' => 'nullable|exists:students,id'
                ]));
            } elseif ($user->role === 'student') {
                // Pour les étudiants, on assigne automatiquement leur student_id
                $student = $user->student;
                if (!$student) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Profil étudiant non trouvé'
                    ], 400);
                }
                $validated['student_id'] = $student->id;
            } else {
                // Pour les admins et enseignants, student_id peut être fourni
                $validated = array_merge($validated, $request->validate([
                    'student_id' => 'nullable|exists:students,id'
                ]));
                
                // 🔧 CORRECTION : Pour les enseignants, déduire le club_id depuis le premier club du teacher
                if ($user->role === 'teacher') {
                    $teacher = Teacher::find($validated['teacher_id']);
                    if ($teacher) {
                        $firstClub = $teacher->clubs()->first();
                        if ($firstClub) {
                            $validated['club_id'] = $firstClub->id;
                        }
                    }
                }
            }

            // ✅ Définir le statut selon le rôle
            // Les clubs confirment automatiquement leurs cours (ils gèrent le planning)
            // Les élèves créent des cours "en attente" (nécessitent validation)
            if ($user->role === 'club' || $user->role === 'teacher') {
                $validated['status'] = 'confirmed';
            } else {
                $validated['status'] = 'pending';
            }

            // Calculer end_time si duration est fourni
            if (isset($validated['duration'])) {
                $startTime = \Carbon\Carbon::parse($validated['start_time']);
                $validated['end_time'] = $startTime->copy()->addMinutes($validated['duration'])->format('Y-m-d H:i:s');
            } else {
                // Durée par défaut de 60 minutes si non fournie
                $startTime = \Carbon\Carbon::parse($validated['start_time']);
                $validated['end_time'] = $startTime->copy()->addMinutes(60)->format('Y-m-d H:i:s');
                $validated['duration'] = 60;
            }

            // Vérifier qu'un élève n'est pas déjà inscrit à la même heure
            if (isset($validated['student_id']) && $validated['student_id']) {
                $this->checkStudentTimeConflict($validated['student_id'], $validated['start_time']);
            }

            // Vérifier la capacité du créneau si c'est pour un club
            if ($user->role === 'club') {
                $club = $user->getFirstClub();
                if ($club) {
                    // Calculer le nombre d'élèves du nouveau cours (au moins 1 si student_id est défini)
                    $newLessonStudentCount = 0;
                    if (isset($validated['student_id']) && $validated['student_id']) {
                        $newLessonStudentCount = 1;
                    }
                    // TODO: Si support de student_ids (array) dans le futur, ajouter le count ici
                    
                    $this->checkSlotCapacity($validated['start_time'], $club->id, $validated['teacher_id'], $newLessonStudentCount, $validated);
                }
            }

            // 💰 CORRECTION : Utiliser automatiquement le prix du CourseType si aucun prix n'est fourni
            if (!isset($validated['price']) || $validated['price'] === null || $validated['price'] == 0) {
                $courseType = \App\Models\CourseType::find($validated['course_type_id']);
                if ($courseType && $courseType->price) {
                    $validated['price'] = $courseType->price;
                    Log::info("💰 Prix automatique depuis CourseType", [
                        'course_type_id' => $courseType->id,
                        'course_type_name' => $courseType->name,
                        'price' => $courseType->price
                    ]);
                } else {
                    // Si le CourseType n'a pas de prix, essayer de récupérer depuis les discipline_settings du club
                    if ($user->role === 'club') {
                        $club = $user->getFirstClub();
                        if ($club && $courseType && $courseType->discipline_id) {
                            $disciplineSettings = $club->discipline_settings ?? [];
                            if (is_string($disciplineSettings)) {
                                $disciplineSettings = json_decode($disciplineSettings, true) ?? [];
                            }
                            
                            if (isset($disciplineSettings[$courseType->discipline_id]['price'])) {
                                $validated['price'] = $disciplineSettings[$courseType->discipline_id]['price'];
                                Log::info("💰 Prix automatique depuis discipline_settings du club", [
                                    'club_id' => $club->id,
                                    'discipline_id' => $courseType->discipline_id,
                                    'price' => $validated['price']
                                ]);
                            }
                        }
                    }
                }
            }

            // 🔧 CORRECTION : Fournir une location_id par défaut si elle n'est pas fournie
            if (!isset($validated['location_id']) || empty($validated['location_id'])) {
                // Chercher une location par défaut (première disponible)
                $defaultLocation = \App\Models\Location::first();
                if ($defaultLocation) {
                    $validated['location_id'] = $defaultLocation->id;
                } else {
                    // Si aucune location n'existe, créer une location par défaut
                    $defaultLocation = \App\Models\Location::create([
                        'name' => 'Location par défaut',
                        'address' => 'Non spécifiée',
                        'city' => 'Non spécifiée',
                        'postal_code' => '00000',
                        'country' => 'Belgium',
                    ]);
                    $validated['location_id'] = $defaultLocation->id;
                }
            }

            // 🔒 Règle : déduction abonnement ou récurrence → exiger un abonnement actif (sinon bloquer la création)
            $deductFromSubscription = filter_var($request->input('deduct_from_subscription', true), FILTER_VALIDATE_BOOLEAN);
            $recurringInterval = max(0, min(52, (int) $request->input('recurring_interval', 0))); // 0 = pas de récurrence
            // Intervalle effectif pour la récurrence : si déduction + élève mais request envoie 0, on considère 1 (hebdo)
            $recurringIntervalForRecurrence = $recurringInterval >= 1
                ? $recurringInterval
                : ($deductFromSubscription && !empty($validated['student_id']) ? 1 : 0);
            $recurringIntervalForRecurrence = max(0, min(52, $recurringIntervalForRecurrence));

            if (!empty($validated['student_id']) && ($deductFromSubscription || $recurringIntervalForRecurrence >= 1)) {
                $clubId = $validated['club_id'] ?? null;
                $activeSubscription = SubscriptionInstance::findActiveSubscriptionForLesson(
                    (int) $validated['student_id'],
                    (int) $validated['course_type_id'],
                    $clubId
                );
                if (!$activeSubscription) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Aucun abonnement actif pour cet élève et ce type de cours. La création du cours est impossible.',
                        'errors' => [
                            'student_id' => ['Aucun abonnement actif trouvé pour cet élève et ce type de cours. Veuillez souscrire un abonnement ou créer le cours sans récurrence ni déduction.'],
                        ],
                    ], 422);
                }
            }

            // 🔒 Récurrence : valider les 26 semaines avant de créer le premier cours (selon l'intervalle effectif)
            if (!empty($validated['student_id']) && $recurringIntervalForRecurrence >= 1 && !empty($validated['teacher_id']) && !empty($validated['start_time'])) {
                $startCarbon = \Carbon\Carbon::parse($validated['start_time']);
                $dayOfWeek = $startCarbon->dayOfWeek;
                $startTimeStr = $startCarbon->format('H:i:s');
                $endCarbon = isset($validated['end_time']) ? \Carbon\Carbon::parse($validated['end_time']) : $startCarbon->copy()->addMinutes($validated['duration'] ?? 60);
                $endTimeStr = $endCarbon->format('H:i:s');
                $startDateStr = $startCarbon->format('Y-m-d');

                $recurringValidator = new \App\Services\RecurringSlotValidator();
                $recurringValidation = $recurringValidator->validateRecurringAvailabilityWithoutOpenSlot(
                    (int) $validated['teacher_id'],
                    (int) $validated['student_id'],
                    $startDateStr,
                    $dayOfWeek,
                    $startTimeStr,
                    $endTimeStr,
                    $recurringIntervalForRecurrence
                );

                if (!$recurringValidation['valid']) {
                    return response()->json([
                        'success' => false,
                        'message' => $recurringValidation['message'],
                        'errors' => [
                            'recurring' => array_map(function (array $c) {
                                return ($c['date'] ?? '') . ' : ' . ($c['message'] ?? '');
                            }, $recurringValidation['conflicts']),
                        ],
                        'conflicts' => $recurringValidation['conflicts'],
                    ], 422);
                }
            }

            $lesson = Lesson::create($validated);

            // Récurrence : création synchrone du créneau + génération des cours pour affichage immédiat au calendrier
            if (!empty($validated['student_id']) && $recurringIntervalForRecurrence >= 1) {
                Log::info("[LessonController] Création récurrence synchrone", [
                    'lesson_id' => $lesson->id,
                    'recurring_interval' => $recurringIntervalForRecurrence,
                ]);
                $recurrenceService = new \App\Services\RecurrenceCreationService();
                $recurrenceService->createRecurrenceAndGenerateLessons($lesson, $recurringIntervalForRecurrence);
            }

            // Job async : déduction abonnement, notifications, rappel (récurrence déjà créée en sync si demandée)
            $shouldDispatchJob = !empty($validated['student_id']) && ($deductFromSubscription || $recurringIntervalForRecurrence >= 1);
            Log::info("⚡ [LessonController] Dispatch job?", [
                'lesson_id' => $lesson->id,
                'student_id' => $validated['student_id'] ?? null,
                'deduct_from_subscription' => $deductFromSubscription,
                'recurring_interval' => $recurringInterval,
                'should_dispatch' => $shouldDispatchJob,
            ]);
            if ($shouldDispatchJob) {
                $interval = $recurringIntervalForRecurrence >= 1 ? $recurringIntervalForRecurrence : 1;
                ProcessLessonPostCreationJob::dispatch($lesson, $interval);
                Log::info("⚡ [LessonController] Job dispatché pour le cours {$lesson->id}", [
                    'deduct_from_subscription' => $deductFromSubscription,
                    'recurring_interval' => $recurringInterval,
                ]);
            } else {
                Log::info("⚡ [LessonController] Cours {$lesson->id} créé sans job (pas d'élève ou pas de déduction ni récurrence)");
            }

            // Charger les relations nécessaires pour la réponse
            $lesson->load([
                'teacher.user',
                'student.user',
                'student.subscriptionInstances' => function ($query) {
                    $query->where('status', 'active')
                          ->where('expires_at', '>=', now())
                          ->with(['subscription.template']);
                },
                'students:id,user_id',
                'students.user:id,name,email',
                'courseType',
                'location',
                'club'
            ]);

            return response()->json([
                'success' => true,
                'data' => $lesson,
                'message' => 'Cours créé avec succès'
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error in Lesson store:', [
                'errors' => $e->errors(),
                'request' => $request->all()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            // Vérifier si c'est une erreur de conflit horaire pour l'élève
            if (str_contains($e->getMessage(), 'déjà un cours programmé')) {
                Log::warning('Conflit horaire pour l\'élève:', [
                    'message' => $e->getMessage(),
                    'request' => $request->all()
                ]);
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage()
                ], 422);
            }
            // Vérifier si c'est une erreur de capacité de créneau
            if (str_contains($e->getMessage(), 'complet') || str_contains($e->getMessage(), 'capacité')) {
                Log::warning('Capacité de créneau atteinte:', [
                    'message' => $e->getMessage(),
                    'request' => $request->all()
                ]);
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'errors' => [
                        'start_time' => [$e->getMessage()]
                    ]
                ], 422);
            }
            
            Log::error('Exception in Lesson store:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du cours',
                'error' => $e->getMessage(),
                'debug' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/lessons/{id}",
     *     summary="Détails d'un cours",
     *     description="Récupère les détails d'un cours spécifique",
     *     operationId="getLesson",
     *     tags={"Lessons"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID du cours",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Détails du cours récupérés avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/Lesson")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Cours non trouvé"
     *     )
     * )
     */
    public function show(string $id): JsonResponse
    {
        try {
            $user = Auth::user();
            $query = Lesson::with([
                'teacher.user',
                'student.user',
                'student.subscriptionInstances' => function ($query) {
                    $query->where('status', 'active')
                          ->where('expires_at', '>=', now())
                          ->with(['subscription.template']);
                },
                'students:id,user_id',
                'students.user:id,name,email',
                'courseType',
                'location',
                'club',
                // ⚠️ IMPORTANT : Charger les subscription_instances directement liées au cours
                // (utilisées pour la suppression des cours futurs)
                'subscriptionInstances' => function ($query) {
                    $query->with(['subscription.template']);
                }
            ]);

            // Vérifier les permissions selon le rôle
            if ($user->role === 'teacher') {
                $query->whereHas('teacher', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
            } elseif ($user->role === 'student') {
                $query->whereHas('student', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
            }

            $lesson = $query->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $lesson
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Cours non trouvé'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du cours',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/lessons/{id}",
     *     summary="Mettre à jour un cours",
     *     description="Met à jour les informations d'un cours",
     *     operationId="updateLesson",
     *     tags={"Lessons"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID du cours",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="scheduled_at", type="string", format="datetime", example="2025-08-15 15:00:00"),
     *             @OA\Property(property="duration", type="integer", example=90),
     *             @OA\Property(property="price", type="number", format="float", example=60.00),
     *             @OA\Property(property="status", type="string", enum={"pending", "confirmed", "completed", "cancelled"}),
     *             @OA\Property(property="notes", type="string", example="Cours reporté à la demande de l'élève")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Cours mis à jour avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/Lesson"),
     *             @OA\Property(property="message", type="string", example="Cours mis à jour avec succès")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Cours non trouvé"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation"
     *     )
     * )
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $user = Auth::user();
            $query = Lesson::query();

            // Vérifier les permissions selon le rôle
            if ($user->role === 'teacher') {
                $query->whereHas('teacher', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
            } elseif ($user->role === 'student') {
                $query->whereHas('student', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
                // Les étudiants ne peuvent modifier que certains champs
                $allowedFields = ['notes'];
                $request = new Request($request->only($allowedFields));
            }

            $lesson = $query->findOrFail($id);

            $validationRules = [
                'teacher_id' => 'sometimes|exists:teachers,id',
                'start_time' => 'sometimes|date',
                'end_time' => 'sometimes|date|after:start_time',
                'duration' => 'sometimes|integer|min:15|max:180',
                'price' => 'sometimes|numeric|min:0',
                'status' => 'sometimes|in:pending,confirmed,completed,cancelled',
                'notes' => 'nullable|string|max:1000',
                'est_legacy' => 'nullable|boolean',
                'deduct_from_subscription' => 'nullable|boolean',
                'update_scope' => 'sometimes|in:single,all_future',
                'recurring_interval' => 'sometimes|integer|min:1|max:52', // Support pour modification de l'intervalle
            ];

            $validated = $request->validate($validationRules);
            $updateScope = $validated['update_scope'] ?? 'single';

            // Vérifier la disponibilité si la date/heure ou l'enseignant change
            if (isset($validated['start_time']) || isset($validated['teacher_id'])) {
                $newStartTime = $validated['start_time'] ?? $lesson->start_time;
                $newTeacherId = $validated['teacher_id'] ?? $lesson->teacher_id;
                // Calculer la durée : utiliser la durée fournie, ou celle du type de cours, ou 60 par défaut
                $newDuration = $validated['duration'] ?? null;
                if (!$newDuration && $lesson->courseType) {
                    $newDuration = $lesson->courseType->duration_minutes ?? $lesson->courseType->duration ?? 60;
                }
                $newDuration = $newDuration ?? 60;
                
                // Récupérer le club
                $clubId = $lesson->club_id;
                if (!$clubId) {
                    $user = Auth::user();
                    if ($user->role === 'club') {
                        $club = $user->getFirstClub();
                        $clubId = $club ? $club->id : null;
                    }
                }
                
                if ($clubId) {
                    // Compter les élèves du cours (pour vérifier la capacité)
                    $studentCount = 0;
                    if ($lesson->student_id) {
                        $studentCount++;
                    }
                    $studentCount += $lesson->students()->count();
                    
                    // Vérifier la disponibilité du créneau et de l'enseignant
                    // Exclure le cours actuel de la vérification
                    try {
                        $this->checkSlotCapacityForUpdate($newStartTime, $clubId, $newTeacherId, $studentCount, $lesson->id, $newDuration);
                    } catch (\Exception $e) {
                        return response()->json([
                            'success' => false,
                            'message' => $e->getMessage()
                        ], 422);
                    }
                }
            }

            // Si le statut passe à 'completed', déduire automatiquement le cours de l'abonnement
            $oldStatus = $lesson->status;
            $newStatus = $validated['status'] ?? $oldStatus;
            
            if ($oldStatus !== 'completed' && $newStatus === 'completed' && $lesson->student_id) {
                $this->consumeLessonFromSubscription($lesson);
            }

            // Stocker l'ancienne valeur de start_time avant la mise à jour
            $oldStartTime = $lesson->start_time ? Carbon::parse($lesson->start_time) : null;
            
            // Si start_time ou duration change, recalculer end_time
            if (isset($validated['start_time']) || isset($validated['duration'])) {
                $newStartTime = Carbon::parse($validated['start_time'] ?? $lesson->start_time);
                $duration = $validated['duration'] ?? null;
                if (!$duration && $lesson->courseType) {
                    $duration = $lesson->courseType->duration_minutes ?? $lesson->courseType->duration ?? 60;
                }
                $duration = $duration ?? 60;
                $validated['end_time'] = $newStartTime->copy()->addMinutes($duration)->format('Y-m-d H:i:s');
            }
            
            $lesson->update($validated);
            
            // Si update_scope est 'all_future' et que le cours fait partie d'un abonnement, mettre à jour tous les cours futurs
            $updatedFutureLessonsCount = 0;
            
            Log::info("🔍 Vérification mise à jour cours futurs", [
                'update_scope' => $updateScope,
                'old_start_time' => $oldStartTime ? $oldStartTime->toDateTimeString() : null,
                'has_start_time_in_validated' => isset($validated['start_time']),
                'start_time_in_validated' => $validated['start_time'] ?? null
            ]);
            
            if ($updateScope === 'all_future' && $oldStartTime && isset($validated['start_time'])) {
                // Recharger les relations pour avoir les subscription_instances
                $lesson->load('subscriptionInstances');
                
                Log::info("✅ Conditions remplies pour mise à jour cours futurs", [
                    'subscription_instances_count' => $lesson->subscriptionInstances()->count(),
                    'has_recurring_interval' => isset($validated['recurring_interval'])
                ]);
                
                if ($lesson->subscriptionInstances()->count() > 0) {
                    $subscriptionInstance = $lesson->subscriptionInstances()->first();
                    
                    // Utiliser l'ancienne date pour trouver les cours futurs (ceux qui étaient après l'ancien cours)
                    // Cela permet de trouver tous les cours qui doivent être décalés, même si le cours actuel a été déplacé vers une date antérieure
                    $oldLessonDate = $oldStartTime;
                    
                    // Récupérer les cours futurs de cette instance d'abonnement
                    // On cherche les cours qui étaient après l'ancienne date du cours modifié
                    $futureLessons = $subscriptionInstance->lessons()
                        ->where('lessons.start_time', '>', $oldLessonDate->toDateTimeString())
                        ->where('lessons.status', '!=', 'cancelled')
                        ->where('lessons.id', '!=', $lesson->id)
                        ->with('courseType') // Charger la relation courseType
                        ->orderBy('lessons.start_time', 'asc')
                        ->get();
                    
                    // 🔄 NOUVEAU : Si recurring_interval est fourni, supprimer tous les cours futurs et les régénérer
                    if (isset($validated['recurring_interval'])) {
                        Log::info("🔄 Changement d'intervalle de récurrence détecté", [
                            'new_recurring_interval' => $validated['recurring_interval'],
                            'future_lessons_to_delete' => $futureLessons->count()
                        ]);
                        
                        // Supprimer tous les cours futurs planifiés
                        $deletedCount = 0;
                        foreach ($futureLessons as $futureLesson) {
                            try {
                                $futureLesson->delete();
                                $deletedCount++;
                                Log::info("🗑️ Cours futur supprimé", [
                                    'lesson_id' => $futureLesson->id,
                                    'start_time' => $futureLesson->start_time
                                ]);
                            } catch (\Exception $e) {
                                Log::warning("❌ Impossible de supprimer le cours futur {$futureLesson->id}", [
                                    'error' => $e->getMessage()
                                ]);
                            }
                        }
                        
                        // Trouver ou créer le créneau récurrent correspondant
                        $newStartTime = Carbon::parse($lesson->start_time);
                        $dayOfWeek = $newStartTime->dayOfWeek;
                        $timeStart = $newStartTime->format('H:i:s');
                        
                        // Calculer la durée
                        $durationMinutes = $validated['duration'] ?? 60;
                        if (!$durationMinutes && $lesson->courseType) {
                            $durationMinutes = $lesson->courseType->duration_minutes ?? 60;
                        }
                        $timeEnd = $newStartTime->copy()->addMinutes($durationMinutes)->format('H:i:s');
                        
                        // Chercher un créneau récurrent existant pour cet abonnement et ce jour/heure
                        $recurringSlot = SubscriptionRecurringSlot::where('subscription_instance_id', $subscriptionInstance->id)
                            ->where('student_id', $lesson->student_id)
                            ->where('teacher_id', $lesson->teacher_id)
                            ->where('day_of_week', $dayOfWeek)
                            ->where('start_time', $timeStart)
                            ->first();
                        
                        if ($recurringSlot) {
                            // Mettre à jour l'intervalle du créneau récurrent existant
                            $recurringSlot->update([
                                'recurring_interval' => $validated['recurring_interval']
                            ]);
                            Log::info("✅ Créneau récurrent mis à jour avec nouvel intervalle", [
                                'recurring_slot_id' => $recurringSlot->id,
                                'new_interval' => $validated['recurring_interval']
                            ]);
                        } else {
                            // Créer un nouveau créneau récurrent si aucun n'existe
                            $recurringStartDate = $newStartTime->copy()->startOfDay();
                            $recurringEndDate = now()->addMonths(6);
                            if ($subscriptionInstance->expires_at && Carbon::parse($subscriptionInstance->expires_at)->lessThan($recurringEndDate)) {
                                $recurringEndDate = Carbon::parse($subscriptionInstance->expires_at);
                            }
                            
                            $recurringSlot = SubscriptionRecurringSlot::create([
                                'subscription_instance_id' => $subscriptionInstance->id,
                                'teacher_id' => $lesson->teacher_id,
                                'student_id' => $lesson->student_id,
                                'day_of_week' => $dayOfWeek,
                                'start_time' => $timeStart,
                                'end_time' => $timeEnd,
                                'recurring_interval' => $validated['recurring_interval'],
                                'start_date' => $recurringStartDate,
                                'end_date' => $recurringEndDate,
                            ]);
                            Log::info("✅ Nouveau créneau récurrent créé", [
                                'recurring_slot_id' => $recurringSlot->id,
                                'interval' => $validated['recurring_interval']
                            ]);
                        }
                        
                        // Régénérer les cours avec le nouvel intervalle
                        try {
                            $legacyService = new \App\Services\LegacyRecurringSlotService();
                            $startDate = $newStartTime->copy()->addWeeks($validated['recurring_interval']); // Commencer après le cours actuel
                            $stats = $legacyService->generateLessonsForSlot($recurringSlot, $startDate, null);
                            
                            Log::info("✅ Cours régénérés avec nouvel intervalle", [
                                'recurring_slot_id' => $recurringSlot->id,
                                'interval' => $validated['recurring_interval'],
                                'deleted_count' => $deletedCount,
                                'generated_count' => $stats['generated'],
                                'skipped' => $stats['skipped'],
                                'errors' => $stats['errors']
                            ]);
                            
                            $updatedFutureLessonsCount = $stats['generated'];
                        } catch (\Exception $e) {
                            Log::error("❌ Erreur lors de la régénération des cours", [
                                'error' => $e->getMessage(),
                                'trace' => $e->getTraceAsString()
                            ]);
                        }
                    } else {
                        // Logique existante : mettre à jour les cours futurs un par un
                        $newStartTime = Carbon::parse($lesson->start_time);
                        
                        // Extraire l'heure et les minutes de la nouvelle date/heure
                        // On va appliquer cette nouvelle heure à tous les cours futurs en gardant leurs dates respectives
                        $newHour = $newStartTime->hour;
                        $newMinute = $newStartTime->minute;
                        $newSecond = $newStartTime->second;
                        
                        // Calculer le décalage de jours (pour déplacer les dates si nécessaire)
                        $oldDate = $oldStartTime->copy()->startOfDay();
                        $newDate = $newStartTime->copy()->startOfDay();
                        $dateOffset = intval(($newDate->timestamp - $oldDate->timestamp) / 86400); // 86400 secondes par jour
                        
                        Log::info("🔄 Mise à jour des cours futurs", [
                            'lesson_id' => $lesson->id,
                            'old_start_time' => $oldStartTime->toDateTimeString(),
                            'new_start_time' => $newStartTime->toDateTimeString(),
                            'new_hour' => $newHour,
                            'new_minute' => $newMinute,
                            'date_offset_days' => $dateOffset,
                            'future_lessons_count' => $futureLessons->count(),
                            'subscription_instance_id' => $subscriptionInstance->id
                        ]);
                    
                        // Mettre à jour chaque cours futur
                        foreach ($futureLessons as $futureLesson) {
                    try {
                        $futureStartTime = Carbon::parse($futureLesson->start_time);
                        
                        // Appliquer le décalage de jours si nécessaire
                        $newFutureStartTime = $futureStartTime->copy();
                        if ($dateOffset > 0) {
                            $newFutureStartTime->addDays($dateOffset);
                        } elseif ($dateOffset < 0) {
                            $newFutureStartTime->subDays(abs($dateOffset));
                        }
                        
                        // Remplacer l'heure par la nouvelle heure (en gardant la date du cours futur)
                        // Cela garantit que tous les cours futurs auront la même heure que le cours modifié
                        $newFutureStartTime->setTime($newHour, $newMinute, $newSecond);
                        
                        Log::info("📝 Mise à jour cours futur", [
                            'future_lesson_id' => $futureLesson->id,
                            'old_start_time' => $futureStartTime->toDateTimeString(),
                            'new_start_time' => $newFutureStartTime->toDateTimeString(),
                            'date_offset_applied' => $dateOffset,
                            'new_hour_applied' => $newHour,
                            'new_minute_applied' => $newMinute
                        ]);
                        
                        // Vérifier la disponibilité avant de mettre à jour
                        $clubId = $futureLesson->club_id;
                        $teacherId = $validated['teacher_id'] ?? $futureLesson->teacher_id;
                        
                        // Compter les élèves du cours
                        $studentCount = 0;
                        if ($futureLesson->student_id) {
                            $studentCount++;
                        }
                        $studentCount += $futureLesson->students()->count();
                        
                        // Calculer la durée : utiliser la durée fournie, ou celle du type de cours, ou 60 par défaut
                        $duration = $validated['duration'] ?? null;
                        if (!$duration && $futureLesson->courseType) {
                            $duration = $futureLesson->courseType->duration_minutes ?? $futureLesson->courseType->duration ?? 60;
                        }
                        $duration = $duration ?? 60;
                        
                        // Vérifier la disponibilité
                        try {
                            $this->checkSlotCapacityForUpdate(
                                $newFutureStartTime->toDateTimeString(),
                                $clubId,
                                $teacherId,
                                $studentCount,
                                $futureLesson->id,
                                $duration
                            );
                            
                            // Calculer la nouvelle heure de fin
                            $newFutureEndTime = $newFutureStartTime->copy()->addMinutes($duration);
                            
                            // Mettre à jour le cours futur
                            $updateResult = $futureLesson->update([
                                'start_time' => $newFutureStartTime->toDateTimeString(),
                                'end_time' => $newFutureEndTime->toDateTimeString(),
                                'teacher_id' => $teacherId
                            ]);
                            
                            Log::info("✅ Cours futur mis à jour", [
                                'future_lesson_id' => $futureLesson->id,
                                'update_result' => $updateResult,
                                'new_start_time' => $newFutureStartTime->toDateTimeString(),
                                'new_end_time' => $newFutureEndTime->toDateTimeString()
                            ]);
                            
                            $updatedFutureLessonsCount++;
                        } catch (\Exception $e) {
                            // Si la mise à jour échoue pour un cours, continuer avec les autres
                            Log::warning("❌ Impossible de mettre à jour le cours futur {$futureLesson->id}", [
                                'error' => $e->getMessage(),
                                'trace' => $e->getTraceAsString()
                            ]);
                        }
                    } catch (\Exception $e) {
                        Log::warning("❌ Erreur lors de la mise à jour du cours futur {$futureLesson->id}", [
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString()
                        ]);
                    }
                }
                    } // Fin du else (logique de mise à jour individuelle)
                }
            }

            $message = 'Cours mis à jour avec succès';
            if ($updateScope === 'all_future' && $updatedFutureLessonsCount > 0) {
                $message .= ". {$updatedFutureLessonsCount} cours futur(s) ont également été mis à jour.";
            }

            return response()->json([
                'success' => true,
                'data' => $lesson->fresh([
                    'teacher.user',
                    'student.user',
                    'student.subscriptionInstances' => function ($query) {
                        $query->where('status', 'active')
                              ->where('expires_at', '>=', now())
                              ->with(['subscription.template']);
                    },
                    'students:id,user_id',
                    'students.user:id,name,email',
                    'courseType',
                    'location'
                ]),
                'message' => $message,
                'updated_future_lessons_count' => $updatedFutureLessonsCount
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Cours non trouvé'
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la mise à jour du cours', [
                'lesson_id' => $id,
                'user_id' => Auth::id(),
                'error_message' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du cours: ' . $e->getMessage(),
                'error' => config('app.debug') ? $e->getMessage() : 'Une erreur est survenue lors de la mise à jour'
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/lessons/{id}",
     *     summary="Supprimer un cours",
     *     description="Supprime un cours (ou l'annule selon le contexte)",
     *     operationId="deleteLesson",
     *     tags={"Lessons"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID du cours",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Cours supprimé avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Cours supprimé avec succès")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Cours non trouvé"
     *     )
     * )
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        try {
            $user = Auth::user();
            // Club avec cancel_scope (modale planning) → déléguer à cancelWithFuture pour gérer single/all_future
            if ($user->role === 'club' && $request->input('cancel_scope')) {
                return $this->cancelWithFuture($request, $id);
            }

            $query = Lesson::query();

            // Vérifier les permissions selon le rôle
            if ($user->role === 'teacher') {
                $query->whereHas('teacher', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
            } elseif ($user->role === 'student') {
                $query->whereHas('student', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
            } elseif ($user->role === 'club') {
                $club = $user->getFirstClub();
                if ($club) {
                    $query->where('club_id', $club->id);
                }
            }

            $lesson = $query->findOrFail($id);

            // Si le cours est dans le futur et a le statut 'pending', on l'annule
            // Sinon on le supprime définitivement (pour les admins principalement)
            if ($lesson->start_time > now() && $lesson->status === 'pending') {
                $lesson->update(['status' => 'cancelled']);
                $message = 'Cours annulé avec succès';
            } else {
                $lesson->delete();
                $message = 'Cours supprimé avec succès';
            }

            return response()->json([
                'success' => true,
                'message' => $message
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Cours non trouvé'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression du cours',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/students/{id}/lessons",
     *     summary="Cours d'un étudiant",
     *     description="Récupère les cours d'un étudiant spécifique",
     *     operationId="getStudentLessons",
     *     tags={"Lessons"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID de l'étudiant",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Cours de l'étudiant récupérés avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Lesson")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Étudiant non trouvé"
     *     )
     * )
     */
    public function studentLessons(string $id): JsonResponse
    {
        try {
            $user = Auth::user();

            // Vérifier que l'utilisateur a le droit de voir ces cours
            if ($user->role === 'student') {
                $student = $user->student;
                if (!$student || $student->id != $id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Accès non autorisé'
                    ], 403);
                }
            }

            $lessons = Lesson::where('student_id', $id)
                ->with(['teacher.user', 'courseType', 'location'])
                ->orderBy('start_time', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $lessons
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des cours',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Envoie les notifications de réservation
     */
    private function sendBookingNotifications(Lesson $lesson): void
    {
        try {
            // Notifier l'enseignant
            if ($lesson->teacher && $lesson->teacher->user) {
                $lesson->teacher->user->notify(new LessonBookedNotification($lesson));
            }

            // Notifier l'élève si ce n'est pas lui qui a créé la réservation
            if ($lesson->student && $lesson->student->user && $lesson->student->user->id !== Auth::id()) {
                $lesson->student->user->notify(new LessonBookedNotification($lesson));
            }
        } catch (\Exception $e) {
            // Log l'erreur mais ne pas faire échouer la création de la leçon
            Log::error("Erreur lors de l'envoi des notifications de réservation: " . $e->getMessage());
        }
    }

    /**
     * Envoie les notifications d'annulation
     */
    private function sendCancellationNotifications(Lesson $lesson, string $reason = ''): void
    {
        try {
            // Notifier l'enseignant
            if ($lesson->teacher && $lesson->teacher->user) {
                $lesson->teacher->user->notify(new LessonCancelledNotification($lesson, $reason));
            }

            // Notifier l'élève
            if ($lesson->student && $lesson->student->user) {
                $lesson->student->user->notify(new LessonCancelledNotification($lesson, $reason));
            }
        } catch (\Exception $e) {
            Log::error("Erreur lors de l'envoi des notifications d'annulation: " . $e->getMessage());
        }
    }

    /**
     * Consomme un cours de l'abonnement quand le statut passe à 'completed'
     */
    private function consumeLessonFromSubscription(Lesson $lesson): void
    {
        // Utiliser la même logique que tryConsumeSubscription
        $this->tryConsumeSubscription($lesson);
    }

    /**
     * Essaie de consommer un abonnement actif pour ce cours
     * RÈGLE FIFO : Consomme toujours l'abonnement le plus ancien en premier
     */
    private function tryConsumeSubscription(Lesson $lesson): void
    {
        try {
            if (!$lesson->course_type_id) {
                return;
            }

            // Récupérer les IDs des étudiants pour ce cours
            // Vérifier d'abord student_id (ancien système), sinon la relation many-to-many
            $studentIds = [];
            if ($lesson->student_id) {
                $studentIds[] = $lesson->student_id;
            }
            
            // Charger aussi les étudiants via la relation many-to-many
            $lessonStudents = $lesson->students()->pluck('students.id')->toArray();
            $studentIds = array_unique(array_merge($studentIds, $lessonStudents));
            
            // Si aucun étudiant, pas d'abonnement à consommer
            if (empty($studentIds)) {
                return;
            }

            // Pour chaque étudiant du cours, essayer de consommer un abonnement
            foreach ($studentIds as $studentId) {
                // Récupérer les instances d'abonnements actifs où l'élève est inscrit
                // 📌 IMPORTANT : Tri par started_at ASC pour consommer le plus ancien en premier (FIFO)
                $subscriptionInstances = SubscriptionInstance::where('status', 'active')
                    ->whereHas('students', function ($query) use ($studentId) {
                        $query->where('students.id', $studentId);
                    })
                    ->with(['subscription.courseTypes', 'students'])
                    ->orderBy('started_at', 'asc') // 🔄 FIFO : Le plus ancien d'abord
                    ->get();

                Log::info("🔍 Recherche d'abonnement pour le cours {$lesson->id}", [
                    'student_id' => $studentId,
                    'course_type_id' => $lesson->course_type_id,
                    'subscriptions_found' => $subscriptionInstances->count(),
                    'order' => 'FIFO (oldest first)'
                ]);

                // Trouver la première instance valide pour ce type de cours
                foreach ($subscriptionInstances as $subscriptionInstance) {
                    $subscriptionInstance->checkAndUpdateStatus();
                    
                    // Si le statut n'est plus actif après la vérification, passer au suivant
                    if ($subscriptionInstance->status !== 'active') {
                        continue;
                    }

                    // Vérifier si ce cours fait partie de l'abonnement
                    $courseTypeIds = $subscriptionInstance->subscription->courseTypes->pluck('id')->toArray();
                    
                    // ⚠️ Ne pas recalculer ici pour préserver les valeurs manuelles
                    // remaining_lessons utilise directement lessons_used qui peut contenir une valeur manuelle
                    // consumeLesson() gérera l'incrémentation correctement
                    
                    if (in_array($lesson->course_type_id, $courseTypeIds) && $subscriptionInstance->remaining_lessons > 0) {
                        try {
                            // Consommer un cours de cet abonnement
                            $subscriptionInstance->consumeLesson($lesson);
                            
                            $studentNames = $subscriptionInstance->students->map(function ($student) {
                                if ($student->user) {
                                    return $student->user->name;
                                }
                                $firstName = $student->first_name ?? '';
                                $lastName = $student->last_name ?? '';
                                $name = trim($firstName . ' ' . $lastName);
                                return !empty($name) ? $name : 'Élève sans nom';
                            })->filter()->join(', ');
                            
                            // Recharger l'instance pour avoir les valeurs à jour
                            $subscriptionInstance->refresh();
                            
                            // 📦 ARCHIVAGE : Si l'abonnement est plein (100% utilisé), le marquer comme completed
                            $totalLessons = $subscriptionInstance->subscription->total_available_lessons;
                            $isFullyUsed = $subscriptionInstance->lessons_used >= $totalLessons;
                            
                            if ($isFullyUsed && $subscriptionInstance->status === 'active') {
                                $subscriptionInstance->status = 'completed';
                                $subscriptionInstance->save();
                                
                                Log::info("📦 Abonnement {$subscriptionInstance->id} ARCHIVÉ (100% utilisé)", [
                                    'subscription_instance_id' => $subscriptionInstance->id,
                                    'lessons_used' => $subscriptionInstance->lessons_used,
                                    'total_lessons' => $totalLessons,
                                    'students' => $studentNames
                                ]);
                            }
                            
                            Log::info("✅ Cours {$lesson->id} consommé depuis l'abonnement {$subscriptionInstance->id} (FIFO)", [
                                'lesson_id' => $lesson->id,
                                'subscription_instance_id' => $subscriptionInstance->id,
                                'student_id' => $studentId,
                                'started_at' => $subscriptionInstance->started_at,
                                'lessons_used' => $subscriptionInstance->lessons_used,
                                'total_lessons' => $totalLessons,
                                'remaining_lessons' => $subscriptionInstance->remaining_lessons,
                                'status' => $subscriptionInstance->status,
                                'students' => $studentNames,
                                'archived' => $isFullyUsed
                            ]);
                            
                            break; // Un seul abonnement consommé par étudiant
                        } catch (\Exception $e) {
                            Log::error("❌ Erreur lors de la consommation du cours {$lesson->id} depuis l'abonnement {$subscriptionInstance->id}: " . $e->getMessage(), [
                                'lesson_id' => $lesson->id,
                                'subscription_instance_id' => $subscriptionInstance->id,
                                'student_id' => $studentId,
                                'error' => $e->getMessage(),
                                'trace' => $e->getTraceAsString()
                            ]);
                            // Continuer avec l'instance suivante si celle-ci échoue
                            continue;
                        }
                    }
                }
            }

            Log::info("Aucun abonnement actif trouvé pour le cours {$lesson->id} (étudiants: " . implode(', ', $studentIds) . ", type {$lesson->course_type_id})");
        } catch (\Exception $e) {
            // Log l'erreur mais ne pas faire échouer la création du cours
            Log::error("Erreur lors de la consommation de l'abonnement: " . $e->getMessage());
        }
    }

    /**
     * Vérifie que le créneau n'est pas complet avant de créer un cours
     * 
     * Vérifie deux choses :
     * 1. max_slots : Nombre total de cours simultanés possibles dans le créneau
     * 2. max_capacity : Nombre maximum d'élèves pour l'enseignant dans ce créneau
     */
    private function checkSlotCapacity(string $startTime, int $clubId, int $teacherId, int $newLessonStudentCount = 1, array $validated = []): void
    {
        try {
            $startDateTime = Carbon::parse($startTime);
            $dayOfWeek = $startDateTime->dayOfWeek;
            $time = $startDateTime->format('H:i');
            $date = $startDateTime->format('Y-m-d');
            
            // Trouver le créneau ouvert correspondant
            $openSlot = ClubOpenSlot::where('club_id', $clubId)
                ->where('day_of_week', $dayOfWeek)
                ->where('start_time', '<=', $time)
                ->where('end_time', '>', $time)
                ->first();
            
            if (!$openSlot) {
                // Pas de créneau défini, on autorise (pour compatibilité)
                return;
            }
            
            // 1. Vérifier max_slots : Nombre total de cours simultanés qui se chevauchent avec le nouveau cours
            // ⚠️ IMPORTANT : Vérifier uniquement les cours qui se chevauchent réellement, pas tous les cours dans la plage horaire
            // Un cours se chevauche si : start_time < other_end_time && end_time > other_start_time
            
            // Calculer la fin du nouveau cours (on a besoin de la durée)
            // La durée devrait être dans $validated, mais on peut aussi la récupérer depuis le CourseType
            $courseType = null;
            if (isset($validated['course_type_id'])) {
                $courseType = \App\Models\CourseType::find($validated['course_type_id']);
            }
            $duration = $validated['duration'] ?? ($courseType ? $courseType->duration_minutes : 60);
            $newLessonEndTime = $startDateTime->copy()->addMinutes($duration);
            
            // Charger la relation courseType pour les cours existants
            $existingLessons = Lesson::where('club_id', $clubId)
                ->whereDate('start_time', $date)
                ->where('status', '!=', 'cancelled') // Ignorer les cours annulés
                ->with('courseType') // Charger la relation pour obtenir la durée
                ->get();
            
            // Compter les cours qui se chevauchent avec le nouveau cours
            $overlappingLessonsCount = 0;
            foreach ($existingLessons as $lesson) {
                // Calculer la fin du cours existant
                $lessonStart = Carbon::parse($lesson->start_time);
                $lessonEnd = $lessonStart->copy();
                
                // Si le cours a une durée stockée, l'utiliser, sinon utiliser la durée du type de cours
                if ($lesson->courseType && $lesson->courseType->duration_minutes) {
                    $lessonEnd->addMinutes($lesson->courseType->duration_minutes);
                } else {
                    // Fallback : utiliser end_time si disponible, sinon 60 minutes par défaut
                    if ($lesson->end_time) {
                        $lessonEnd = Carbon::parse($lesson->end_time);
                    } else {
                        $lessonEnd->addMinutes(60);
                    }
                }
                
                // Vérifier le chevauchement : le nouveau cours chevauche si :
                // - Il commence avant la fin du cours existant ET
                // - Il se termine après le début du cours existant
                if ($startDateTime->lt($lessonEnd) && $newLessonEndTime->gt($lessonStart)) {
                    $overlappingLessonsCount++;
                }
            }
            
            $maxSlots = $openSlot->max_slots ?? 1; // Par défaut 1 si non défini
            
            if ($overlappingLessonsCount >= $maxSlots) {
                throw new \Exception("Ce créneau est complet ({$overlappingLessonsCount}/{$maxSlots} plages simultanées). Impossible d'ajouter un nouveau cours.");
            }
            
            // 2. Vérifier qu'un enseignant n'a pas déjà un cours qui se chevauche avec le nouveau cours
            // ⚠️ IMPORTANT : Un enseignant ne peut pas avoir plusieurs cours qui se chevauchent dans le temps
            // même s'il a la capacité pour plusieurs élèves dans un seul cours
            $newLessonStart = Carbon::parse($startTime);
            $newLessonEnd = $newLessonStart->copy()->addMinutes($duration);
            
            $overlappingTeacherLessons = Lesson::where('club_id', $clubId)
                ->where('teacher_id', $teacherId)
                ->where('status', '!=', 'cancelled')
                ->whereDate('start_time', $date)
                ->get()
                ->filter(function ($lesson) use ($newLessonStart, $newLessonEnd) {
                    // Calculer la fin du cours existant
                    $lessonStart = Carbon::parse($lesson->start_time);
                    $lessonEnd = $lessonStart->copy();
                    
                    // Si le cours a une durée stockée, l'utiliser, sinon utiliser la durée du type de cours
                    if ($lesson->courseType && $lesson->courseType->duration_minutes) {
                        $lessonEnd->addMinutes($lesson->courseType->duration_minutes);
                    } else {
                        // Fallback : utiliser end_time si disponible, sinon 60 minutes par défaut
                        if ($lesson->end_time) {
                            $lessonEnd = Carbon::parse($lesson->end_time);
                        } else {
                            $lessonEnd->addMinutes(60);
                        }
                    }
                    
                    // Vérifier le chevauchement : les cours se chevauchent si :
                    // - Le nouveau cours commence avant la fin du cours existant ET
                    // - Le nouveau cours se termine après le début du cours existant
                    return $newLessonStart->lt($lessonEnd) && $newLessonEnd->gt($lessonStart);
                });
            
            if ($overlappingTeacherLessons->count() > 0) {
                $existingLesson = $overlappingTeacherLessons->first();
                $existingStart = Carbon::parse($existingLesson->start_time)->format('H:i');
                throw new \Exception("Cet enseignant a déjà un cours programmé qui se chevauche avec cette heure (début à {$existingStart}). Un enseignant ne peut pas avoir plusieurs cours simultanés.");
            }
            
            // 3. Vérifier max_capacity : Nombre maximum d'élèves pour cet enseignant à l'heure exacte du cours
            // ⚠️ IMPORTANT : Vérifier uniquement les cours qui commencent à la même heure (même start_time)
            // et non pas tous les cours dans la plage horaire du créneau ouvert
            // Utiliser une comparaison de datetime pour être compatible avec SQLite et MySQL
            $startDateTime = Carbon::parse($date . ' ' . $time . ':00');
            $endDateTime = $startDateTime->copy()->addMinute(); // +1 minute pour avoir la plage exacte
            
            $teacherLessonsAtThisTime = Lesson::where('club_id', $clubId)
                ->where('teacher_id', $teacherId)
                ->where('start_time', '>=', $startDateTime)
                ->where('start_time', '<', $endDateTime)
                ->where('status', '!=', 'cancelled')
                ->get();
            
            // Compter le nombre total d'élèves (student_id + relation many-to-many students)
            $totalStudentsForTeacher = 0;
            foreach ($teacherLessonsAtThisTime as $lesson) {
                // Compter student_id (ancien système)
                if ($lesson->student_id) {
                    $totalStudentsForTeacher++;
                }
                // Compter les étudiants via la relation many-to-many
                $totalStudentsForTeacher += $lesson->students()->count();
            }
            
            $maxCapacity = $openSlot->max_capacity ?? 1; // Par défaut 1 si non défini
            
            // Calculer le total après ajout du nouveau cours
            $totalAfterNewLesson = $totalStudentsForTeacher + $newLessonStudentCount;
            
            // Vérifier si on dépasse la capacité avec le nouveau cours
            if ($totalAfterNewLesson > $maxCapacity) {
                throw new \Exception("L'enseignant dépasserait sa capacité maximale d'élèves ({$totalAfterNewLesson}/{$maxCapacity} élèves) dans ce créneau. Actuellement : {$totalStudentsForTeacher} élèves, nouveau cours : {$newLessonStudentCount} élève(s).");
            }
            
        } catch (\Exception $e) {
            // Si c'est notre exception de capacité ou de chevauchement, la propager
            if (str_contains($e->getMessage(), 'complet') || 
                str_contains($e->getMessage(), 'capacité maximale') ||
                str_contains($e->getMessage(), 'déjà un cours programmé qui se chevauche') ||
                str_contains($e->getMessage(), 'plusieurs cours simultanés')) {
                throw $e;
            }
            // Sinon, logger et continuer (pour ne pas bloquer si erreur technique)
            Log::warning("Erreur lors de la vérification de capacité du créneau: " . $e->getMessage());
        }
    }

    /**
     * Vérifie que le créneau n'est pas complet avant de mettre à jour un cours
     * Similaire à checkSlotCapacity mais exclut le cours actuel de la vérification
     * 
     * @param string $startTime L'heure de début du nouveau cours
     * @param int $clubId L'ID du club
     * @param int $teacherId L'ID de l'enseignant
     * @param int $studentCount Le nombre d'élèves dans le cours
     * @param int $lessonId L'ID du cours à exclure de la vérification
     * @param int $duration La durée du cours en minutes
     * @throws \Exception Si le créneau est complet ou si l'enseignant n'est pas disponible
     */
    private function checkSlotCapacityForUpdate(string $startTime, int $clubId, int $teacherId, int $studentCount, int $lessonId, int $duration): void
    {
        try {
            $startDateTime = Carbon::parse($startTime);
            $dayOfWeek = $startDateTime->dayOfWeek;
            $time = $startDateTime->format('H:i');
            $date = $startDateTime->format('Y-m-d');
            
            // Trouver le créneau ouvert correspondant
            $openSlot = ClubOpenSlot::where('club_id', $clubId)
                ->where('day_of_week', $dayOfWeek)
                ->where('start_time', '<=', $time)
                ->where('end_time', '>', $time)
                ->first();
            
            if (!$openSlot) {
                // Pas de créneau défini, on autorise (pour compatibilité)
                return;
            }
            
            // Calculer la fin du cours mis à jour
            $newLessonEndTime = $startDateTime->copy()->addMinutes($duration);
            
            // Charger les cours existants en excluant le cours actuel
            $existingLessons = Lesson::where('club_id', $clubId)
                ->where('id', '!=', $lessonId) // Exclure le cours actuel
                ->whereDate('start_time', $date)
                ->where('status', '!=', 'cancelled')
                ->with('courseType')
                ->get();
            
            // Compter les cours qui se chevauchent avec le cours mis à jour
            $overlappingLessonsCount = 0;
            foreach ($existingLessons as $lesson) {
                $lessonStart = Carbon::parse($lesson->start_time);
                $lessonEnd = $lessonStart->copy();
                
                if ($lesson->courseType && $lesson->courseType->duration_minutes) {
                    $lessonEnd->addMinutes($lesson->courseType->duration_minutes);
                } else {
                    if ($lesson->end_time) {
                        $lessonEnd = Carbon::parse($lesson->end_time);
                    } else {
                        $lessonEnd->addMinutes(60);
                    }
                }
                
                if ($startDateTime->lt($lessonEnd) && $newLessonEndTime->gt($lessonStart)) {
                    $overlappingLessonsCount++;
                }
            }
            
            $maxSlots = $openSlot->max_slots ?? 1;
            
            if ($overlappingLessonsCount >= $maxSlots) {
                throw new \Exception("Ce créneau est complet ({$overlappingLessonsCount}/{$maxSlots} plages simultanées). Impossible de modifier ce cours.");
            }
            
            // Vérifier qu'un enseignant n'a pas déjà un cours qui se chevauche (en excluant le cours actuel)
            $newLessonStart = Carbon::parse($startTime);
            $newLessonEnd = $newLessonStart->copy()->addMinutes($duration);
            
            $overlappingTeacherLessons = Lesson::where('club_id', $clubId)
                ->where('teacher_id', $teacherId)
                ->where('id', '!=', $lessonId) // Exclure le cours actuel
                ->where('status', '!=', 'cancelled')
                ->whereDate('start_time', $date)
                ->get()
                ->filter(function ($lesson) use ($newLessonStart, $newLessonEnd) {
                    $lessonStart = Carbon::parse($lesson->start_time);
                    $lessonEnd = $lessonStart->copy();
                    
                    if ($lesson->courseType && $lesson->courseType->duration_minutes) {
                        $lessonEnd->addMinutes($lesson->courseType->duration_minutes);
                    } else {
                        if ($lesson->end_time) {
                            $lessonEnd = Carbon::parse($lesson->end_time);
                        } else {
                            $lessonEnd->addMinutes(60);
                        }
                    }
                    
                    return $newLessonStart->lt($lessonEnd) && $newLessonEnd->gt($lessonStart);
                });
            
            if ($overlappingTeacherLessons->count() > 0) {
                $existingLesson = $overlappingTeacherLessons->first();
                $existingStart = Carbon::parse($existingLesson->start_time)->format('H:i');
                throw new \Exception("Cet enseignant a déjà un cours programmé qui se chevauche avec cette heure (début à {$existingStart}). Un enseignant ne peut pas avoir plusieurs cours simultanés.");
            }
            
            // Vérifier max_capacity : Nombre maximum d'élèves pour cet enseignant à l'heure exacte du cours
            $startDateTime = Carbon::parse($date . ' ' . $time . ':00');
            $endDateTime = $startDateTime->copy()->addMinute();
            
            $teacherLessonsAtThisTime = Lesson::where('club_id', $clubId)
                ->where('teacher_id', $teacherId)
                ->where('id', '!=', $lessonId) // Exclure le cours actuel
                ->where('start_time', '>=', $startDateTime)
                ->where('start_time', '<', $endDateTime)
                ->where('status', '!=', 'cancelled')
                ->get();
            
            $totalStudentsForTeacher = 0;
            foreach ($teacherLessonsAtThisTime as $lesson) {
                if ($lesson->student_id) {
                    $totalStudentsForTeacher++;
                }
                $totalStudentsForTeacher += $lesson->students()->count();
            }
            
            $maxCapacity = $openSlot->max_capacity ?? 1;
            $totalAfterUpdate = $totalStudentsForTeacher + $studentCount;
            
            if ($totalAfterUpdate > $maxCapacity) {
                throw new \Exception("L'enseignant dépasserait sa capacité maximale d'élèves ({$totalAfterUpdate}/{$maxCapacity} élèves) dans ce créneau. Actuellement : {$totalStudentsForTeacher} élèves, cours modifié : {$studentCount} élève(s).");
            }
            
        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'complet') || 
                str_contains($e->getMessage(), 'capacité maximale') ||
                str_contains($e->getMessage(), 'déjà un cours programmé qui se chevauche') ||
                str_contains($e->getMessage(), 'plusieurs cours simultanés')) {
                throw $e;
            }
            Log::warning("Erreur lors de la vérification de capacité du créneau pour mise à jour: " . $e->getMessage());
        }
    }

    /**
     * Vérifie qu'un élève n'est pas déjà inscrit à la même heure
     * 
     * @param int $studentId L'ID de l'élève
     * @param string $startTime L'heure de début du cours (format Y-m-d H:i:s)
     * @throws \Exception Si l'élève a déjà un cours à cette heure
     */
    private function checkStudentTimeConflict(int $studentId, string $startTime): void
    {
        try {
            $startDateTime = Carbon::parse($startTime);
            $date = $startDateTime->format('Y-m-d');
            $time = $startDateTime->format('H:i');
            
            // Créer une plage de temps très précise (même heure et minute)
            $exactStartTime = Carbon::parse($date . ' ' . $time . ':00');
            $exactEndTime = $exactStartTime->copy()->addMinute(); // +1 minute pour avoir la plage exacte
            
            // Vérifier les cours où l'élève est l'étudiant principal (student_id)
            $conflictingLessonsAsMain = Lesson::where('student_id', $studentId)
                ->where('start_time', '>=', $exactStartTime)
                ->where('start_time', '<', $exactEndTime)
                ->where('status', '!=', 'cancelled') // Ignorer les cours annulés
                ->exists();
            
            if ($conflictingLessonsAsMain) {
                throw new \Exception("Cet élève a déjà un cours programmé à cette heure ({$time}).");
            }
            
            // Vérifier les cours où l'élève est dans la relation many-to-many (students)
            $conflictingLessonsAsSecondary = Lesson::whereHas('students', function ($query) use ($studentId) {
                    $query->where('students.id', $studentId);
                })
                ->where('start_time', '>=', $exactStartTime)
                ->where('start_time', '<', $exactEndTime)
                ->where('status', '!=', 'cancelled') // Ignorer les cours annulés
                ->exists();
            
            if ($conflictingLessonsAsSecondary) {
                throw new \Exception("Cet élève a déjà un cours programmé à cette heure ({$time}).");
            }
            
        } catch (\Exception $e) {
            // Si c'est notre exception de conflit, la propager
            if (str_contains($e->getMessage(), 'déjà un cours programmé')) {
                throw $e;
            }
            // Sinon, logger et continuer (pour ne pas bloquer si erreur technique)
            Log::warning("Erreur lors de la vérification de conflit horaire pour l'élève: " . $e->getMessage());
        }
    }

    /**
     * 🔄 Crée automatiquement un créneau récurrent si l'élève a un abonnement actif
     * 
     * IMPORTANT : Ceci RÉSERVE le créneau (pas un blocage dur)
     * - Les créneaux réservés servent d'avertissement mais n'empêchent PAS la création d'autres cours
     * - Ils peuvent être libérés manuellement via l'API /club/recurring-slots/{id}/release
     * - Utile pour gérer les abonnements à terme ou les changements de planning
     * 
     * @param Lesson $lesson Le cours qui déclenche la réservation
     */
    private function createRecurringSlotIfSubscription(Lesson $lesson): void
    {
        try {
            if (!$lesson->student_id || !$lesson->teacher_id) {
                return;
            }

            // Vérifier si l'élève a un abonnement actif
            $activeSubscription = SubscriptionInstance::where('status', 'active')
                ->whereHas('students', function ($query) use ($lesson) {
                    $query->where('students.id', $lesson->student_id);
                })
                ->with('subscription')
                ->orderBy('started_at', 'asc')
                ->first();

            if (!$activeSubscription) {
                Log::info("🔄 Pas de récurrence créée : aucun abonnement actif pour l'élève {$lesson->student_id}");
                return;
            }

            // Extraire les informations du cours
            $startTime = Carbon::parse($lesson->start_time);
            $dayOfWeek = $startTime->dayOfWeek; // 0 = Dimanche, 1 = Lundi, etc.
            $timeStart = $startTime->format('H:i:s');
            $timeEnd = $startTime->copy()->addMinutes($lesson->duration ?? 60)->format('H:i:s');

            // Date de début : date du cours (pas aujourd'hui, pour éviter de bloquer des créneaux dans le passé)
            $recurringStartDate = Carbon::parse($lesson->start_time)->startOfDay();
            
            // Date de fin : 6 mois à partir d'aujourd'hui OU date d'expiration de l'abonnement (le plus proche)
            $recurringEndDate = now()->addMonths(6);
            if ($activeSubscription->expires_at && Carbon::parse($activeSubscription->expires_at)->lessThan($recurringEndDate)) {
                $recurringEndDate = Carbon::parse($activeSubscription->expires_at);
            }

            // Vérifier si une récurrence existe déjà pour ce même créneau (même élève + même horaire)
            // ⚠️ IMPORTANT : Un élève ne peut pas avoir plusieurs créneaux récurrents au même jour/heure
            // même avec des enseignants différents, car cela créerait des conflits
            $existingRecurring = SubscriptionRecurringSlot::where('student_id', $lesson->student_id)
                ->where('day_of_week', $dayOfWeek)
                ->where('start_time', $timeStart)
                ->where('end_time', $timeEnd)
                ->where('status', 'active')
                ->where(function ($query) use ($recurringStartDate, $recurringEndDate) {
                    // Vérifier que les périodes se chevauchent
                    $query->where(function ($q) use ($recurringStartDate, $recurringEndDate) {
                        // Le créneau existant commence avant la fin du nouveau ET se termine après le début du nouveau
                        $q->where('start_date', '<=', $recurringEndDate)
                          ->where('end_date', '>=', $recurringStartDate);
                    });
                })
                ->first();

            if ($existingRecurring) {
                Log::warning("⚠️ Récurrence déjà existante pour ce créneau - Doublon détecté", [
                    'existing_recurring_slot_id' => $existingRecurring->id,
                    'existing_teacher_id' => $existingRecurring->teacher_id,
                    'new_teacher_id' => $lesson->teacher_id,
                    'student_id' => $lesson->student_id,
                    'day_of_week' => $dayOfWeek,
                    'start_time' => $timeStart,
                    'end_time' => $timeEnd,
                    'subscription_instance_id' => $activeSubscription->id,
                    'existing_subscription_instance_id' => $existingRecurring->subscription_instance_id,
                    'message' => 'Un créneau récurrent existe déjà pour cet élève à ce créneau horaire. Le nouveau créneau récurrent ne sera pas créé pour éviter les doublons.'
                ]);
                return;
            }

            // ⚠️ VÉRIFICATION DES CONFLITS : Vérifier s'il existe d'autres récurrences sur ce créneau
            $conflicts = $this->checkRecurringConflicts(
                $dayOfWeek,
                $timeStart,
                $timeEnd,
                $lesson->teacher_id,
                $lesson->student_id,
                $lesson->club_id,
                $recurringStartDate,
                $recurringEndDate
            );

            if (!empty($conflicts)) {
                Log::warning("⚠️ Conflits détectés lors de la réservation du créneau récurrent", [
                    'lesson_id' => $lesson->id,
                    'student_id' => $lesson->student_id,
                    'conflicts_count' => count($conflicts),
                    'conflicts' => array_slice($conflicts, 0, 5),
                    'note' => 'Créneaux RÉSERVÉS (pas bloqués) - Peuvent être libérés manuellement'
                ]);
            }

            // Validation 26 semaines (règle Planning & Recurrence) : refuser la création si conflits
            $validator = new \App\Services\RecurringSlotValidator();
            $recurringIntervalForValidation = 1;
            $validation26 = $validator->validateRecurringAvailabilityWithoutOpenSlot(
                (int) $lesson->teacher_id,
                (int) $lesson->student_id,
                $recurringStartDate->format('Y-m-d'),
                $dayOfWeek,
                $timeStart,
                $timeEnd,
                $recurringIntervalForValidation,
                (int) $lesson->id
            );
            if (!$validation26['valid']) {
                Log::warning("❌ Récurrence non créée : conflits sur 26 semaines", [
                    'lesson_id' => $lesson->id,
                    'conflicts_count' => count($validation26['conflicts']),
                    'conflicts' => array_slice($validation26['conflicts'], 0, 5),
                ]);
                return;
            }

            // Créer la réservation de créneau récurrent
            $recurringSlot = SubscriptionRecurringSlot::create([
                'subscription_instance_id' => $activeSubscription->id,
                'open_slot_id' => null, // Pas forcément lié à un open_slot
                'teacher_id' => $lesson->teacher_id,
                'student_id' => $lesson->student_id,
                'day_of_week' => $dayOfWeek,
                'start_time' => $timeStart,
                'end_time' => $timeEnd,
                'start_date' => $recurringStartDate,
                'end_date' => $recurringEndDate,
                'status' => 'active',
                'notes' => "Créneau récurrent RÉSERVÉ automatiquement pour le cours #{$lesson->id} - Peut être libéré via API si nécessaire",
            ]);

            $logData = [
                'recurring_slot_id' => $recurringSlot->id,
                'subscription_instance_id' => $activeSubscription->id,
                'lesson_id' => $lesson->id,
                'student_id' => $lesson->student_id,
                'teacher_id' => $lesson->teacher_id,
                'day_of_week' => $dayOfWeek,
                'start_time' => $timeStart,
                'end_time' => $timeEnd,
                'start_date' => $recurringStartDate->format('Y-m-d'),
                'end_date' => $recurringEndDate->format('Y-m-d'),
                'duration_months' => 6,
                'conflicts_detected' => !empty($conflicts),
                'note' => 'Réservation flexible - libérable via POST /club/recurring-slots/{id}/release'
            ];

            if (!empty($conflicts)) {
                Log::warning("⚠️ Créneau récurrent RÉSERVÉ avec avertissements", $logData);
            } else {
                Log::info("✅ Créneau récurrent RÉSERVÉ sans conflit", $logData);
            }

        } catch (\Exception $e) {
            // Log l'erreur mais ne pas faire échouer la création du cours
            Log::error("Erreur lors de la création de la récurrence: " . $e->getMessage(), [
                'lesson_id' => $lesson->id,
                'student_id' => $lesson->student_id,
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Vérifier s'il existe des conflits de récurrence sur un créneau donné
     * Retourne un tableau de conflits détectés
     */
    private function checkRecurringConflicts(
        int $dayOfWeek,
        string $startTime,
        string $endTime,
        int $teacherId,
        int $studentId,
        int $clubId,
        Carbon $startDate,
        Carbon $endDate
    ): array {
        $conflicts = [];

        // 1. Vérifier si l'enseignant a déjà une récurrence active sur ce créneau
        $teacherRecurringConflicts = SubscriptionRecurringSlot::where('teacher_id', $teacherId)
            ->where('day_of_week', $dayOfWeek)
            ->where('status', 'active')
            ->where(function ($query) use ($startDate, $endDate) {
                // Vérifier le chevauchement de dates
                $query->where(function ($q) use ($startDate, $endDate) {
                    $q->where('start_date', '<=', $endDate->format('Y-m-d'))
                      ->where('end_date', '>=', $startDate->format('Y-m-d'));
                });
            })
            ->where(function ($query) use ($startTime, $endTime) {
                // Vérifier le chevauchement d'horaires
                $query->where(function ($q) use ($startTime, $endTime) {
                    $q->where('start_time', '<', $endTime)
                      ->where('end_time', '>', $startTime);
                });
            })
            ->with(['student.user', 'subscriptionInstance'])
            ->get();

        foreach ($teacherRecurringConflicts as $conflict) {
            $studentName = $conflict->student->user->name ?? 
                          ($conflict->student->first_name . ' ' . $conflict->student->last_name) ?? 
                          'Élève inconnu';
            
            $conflicts[] = [
                'type' => 'teacher_recurring',
                'message' => "L'enseignant a déjà un créneau récurrent avec {$studentName}",
                'day_of_week' => $dayOfWeek,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'conflicting_student' => $studentName,
                'recurring_slot_id' => $conflict->id
            ];
        }

        // 2. Vérifier si l'élève lui-même a déjà une récurrence active sur ce créneau
        // (éviter qu'un élève ait 2 cours en même temps avec 2 enseignants différents)
        $studentRecurringConflicts = SubscriptionRecurringSlot::where('student_id', $studentId)
            ->where('day_of_week', $dayOfWeek)
            ->where('status', 'active')
            ->where(function ($query) use ($startDate, $endDate) {
                $query->where(function ($q) use ($startDate, $endDate) {
                    $q->where('start_date', '<=', $endDate->format('Y-m-d'))
                      ->where('end_date', '>=', $startDate->format('Y-m-d'));
                });
            })
            ->where(function ($query) use ($startTime, $endTime) {
                $query->where(function ($q) use ($startTime, $endTime) {
                    $q->where('start_time', '<', $endTime)
                      ->where('end_time', '>', $startTime);
                });
            })
            ->with(['teacher.user', 'subscriptionInstance'])
            ->get();

        foreach ($studentRecurringConflicts as $conflict) {
            $teacherName = $conflict->teacher->user->name ?? 'Enseignant inconnu';
            
            $conflicts[] = [
                'type' => 'student_recurring',
                'message' => "L'élève a déjà un créneau récurrent avec {$teacherName}",
                'day_of_week' => $dayOfWeek,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'conflicting_teacher' => $teacherName,
                'recurring_slot_id' => $conflict->id
            ];
        }

        // 3. Suggestions de créneaux alternatifs (si conflits détectés)
        if (!empty($conflicts)) {
            $alternatives = $this->findAlternativeSlots(
                $clubId,
                $teacherId,
                $dayOfWeek,
                $startTime,
                $endTime,
                $startDate
            );
            
            if (!empty($alternatives)) {
                Log::info("💡 Créneaux alternatifs suggérés", [
                    'alternatives_count' => count($alternatives),
                    'alternatives' => $alternatives
                ]);
            }
        }

        return $conflicts;
    }

    /**
     * Trouver des créneaux alternatifs en cas de conflit
     * Retourne un tableau de suggestions
     */
    private function findAlternativeSlots(
        int $clubId,
        int $teacherId,
        int $dayOfWeek,
        string $startTime,
        string $endTime,
        Carbon $startDate
    ): array {
        $alternatives = [];
        
        // Stratégie 1 : Même jour, horaires différents (± 1 heure)
        $startTimeCarbon = Carbon::parse($startTime);
        $duration = Carbon::parse($startTime)->diffInMinutes(Carbon::parse($endTime));
        
        foreach ([-60, -30, 30, 60] as $offset) {
            $altStartTime = $startTimeCarbon->copy()->addMinutes($offset)->format('H:i:s');
            $altEndTime = $startTimeCarbon->copy()->addMinutes($offset + $duration)->format('H:i:s');
            
            // Vérifier si ce créneau est libre
            $hasConflict = SubscriptionRecurringSlot::where('teacher_id', $teacherId)
                ->where('day_of_week', $dayOfWeek)
                ->where('status', 'active')
                ->where(function ($query) use ($altStartTime, $altEndTime) {
                    $query->where(function ($q) use ($altStartTime, $altEndTime) {
                        $q->where('start_time', '<', $altEndTime)
                          ->where('end_time', '>', $altStartTime);
                    });
                })
                ->exists();
            
            if (!$hasConflict) {
                $alternatives[] = [
                    'type' => 'same_day_different_time',
                    'day_of_week' => $dayOfWeek,
                    'start_time' => $altStartTime,
                    'end_time' => $altEndTime,
                    'description' => "Même jour, " . ($offset > 0 ? "+" : "") . ($offset / 60) . "h"
                ];
                
                if (count($alternatives) >= 3) break;
            }
        }
        
        // Stratégie 2 : Jours adjacents, même horaire
        foreach ([-1, 1, -2, 2] as $dayOffset) {
            $altDayOfWeek = ($dayOfWeek + $dayOffset + 7) % 7;
            
            $hasConflict = SubscriptionRecurringSlot::where('teacher_id', $teacherId)
                ->where('day_of_week', $altDayOfWeek)
                ->where('status', 'active')
                ->where(function ($query) use ($startTime, $endTime) {
                    $query->where(function ($q) use ($startTime, $endTime) {
                        $q->where('start_time', '<', $endTime)
                          ->where('end_time', '>', $startTime);
                    });
                })
                ->exists();
            
            if (!$hasConflict) {
                $dayNames = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
                $alternatives[] = [
                    'type' => 'different_day_same_time',
                    'day_of_week' => $altDayOfWeek,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'description' => $dayNames[$altDayOfWeek] . " (au lieu de " . $dayNames[$dayOfWeek] . ")"
                ];
                
                if (count($alternatives) >= 5) break;
            }
        }
        
        return array_slice($alternatives, 0, 5); // Limiter à 5 suggestions
    }

    /**
     * Modifier la relation cours-abonnement (lier ou délier un cours d'un abonnement)
     */
    public function updateSubscription(Request $request, string $id): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Seuls les clubs peuvent modifier cette relation
            if ($user->role !== 'club') {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès réservé aux clubs'
                ], 403);
            }

            $lesson = Lesson::findOrFail($id);
            
            $validated = $request->validate([
                'deduct_from_subscription' => 'required|boolean'
            ]);

            $deductFromSubscription = $validated['deduct_from_subscription'];

            if ($deductFromSubscription) {
                // Lier le cours à un abonnement si possible
                if ($lesson->student_id) {
                    $studentIds = [$lesson->student_id];
                    $lessonStudents = $lesson->students()->pluck('students.id')->toArray();
                    $studentIds = array_unique(array_merge($studentIds, $lessonStudents));
                    
                    foreach ($studentIds as $studentId) {
                        // Vérifier si le cours est déjà lié à un abonnement
                        if ($lesson->subscriptionInstances()->count() > 0) {
                            Log::info("⏭️ Cours {$lesson->id} déjà lié à un abonnement");
                            break;
                        }

                        // Trouver le bon abonnement actif pour cet élève et ce type de cours
                        $clubId = $lesson->club_id ?? null;
                        $subscriptionInstance = \App\Models\SubscriptionInstance::findActiveSubscriptionForLesson(
                            $studentId,
                            $lesson->course_type_id,
                            $clubId
                        );

                        if ($subscriptionInstance) {
                            try {
                                // Lier le cours à l'abonnement sans consommer (car le cours existe déjà)
                                $lesson->subscriptionInstances()->syncWithoutDetaching([$subscriptionInstance->id]);
                                
                                // Consommer le cours de l'abonnement
                                $subscriptionInstance->consumeLesson($lesson);
                                
                                Log::info("✅ Cours {$lesson->id} lié à l'abonnement {$subscriptionInstance->id}");
                                break; // Un seul abonnement par cours
                            } catch (\Exception $e) {
                                Log::error("❌ Erreur lors de la liaison: " . $e->getMessage());
                                continue;
                            }
                        }
                    }
                }
            } else {
                // Délier le cours de tous les abonnements (sans décrémenter lessons_used)
                $lesson->subscriptionInstances()->detach();
                Log::info("✅ Cours {$lesson->id} délié de tous les abonnements");
            }

            // Recharger les relations
            $lesson->load([
                'teacher.user',
                'student.user',
                'courseType',
                'location',
                'subscriptionInstances'
            ]);

            return response()->json([
                'success' => true,
                'data' => $lesson,
                'message' => 'Relation cours-abonnement modifiée avec succès'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Cours non trouvé'
            ], 404);
        } catch (\Exception $e) {
            Log::error("Erreur updateSubscription: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la modification de la relation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupérer les cours occupant un créneau donné
     * Utilisé pour afficher les conflits quand un créneau est plein
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getSlotOccupants(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'date' => 'required|date',
                'time' => 'required|date_format:H:i',
                'duration' => 'nullable|integer|min:15|max:480',
                'teacher_id' => 'nullable|integer|exists:teachers,id',
            ]);

            $user = Auth::user();
            $club = $user->getFirstClub();
            
            if (!$club) {
                return response()->json([
                    'success' => false,
                    'message' => 'Club non trouvé'
                ], 404);
            }

            $date = Carbon::parse($validated['date']);
            $time = $validated['time'];
            $duration = (int) ($validated['duration'] ?? 60);
            $teacherId = $validated['teacher_id'] ?? null;
            
            $startDateTime = Carbon::parse($validated['date'] . ' ' . $time);
            $endDateTime = $startDateTime->copy()->addMinutes($duration);
            $dayOfWeek = $startDateTime->dayOfWeek;

            // Récupérer le créneau ouvert correspondant
            $openSlot = ClubOpenSlot::where('club_id', $club->id)
                ->where('day_of_week', $dayOfWeek)
                ->where('start_time', '<=', $time)
                ->where('end_time', '>', $time)
                ->first();

            $maxSlots = $openSlot ? ($openSlot->max_slots ?? 1) : 1;
            $maxCapacity = $openSlot ? ($openSlot->max_capacity ?? null) : null;

            // Récupérer les cours qui chevauchent ce créneau
            $query = Lesson::where('club_id', $club->id)
                ->whereDate('start_time', $date->format('Y-m-d'))
                ->where('status', '!=', 'cancelled')
                ->with([
                    'teacher.user',
                    'student.user',
                    'students.user',
                    'courseType',
                    'subscriptionInstances.subscription.template'
                ]);

            // Si un enseignant est spécifié, filtrer par enseignant
            if ($teacherId) {
                $query->where('teacher_id', $teacherId);
            }

            $allLessonsOfDay = $query->get();

            // Filtrer pour ne garder que les cours qui chevauchent vraiment
            $overlappingLessons = $allLessonsOfDay->filter(function ($lesson) use ($startDateTime, $endDateTime) {
                $lessonStart = Carbon::parse($lesson->start_time);
                $lessonDuration = (int) ($lesson->courseType?->duration_minutes ?? $lesson->duration ?? 60);
                $lessonEnd = $lessonStart->copy()->addMinutes($lessonDuration);

                // Chevauchement : le nouveau cours chevauche si :
                // - Il commence avant la fin du cours existant ET
                // - Il se termine après le début du cours existant
                return $startDateTime < $lessonEnd && $endDateTime > $lessonStart;
            });

            // Enrichir les données des cours
            $lessonsData = $overlappingLessons->map(function ($lesson) {
                $studentName = null;
                
                // Récupérer le nom de l'élève (relation directe ou many-to-many)
                if ($lesson->student && $lesson->student->user) {
                    $studentName = $lesson->student->user->name;
                } elseif ($lesson->student) {
                    $studentName = trim(($lesson->student->first_name ?? '') . ' ' . ($lesson->student->last_name ?? ''));
                } elseif ($lesson->students->count() > 0) {
                    $studentName = $lesson->students->map(function ($s) {
                        return $s->user?->name ?? trim(($s->first_name ?? '') . ' ' . ($s->last_name ?? ''));
                    })->filter()->join(', ');
                }

                // Vérifier si le cours fait partie d'un abonnement
                $subscriptionInstance = $lesson->subscriptionInstances->first();
                $hasSubscription = $subscriptionInstance !== null;
                $subscriptionName = $hasSubscription 
                    ? ($subscriptionInstance->subscription?->template?->name ?? 'Abonnement')
                    : null;

                $duration = (int) ($lesson->courseType?->duration_minutes ?? $lesson->duration ?? 60);
                
                return [
                    'id' => $lesson->id,
                    'start_time' => $lesson->start_time,
                    'end_time' => Carbon::parse($lesson->start_time)
                        ->addMinutes($duration)
                        ->toDateTimeString(),
                    'duration' => $duration,
                    'status' => $lesson->status,
                    'teacher_name' => $lesson->teacher?->user?->name ?? 'Non assigné',
                    'teacher_id' => $lesson->teacher_id,
                    'student_name' => $studentName ?: 'Élève non défini',
                    'student_id' => $lesson->student_id,
                    'course_type_name' => $lesson->courseType?->name ?? 'Cours',
                    'course_type_id' => $lesson->course_type_id,
                    'has_subscription' => $hasSubscription,
                    'subscription_name' => $subscriptionName,
                    'subscription_instance_id' => $subscriptionInstance?->id,
                    'price' => $lesson->price,
                ];
            })->values();

            // Calculer si le créneau est plein
            $isSlotFull = $overlappingLessons->count() >= $maxSlots;

            return response()->json([
                'success' => true,
                'data' => [
                    'lessons' => $lessonsData,
                    'slot_info' => [
                        'date' => $date->format('Y-m-d'),
                        'time' => $time,
                        'day_of_week' => $dayOfWeek,
                        'max_slots' => $maxSlots,
                        'max_capacity' => $maxCapacity,
                        'current_count' => $overlappingLessons->count(),
                        'is_full' => $isSlotFull,
                        'available_slots' => max(0, $maxSlots - $overlappingLessons->count()),
                    ]
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error("Erreur getSlotOccupants: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des cours du créneau',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Annuler un cours avec option pour les cours futurs
     * 
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function cancelWithFuture(Request $request, string $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'cancel_scope' => 'required|in:single,all_future',
                'action' => 'required|in:cancel,delete', // Nouveau paramètre : cancel ou delete
                'reason' => 'nullable|string|max:500',
            ]);

            $user = Auth::user();
            $club = $user->getFirstClub();
            
            if (!$club) {
                return response()->json([
                    'success' => false,
                    'message' => 'Club non trouvé'
                ], 404);
            }

            $lesson = Lesson::where('club_id', $club->id)->findOrFail($id);
            $cancelScope = $validated['cancel_scope'];
            $action = $validated['action']; // 'cancel' ou 'delete'
            $reason = $validated['reason'] ?? ($action === 'delete' ? 'Supprimé définitivement par le club' : 'Annulé par le club');

            $processedCount = 0;
            $processedLessons = [];

            if ($cancelScope === 'single') {
                // Traiter uniquement ce cours
                if ($action === 'delete') {
                    // Supprimer définitivement (peut être appelé même si le cours est déjà annulé)
                    $lessonId = $lesson->id; // Sauvegarder l'ID avant suppression
                    $this->releaseSubscriptionLesson($lesson);
                    $lesson->delete();
                    $processedCount = 1;
                    $processedLessons[] = $lessonId; // Utiliser l'ID sauvegardé
                } else {
                    // Annuler (même si déjà annulé, on peut le réannuler/mettre à jour les notes)
                    $lesson->status = 'cancelled';
                    $lesson->notes = ($lesson->notes ? $lesson->notes . "\n" : '') . "[Annulé] " . $reason;
                    $lesson->save();
                    
                    // Libérer l'abonnement si lié
                    $this->releaseSubscriptionLesson($lesson);
                    
                    $processedCount = 1;
                    $processedLessons[] = $lesson->id;
                }
                
            } else {
                // Traiter ce cours et tous les cours futurs de la même série (abonnement)
                $lesson->load('subscriptionInstances');
                
                $lessonsToProcess = [$lesson];
                
                // Si le cours est lié à un abonnement, récupérer les cours futurs
                if ($lesson->subscriptionInstances->count() > 0) {
                    $subscriptionInstance = $lesson->subscriptionInstances->first();
                    
                    // Extraire les caractéristiques du créneau du cours actuel pour filtrer les cours futurs
                    $lessonStartDateTime = Carbon::parse($lesson->start_time);
                    $lessonEndDateTime = Carbon::parse($lesson->end_time);
                    
                    // Carbon dayOfWeek retourne 0 (Dimanche) à 6 (Samedi)
                    // MySQL DAYOFWEEK retourne 1 (Dimanche) à 7 (Samedi)
                    // Conversion : Carbon 0 (Dim) -> MySQL 1 (Dim), Carbon 6 (Sam) -> MySQL 7 (Sam)
                    $lessonDayOfWeekCarbon = $lessonStartDateTime->dayOfWeek;
                    $lessonDayOfWeekMySQL = $lessonDayOfWeekCarbon === 0 ? 1 : ($lessonDayOfWeekCarbon + 1);
                    
                    $lessonStartTime = $lessonStartDateTime->format('H:i:s'); // Format "HH:MM:SS"
                    $lessonEndTime = $lessonEndDateTime->format('H:i:s');
                    $lessonStudentId = $lesson->student_id;
                    $lessonClubId = $lesson->club_id;
                    
                    $futureLessonsQuery = $subscriptionInstance->lessons()
                        ->where('lessons.start_time', '>', $lessonStartDateTime)
                        ->where('lessons.id', '!=', $lesson->id)
                        // 🔒 VÉRIFICATION IMPORTANTE : Même créneau (même jour, même plage horaire, même élève, même club)
                        // Vérifier le jour de la semaine (MySQL DAYOFWEEK : 1=Dimanche, 7=Samedi)
                        ->whereRaw('DAYOFWEEK(lessons.start_time) = ?', [$lessonDayOfWeekMySQL])
                        // Vérifier la même plage horaire (même heure de début et fin)
                        ->whereRaw('TIME(lessons.start_time) = ?', [$lessonStartTime])
                        ->whereRaw('TIME(lessons.end_time) = ?', [$lessonEndTime])
                        // Vérifier le même élève
                        ->where('lessons.student_id', $lessonStudentId)
                        // Vérifier le même club
                        ->where('lessons.club_id', $lessonClubId);
                    
                    // Si on annule :
                    // - Si le cours actuel est annulé, on traite uniquement les cours futurs qui sont aussi annulés
                    // - Si le cours actuel n'est pas annulé, on traite uniquement les cours futurs non annulés
                    // Si on supprime définitivement, on inclut tous les cours (y compris annulés)
                    if ($action === 'cancel') {
                        if ($lesson->status === 'cancelled') {
                            // Si le cours actuel est annulé, on veut traiter uniquement les cours futurs qui sont aussi annulés
                            $futureLessonsQuery->where('lessons.status', '=', 'cancelled');
                        } else {
                            // Si le cours actuel n'est pas annulé, on traite uniquement les cours futurs non annulés
                            $futureLessonsQuery->where('lessons.status', '!=', 'cancelled');
                        }
                    }
                    // Pour 'delete', on inclut tous les cours (y compris annulés) - pas de filtre supplémentaire
                    
                    $futureLessons = $futureLessonsQuery->get();
                    $lessonsToProcess = array_merge($lessonsToProcess, $futureLessons->all());
                }
                
                // Traiter tous les cours (actuel + futurs)
                foreach ($lessonsToProcess as $lessonToProcess) {
                    if ($action === 'delete') {
                        // Supprimer définitivement (peut être appelé même si le cours est déjà annulé)
                        $lessonId = $lessonToProcess->id; // Sauvegarder l'ID avant suppression
                        $this->releaseSubscriptionLesson($lessonToProcess);
                        $lessonToProcess->delete();
                        $processedCount++;
                        $processedLessons[] = $lessonId; // Utiliser l'ID sauvegardé
                    } else {
                        // Annuler (action='cancel')
                        // Si le cours actuel est annulé, on traite uniquement les cours futurs annulés (pour mettre à jour leurs notes)
                        // Si le cours actuel n'est pas annulé, on annule les cours futurs non annulés
                        if ($lessonToProcess->status === 'cancelled') {
                            // Si déjà annulé, mettre à jour uniquement les notes pour tracer l'action
                            // Ne pas libérer l'abonnement car il a déjà été libéré lors de l'annulation initiale
                            $currentNotes = $lessonToProcess->notes ?? '';
                            if ($lessonToProcess->id === $lesson->id) {
                                $newNote = "[Annulé] " . $reason;
                            } else {
                                $newNote = "[Annulé en cascade] " . $reason;
                            }
                            // Éviter les doublons de notes
                            if (!str_contains($currentNotes, $newNote)) {
                                $lessonToProcess->notes = ($currentNotes ? $currentNotes . "\n" : '') . $newNote;
                                $lessonToProcess->save();
                            }
                        } else {
                            // Si pas encore annulé, annuler maintenant et libérer l'abonnement
                            if ($lessonToProcess->id === $lesson->id) {
                                $lessonToProcess->status = 'cancelled';
                                $lessonToProcess->notes = ($lessonToProcess->notes ? $lessonToProcess->notes . "\n" : '') . "[Annulé] " . $reason;
                            } else {
                                $lessonToProcess->status = 'cancelled';
                                $lessonToProcess->notes = ($lessonToProcess->notes ? $lessonToProcess->notes . "\n" : '') . "[Annulé en cascade] " . $reason;
                            }
                            $lessonToProcess->save();
                            // Libérer l'abonnement uniquement si le cours n'était pas déjà annulé
                            $this->releaseSubscriptionLesson($lessonToProcess);
                        }
                        
                        $processedCount++;
                        $processedLessons[] = $lessonToProcess->id;
                    }
                }
            }

            $actionText = $action === 'delete' ? 'supprimé' : 'annulé';
            $message = $processedCount === 1 
                ? "Cours {$actionText} avec succès" 
                : "{$processedCount} cours {$actionText}s avec succès";

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'processed_count' => $processedCount,
                    'processed_lesson_ids' => $processedLessons,
                    'action' => $action
                ]
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Cours non trouvé'
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error("Erreur cancelWithFuture: " . $e->getMessage(), [
                'lesson_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'annulation du cours',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Libérer un cours d'un abonnement (décrémente lessons_used)
     */
    private function releaseSubscriptionLesson(Lesson $lesson): void
    {
        try {
            $subscriptionInstances = $lesson->subscriptionInstances;
            
            foreach ($subscriptionInstances as $instance) {
                // Recalculer les cours utilisés (exclut les annulés)
                $instance->recalculateLessonsUsed();
            }
        } catch (\Exception $e) {
            Log::warning("Erreur lors de la libération de l'abonnement: " . $e->getMessage());
        }
    }
}
