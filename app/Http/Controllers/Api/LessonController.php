<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\User;
use App\Models\Teacher;
use App\Models\SubscriptionInstance;
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
            $query = Lesson::with(['teacher.user', 'student.user', 'courseType', 'location']);

            // Filtrage selon le rôle de l'utilisateur
            if ($user->role === 'teacher') {
                $query->whereHas('teacher', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
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

            if ($request->has('date_from')) {
                $query->whereDate('start_time', '>=', $request->date_from);
            }

            if ($request->has('date_to')) {
                $query->whereDate('start_time', '<=', $request->date_to);
            }

            $lessons = $query->orderBy('start_time', 'desc')->get();

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

            $lesson = Lesson::create($validated);

            // Essayer de consommer un abonnement si l'élève en a un actif
            if (isset($validated['student_id'])) {
                $this->tryConsumeSubscription($lesson);
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
                'data' => $lesson->load(['teacher.user', 'student.user', 'courseType', 'location']),
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
            $query = Lesson::with(['teacher.user', 'student.user', 'courseType', 'location']);

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
     * Essaie de consommer un abonnement actif pour ce cours
     */
    private function tryConsumeSubscription(Lesson $lesson): void
    {
        try {
            if (!$lesson->student_id || !$lesson->course_type_id) {
                return;
            }

            // Récupérer les instances d'abonnements actifs où l'élève est inscrit
            $subscriptionInstances = SubscriptionInstance::where('status', 'active')
                ->whereHas('students', function ($query) use ($lesson) {
                    $query->where('students.id', $lesson->student_id);
                })
                ->with(['subscription.courseTypes', 'students'])
                ->get();

            // Trouver la première instance valide pour ce type de cours
            foreach ($subscriptionInstances as $subscriptionInstance) {
                $subscriptionInstance->checkAndUpdateStatus();
                
                // Si le statut n'est plus actif après la vérification, passer au suivant
                if ($subscriptionInstance->status !== 'active') {
                    continue;
                }

                // Vérifier si ce cours fait partie de l'abonnement
                $courseTypeIds = $subscriptionInstance->subscription->courseTypes->pluck('id')->toArray();
                
                if (in_array($lesson->course_type_id, $courseTypeIds) && $subscriptionInstance->remaining_lessons > 0) {
                    // Consommer un cours de cet abonnement
                    $subscriptionInstance->consumeLesson($lesson);
                    
                    $studentNames = $subscriptionInstance->students->pluck('user.name')->join(', ');
                    Log::info("Cours {$lesson->id} consommé depuis l'abonnement partagé {$subscriptionInstance->id} (élèves: {$studentNames})");
                    
                    return; // Un seul abonnement consommé par cours
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
}
