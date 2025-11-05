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
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
            $query = Lesson::select('lessons.id', 'lessons.teacher_id', 'lessons.student_id', 'lessons.course_type_id', 
                                   'lessons.location_id', 'lessons.club_id', 'lessons.start_time', 'lessons.end_time', 
                                   'lessons.status', 'lessons.price', 'lessons.notes', 'lessons.created_at', 'lessons.updated_at')
                ->with([
                    'teacher:id,user_id',
                    'teacher.user:id,name,email',
                    'student:id,user_id',
                    'student.user:id,name,email',
                    'student.subscriptionInstances' => function ($query) {
                        $query->where('status', 'active')
                              ->where('expires_at', '>=', now())
                              ->with(['subscription.template']);
                    },
                    'courseType:id,name',
                    'location:id,name',
                    'club:id,name,email,phone'
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
            } else {
                // Par défaut: filtrer sur les 7 prochains jours si aucune période spécifiée
                $now = now();
                $query->whereBetween('start_time', [
                    $now->copy()->startOfDay(),
                    $now->copy()->addDays(7)->endOfDay()
                ]);
            }

            if ($request->has('date_from')) {
                $query->whereDate('start_time', '>=', $request->date_from);
            }

            if ($request->has('date_to')) {
                $query->whereDate('start_time', '<=', $request->date_to);
            }

            // Limiter le nombre de résultats pour éviter les chargements trop longs
            $limit = min($request->get('limit', 50), 200); // Par défaut 50 cours max, max 200
            // ✅ Tri ASC (chronologique) pour afficher les cours à venir dans l'ordre
            $lessons = $query->orderBy('start_time', 'asc')
                ->limit($limit)
                ->get()
                ->makeHidden(['teacher_name', 'student_name', 'duration', 'title']); // Désactiver les accessors coûteux

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
            $user = Auth::user();

            // Validation de base avec date moins stricte
            $validated = $request->validate([
                'teacher_id' => 'required|exists:teachers,id',
                'course_type_id' => 'required|exists:course_types,id',
                'location_id' => 'nullable|exists:locations,id',
                'start_time' => 'required|date|after_or_equal:today',
                'duration' => 'nullable|integer|min:15|max:180',
                'price' => 'nullable|numeric|min:0',
                'notes' => 'nullable|string|max:1000'
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

            // Vérifier la capacité du créneau si c'est pour un club
            if ($user->role === 'club') {
                $club = $user->getFirstClub();
                if ($club) {
                    $this->checkSlotCapacity($validated['start_time'], $club->id);
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

            $lesson = Lesson::create($validated);

            // Essayer de consommer un abonnement si l'élève en a un actif
            if (isset($validated['student_id'])) {
                $this->tryConsumeSubscription($lesson);
                
                // 🔄 RÉCURRENCE AUTOMATIQUE : Bloquer le créneau pour les 6 prochains mois
                $this->createRecurringSlotIfSubscription($lesson);
            }

            // Envoyer les notifications
            $this->sendBookingNotifications($lesson);

            // Programmer un rappel 24h avant le cours
            try {
                $reminderTime = Carbon::parse($lesson->start_time)->subHours(24);
                if ($reminderTime->isFuture()) {
                    SendLessonReminderJob::dispatch($lesson)->delay($reminderTime);
                }
            } catch (\Exception $e) {
                // Logger l'erreur mais ne pas bloquer la création du cours
                Log::warning("Impossible de programmer le rappel pour le cours {$lesson->id}: " . $e->getMessage());
            }

            return response()->json([
                'success' => true,
                'data' => $lesson->load(['teacher.user', 'student.user', 'courseType', 'location', 'club']),
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
            $query = Lesson::with(['teacher.user', 'student.user', 'courseType', 'location', 'club']);

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
                'start_time' => 'sometimes|date|after:now',
                'duration' => 'sometimes|integer|min:15|max:180',
                'price' => 'sometimes|numeric|min:0',
                'status' => 'sometimes|in:pending,confirmed,completed,cancelled',
                'notes' => 'nullable|string|max:1000'
            ];

            $validated = $request->validate($validationRules);

            // Si le statut passe à 'completed', déduire automatiquement le cours de l'abonnement
            $oldStatus = $lesson->status;
            $newStatus = $validated['status'] ?? $oldStatus;
            
            if ($oldStatus !== 'completed' && $newStatus === 'completed' && $lesson->student_id) {
                $this->consumeLessonFromSubscription($lesson);
            }

            $lesson->update($validated);

            return response()->json([
                'success' => true,
                'data' => $lesson->fresh(['teacher.user', 'student.user', 'courseType', 'location']),
                'message' => 'Cours mis à jour avec succès'
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
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du cours',
                'error' => $e->getMessage()
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
    public function destroy(string $id): JsonResponse
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
            if (!$lesson->student_id || !$lesson->course_type_id) {
                return;
            }

            // Récupérer les instances d'abonnements actifs où l'élève est inscrit
            // 📌 IMPORTANT : Tri par started_at ASC pour consommer le plus ancien en premier (FIFO)
            $subscriptionInstances = SubscriptionInstance::where('status', 'active')
                ->whereHas('students', function ($query) use ($lesson) {
                    $query->where('students.id', $lesson->student_id);
                })
                ->with(['subscription.courseTypes', 'students'])
                ->orderBy('started_at', 'asc') // 🔄 FIFO : Le plus ancien d'abord
                ->get();

            Log::info("🔍 Recherche d'abonnement pour le cours {$lesson->id}", [
                'student_id' => $lesson->student_id,
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
                
                // Recalculer avant de vérifier remaining_lessons pour avoir la valeur à jour
                $subscriptionInstance->recalculateLessonsUsed();
                
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
                            'started_at' => $subscriptionInstance->started_at,
                            'lessons_used' => $subscriptionInstance->lessons_used,
                            'total_lessons' => $totalLessons,
                            'remaining_lessons' => $subscriptionInstance->remaining_lessons,
                            'status' => $subscriptionInstance->status,
                            'students' => $studentNames,
                            'archived' => $isFullyUsed
                        ]);
                        
                        return; // Un seul abonnement consommé par cours
                    } catch (\Exception $e) {
                        Log::error("❌ Erreur lors de la consommation du cours {$lesson->id} depuis l'abonnement {$subscriptionInstance->id}: " . $e->getMessage(), [
                            'lesson_id' => $lesson->id,
                            'subscription_instance_id' => $subscriptionInstance->id,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString()
                        ]);
                        // Continuer avec l'instance suivante si celle-ci échoue
                        continue;
                    }
                }
            }

            Log::info("Aucun abonnement actif trouvé pour le cours {$lesson->id} (élève {$lesson->student_id}, type {$lesson->course_type_id})");
        } catch (\Exception $e) {
            // Log l'erreur mais ne pas faire échouer la création du cours
            Log::error("Erreur lors de la consommation de l'abonnement: " . $e->getMessage());
        }
    }

    /**
     * Vérifie que le créneau n'est pas complet avant de créer un cours
     */
    private function checkSlotCapacity(string $startTime, int $clubId): void
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
            
            // Compter les cours déjà existants sur cette plage horaire pour cette date
            // 🔧 CORRECTION : Utilisation directe de club_id pour des requêtes plus performantes
            $existingLessonsCount = Lesson::where('club_id', $clubId)
                ->whereDate('start_time', $date)
                ->whereTime('start_time', '>=', $openSlot->start_time)
                ->whereTime('start_time', '<', $openSlot->end_time)
                ->count();
            
            if ($existingLessonsCount >= $openSlot->max_capacity) {
                throw new \Exception("Ce créneau est complet ({$existingLessonsCount}/{$openSlot->max_capacity} cours). Impossible d'ajouter un nouveau cours.");
            }
            
        } catch (\Exception $e) {
            // Si c'est notre exception de capacité, la propager
            if (str_contains($e->getMessage(), 'complet')) {
                throw $e;
            }
            // Sinon, logger et continuer (pour ne pas bloquer si erreur technique)
            Log::warning("Erreur lors de la vérification de capacité du créneau: " . $e->getMessage());
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

            // Vérifier si une récurrence existe déjà pour ce même créneau (même élève + enseignant)
            $existingRecurring = SubscriptionRecurringSlot::where('subscription_instance_id', $activeSubscription->id)
                ->where('student_id', $lesson->student_id)
                ->where('teacher_id', $lesson->teacher_id)
                ->where('day_of_week', $dayOfWeek)
                ->where('start_time', $timeStart)
                ->where('status', 'active')
                ->first();

            if ($existingRecurring) {
                Log::info("🔄 Récurrence déjà existante pour ce créneau", [
                    'recurring_slot_id' => $existingRecurring->id,
                    'student_id' => $lesson->student_id,
                    'day_of_week' => $dayOfWeek,
                    'start_time' => $timeStart
                ]);
                return;
            }

            // ⚠️ VÉRIFICATION DES CONFLITS : Vérifier s'il existe d'autres récurrences sur ce créneau
            $conflicts = $this->checkRecurringConflicts(
                $dayOfWeek,
                $timeStart,
                $timeEnd,
                $lesson->teacher_id,
                $lesson->club_id,
                $recurringStartDate,
                $recurringEndDate
            );

            if (!empty($conflicts)) {
                Log::warning("⚠️ Conflits détectés lors de la réservation du créneau récurrent", [
                    'lesson_id' => $lesson->id,
                    'student_id' => $lesson->student_id,
                    'conflicts_count' => count($conflicts),
                    'conflicts' => array_slice($conflicts, 0, 5), // Limiter aux 5 premiers
                    'note' => 'Créneaux RÉSERVÉS (pas bloqués) - Peuvent être libérés manuellement'
                ]);
                
                // On crée quand même la réservation mais on log l'avertissement
                // Les conflits n'empêchent PAS la création, ils servent juste d'avertissement
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
        // Note: On ne filtre pas par clubId car on veut détecter les conflits même inter-clubs
        $studentRecurringConflicts = SubscriptionRecurringSlot::where('day_of_week', $dayOfWeek)
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
}
