<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\User;
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
            if ($user->isTeacher()) {
                $query->whereHas('teacher', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
            } elseif ($user->isStudent()) {
                $query->whereHas('student', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
            }
            // Les admins voient tous les cours

            // Filtres optionnels
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('date_from')) {
                $query->whereDate('scheduled_at', '>=', $request->date_from);
            }

            if ($request->has('date_to')) {
                $query->whereDate('scheduled_at', '<=', $request->date_to);
            }

            $lessons = $query->orderBy('scheduled_at', 'desc')->get();

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

            $validated = $request->validate([
                'teacher_id' => 'required|exists:teachers,id',
                'course_type_id' => 'required|exists:course_types,id',
                'location_id' => 'nullable|exists:locations,id',
                'scheduled_at' => 'required|date|after:now',
                'duration' => 'nullable|integer|min:15|max:180',
                'price' => 'nullable|numeric|min:0',
                'notes' => 'nullable|string|max:1000'
            ]);

            // Pour les étudiants, on assigne automatiquement leur student_id
            if ($user->role === User::ROLE_STUDENT) {
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
            }

            $validated['status'] = 'pending';

            $lesson = Lesson::create($validated);

            // Envoyer les notifications
            $this->sendBookingNotifications($lesson);

            // Programmer un rappel 24h avant le cours
            $reminderTime = Carbon::parse($lesson->scheduled_at)->subHours(24);
            if ($reminderTime->isFuture()) {
                SendLessonReminderJob::dispatch($lesson)->delay($reminderTime);
            }

            return response()->json([
                'success' => true,
                'data' => $lesson->load(['teacher.user', 'student.user', 'courseType', 'location']),
                'message' => 'Cours créé avec succès'
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du cours',
                'error' => $e->getMessage()
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
            if ($user->isTeacher()) {
                $query->whereHas('teacher', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
            } elseif ($user->isStudent()) {
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
            if ($user->isTeacher()) {
                $query->whereHas('teacher', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
            } elseif ($user->isStudent()) {
                $query->whereHas('student', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
                // Les étudiants ne peuvent modifier que certains champs
                $allowedFields = ['notes'];
                $request = new Request($request->only($allowedFields));
            }

            $lesson = $query->findOrFail($id);

            $validationRules = [
                'scheduled_at' => 'sometimes|date|after:now',
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
            if ($user->isTeacher()) {
                $query->whereHas('teacher', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
            } elseif ($user->isStudent()) {
                $query->whereHas('student', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
            }

            $lesson = $query->findOrFail($id);

            // Si le cours est dans le futur et a le statut 'pending', on l'annule
            // Sinon on le supprime définitivement (pour les admins principalement)
            if ($lesson->scheduled_at > now() && $lesson->status === 'pending') {
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
            if ($user->isStudent()) {
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
                ->orderBy('scheduled_at', 'desc')
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
}
