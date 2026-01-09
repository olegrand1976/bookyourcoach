<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Lesson;
use App\Models\User;
use App\Models\Teacher;
use App\Models\Student;
use App\Models\CourseType;
use App\Models\Location;
use App\Models\Club;
use App\Notifications\LessonCancelledByStudentNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use App\Models\SubscriptionInstance;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Récupère les statistiques pour le tableau de bord de l'élève.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStats(Request $request)
    {
        $user = $request->user();

        // Assurer que l'utilisateur est un élève ou a un profil élève
        if (!$user->student) {
            return response()->json([
                'success' => false,
                'message' => 'Profil étudiant non trouvé.'
            ], 404);
        }

        $studentId = $user->student->id;

        // Calcul des statistiques
        $upcoming_lessons = Lesson::where('student_id', $studentId)
            ->where('status', 'confirmed')
            ->where('start_time', '>=', Carbon::now())
            ->count();

        $completed_lessons = Lesson::where('student_id', $studentId)
            ->where('status', 'completed')
            ->count();

        $total_hours = Lesson::where('student_id', $studentId)
            ->where('status', 'completed')
            ->get()
            ->sum(function ($lesson) {
                return Carbon::parse($lesson->start_time)->diffInMinutes(Carbon::parse($lesson->end_time));
            }) / 60; // Convertir les minutes en heures

        return response()->json([
            'success' => true,
            'data' => [
                'availableLessons' => Lesson::where('status', 'available')
                    ->where('start_time', '>=', Carbon::now())
                    ->count(),
                'activeBookings' => $upcoming_lessons,
                'completedLessons' => $completed_lessons,
                'favoriteTeachers' => Teacher::whereHas('lessons', function ($q) use ($studentId) {
                    $q->where('student_id', $studentId)
                      ->where('status', 'completed');
                })->distinct()->count()
            ]
        ]);
    }

    /**
     * Récupère les cours disponibles pour l'étudiant.
     */
    public function getAvailableLessons(Request $request)
    {
        $query = Lesson::with(['teacher.user', 'courseType', 'location', 'club'])
            ->where('status', 'available')
            ->where('start_time', '>=', Carbon::now());

        if ($request->has('subject')) {
            $query->whereHas('courseType', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->subject . '%');
            });
        }

        if ($request->has('date')) {
            $date = Carbon::parse($request->date);
            $query->whereDate('start_time', $date);
        }

        if ($request->has('discipline')) {
            $query->whereHas('courseType', function ($q) use ($request) {
                $q->where('discipline_id', $request->discipline);
            });
        }

        if ($request->has('courseType')) {
            $query->where('course_type_id', $request->courseType);
        }

        if ($request->has('format')) {
            if ($request->format === 'individual') {
                $query->whereHas('courseType', function ($q) {
                    $q->where('is_individual', true);
                });
            } else if ($request->format === 'group') {
                $query->whereHas('courseType', function ($q) {
                    $q->where('is_individual', false);
                });
            }
        }

        $lessons = $query->orderBy('start_time', 'asc')->get();

        return response()->json([
            'success' => true,
            'data' => $lessons
        ]);
    }

    /**
     * Récupère les réservations de l'étudiant.
     */
    public function getBookings(Request $request)
    {
        $user = $request->user();
        
        if (!$user->student) {
            return response()->json([
                'success' => false,
                'message' => 'Profil étudiant non trouvé'
            ], 404);
        }
        
        $studentId = $user->student->id;

        $query = Lesson::with(['teacher.user', 'courseType', 'location', 'club'])
            ->where('student_id', $studentId);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $bookings = $query->orderBy('start_time', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $bookings
        ]);
    }

    /**
     * Crée une nouvelle réservation.
     */
    public function createBooking(Request $request)
    {
        $request->validate([
            'lesson_id' => 'required|exists:lessons,id',
            'notes' => 'nullable|string',
        ]);

        $user = $request->user();
        
        if (!$user->student) {
            return response()->json([
                'success' => false,
                'message' => 'Profil étudiant non trouvé'
            ], 404);
        }
        
        $studentId = $user->student->id;

        $lesson = Lesson::findOrFail($request->lesson_id);
        
        if ($lesson->status !== 'available') {
            return response()->json([
                'success' => false,
                'message' => 'Ce cours n\'est pas disponible.'
            ], 400);
        }

        // Vérifier si l'étudiant a un abonnement actif avec des crédits
        $subscriptionInstance = SubscriptionInstance::findActiveSubscriptionForLesson(
            $studentId,
            $lesson->course_type_id,
            $lesson->club_id
        );

        if ($subscriptionInstance) {
            // L'étudiant a un crédit, on procède à la réservation
            try {
                DB::beginTransaction();

                $lesson->update([
                    'student_id' => $studentId,
                    'status' => 'confirmed',
                    'notes' => $request->notes,
                ]);

                // Déduire le crédit
                $subscriptionInstance->consumeLesson($lesson);

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("Erreur lors de la réservation (consommation crédit): " . $e->getMessage());
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de la réservation: ' . $e->getMessage()
                ], 500);
            }
        } else {
            // Pas de crédit disponible -> Proposer le paiement
            return response()->json([
                'success' => false,
                'message' => 'Aucun crédit disponible pour ce cours. Veuillez payer la séance ou souscrire à un abonnement.',
                'code' => 'PAYMENT_REQUIRED',
                'lesson' => $lesson
            ], 402);
        }

        return response()->json([
            'success' => true,
            'data' => $lesson->load(['teacher.user', 'courseType', 'location', 'club']),
            'message' => 'Réservation créée avec succès'
        ], 201);
    }

    /**
     * Annule une réservation avec envoi d'emails au responsable du club et à l'enseignant.
     */
    public function cancelBooking(Request $request, string $id)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $user = $request->user();
        
        if (!$user->student) {
            return response()->json([
                'success' => false,
                'message' => 'Profil étudiant non trouvé'
            ], 404);
        }
        
        $studentId = $user->student->id;

        $lesson = Lesson::where('id', $id)
            ->where('student_id', $studentId)
            ->with(['teacher.user', 'club', 'courseType', 'location', 'student.user'])
            ->firstOrFail();

        // Vérifier que le cours n'est pas déjà annulé ou terminé
        if ($lesson->status === 'cancelled') {
            return response()->json([
                'success' => false,
                'message' => 'Ce cours est déjà annulé.'
            ], 400);
        }

        if ($lesson->status === 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'Impossible d\'annuler un cours déjà terminé.'
            ], 400);
        }

        // Vérifier que le cours est dans le futur
        if (Carbon::parse($lesson->start_time)->isPast()) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible d\'annuler un cours qui a déjà commencé.'
            ], 400);
        }

        $reason = $request->input('reason');

        // Mettre à jour le statut du cours
        $lesson->update([
            'status' => 'cancelled',
            'notes' => ($lesson->notes ? $lesson->notes . "\n\n" : '') . "[Annulé par l'élève] " . $reason
        ]);

        // Libérer l'abonnement si lié
        try {
            $subscriptionInstances = $lesson->subscriptionInstances;
            foreach ($subscriptionInstances as $instance) {
                $instance->recalculateLessonsUsed();
            }
        } catch (\Exception $e) {
            Log::warning("Erreur lors de la libération de l'abonnement: " . $e->getMessage());
        }

        // Envoyer les notifications
        try {
            // Envoyer à l'enseignant
            if ($lesson->teacher && $lesson->teacher->user) {
                $lesson->teacher->user->notify(
                    new LessonCancelledByStudentNotification($lesson, $reason, $user->student)
                );
            }

            // Envoyer aux responsables du club
            if ($lesson->club) {
                // Récupérer les responsables du club (owners, managers, admins)
                $clubManagers = \Illuminate\Support\Facades\DB::table('club_user')
                    ->where('club_id', $lesson->club->id)
                    ->where(function ($query) {
                        $query->whereIn('role', ['owner', 'manager', 'admin'])
                              ->orWhere('is_admin', true);
                    })
                    ->pluck('user_id');

                $managers = User::whereIn('id', $clubManagers)->get();

                foreach ($managers as $manager) {
                    $manager->notify(
                        new LessonCancelledByStudentNotification($lesson, $reason, $user->student)
                    );
                }
            }
        } catch (\Exception $e) {
            Log::error("Erreur lors de l'envoi des notifications d'annulation: " . $e->getMessage(), [
                'lesson_id' => $lesson->id,
                'student_id' => $studentId
            ]);
            // Ne pas faire échouer la requête si l'envoi d'email échoue
        }

        return response()->json([
            'success' => true,
            'message' => 'Réservation annulée avec succès. Les responsables du club et l\'enseignant ont été notifiés.'
        ]);
    }

    /**
     * Récupère les enseignants disponibles.
     */
    public function getAvailableTeachers(Request $request)
    {
        $query = Teacher::with('user')
            ->where('is_available', true);

        if ($request->has('subject')) {
            $query->whereHas('courseTypes', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->subject . '%');
            });
        }

        $teachers = $query->get();

        return response()->json($teachers);
    }

    /**
     * Récupère les cours d'un enseignant spécifique.
     */
    public function getTeacherLessons(Request $request, $id)
    {
        $lessons = Lesson::with(['courseType', 'location', 'club'])
            ->where('teacher_id', $id)
            ->where('status', 'available')
            ->where('start_time', '>=', Carbon::now())
            ->get();

        return response()->json($lessons);
    }

    /**
     * Recherche des cours.
     */
    public function searchLessons(Request $request)
    {
        $query = Lesson::with(['teacher.user', 'courseType', 'location', 'club'])
            ->where('status', 'available')
            ->where('start_time', '>=', Carbon::now());

        if ($request->has('q')) {
            $query->where(function ($q) use ($request) {
                $q->whereHas('courseType', function ($cq) use ($request) {
                    $cq->where('name', 'like', '%' . $request->q . '%');
                })
                ->orWhereHas('teacher.user', function ($tq) use ($request) {
                    $tq->where('name', 'like', '%' . $request->q . '%');
                });
            });
        }

        if ($request->has('subject')) {
            $query->whereHas('courseType', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->subject . '%');
            });
        }

        if ($request->has('start_date')) {
            $query->where('start_time', '>=', Carbon::parse($request->start_date));
        }

        if ($request->has('end_date')) {
            $query->where('start_time', '<=', Carbon::parse($request->end_date));
        }

        if ($request->has('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }

        $lessons = $query->get();

        return response()->json($lessons);
    }

    /**
     * Récupère l'historique des cours.
     */
    public function getLessonHistory(Request $request)
    {
        $user = $request->user();
        
        if (!$user->student) {
            return response()->json([
                'success' => false,
                'message' => 'Profil étudiant non trouvé'
            ], 404);
        }
        
        $studentId = $user->student->id;

        $lessons = Lesson::with(['teacher.user', 'courseType', 'location', 'club'])
            ->where('student_id', $studentId)
            ->where('status', 'completed')
            ->orderBy('start_time', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $lessons
        ]);
    }

    /**
     * Note un cours terminé.
     */
    public function rateLesson(Request $request, $id)
    {
        $request->validate([
            'rating' => 'required|integer|between:1,5',
            'review' => 'nullable|string',
        ]);

        $user = $request->user();
        
        if (!$user->student) {
            return response()->json([
                'success' => false,
                'message' => 'Profil étudiant non trouvé'
            ], 404);
        }
        
        $studentId = $user->student->id;

        $lesson = Lesson::where('id', $id)
            ->where('student_id', $studentId)
            ->where('status', 'completed')
            ->firstOrFail();

        $lesson->update([
            'rating' => $request->rating,
            'review' => $request->review,
        ]);

        return response()->json(['message' => 'Cours noté avec succès.']);
    }

    /**
     * Récupère les enseignants favoris.
     */
    public function getFavoriteTeachers(Request $request)
    {
        $user = $request->user();
        $studentId = $user->student->id;

        // Pour l'instant, retourner les enseignants avec qui l'étudiant a eu le plus de cours
        $teachers = Teacher::with('user')
            ->whereHas('lessons', function ($q) use ($studentId) {
                $q->where('student_id', $studentId)
                  ->where('status', 'completed');
            })
            ->get();

        return response()->json($teachers);
    }

    /**
     * Ajoute/Retire un enseignant des favoris.
     */
    public function toggleFavoriteTeacher(Request $request, $id)
    {
        // Pour l'instant, retourner un succès
        return response()->json(['message' => 'Enseignant ajouté aux favoris.']);
    }

    /**
     * Récupère tous les enseignants.
     */
    public function getTeachers(Request $request)
    {
        $teachers = Teacher::with('user')->get();
        return response()->json($teachers);
    }

    /**
     * Récupère les préférences de l'étudiant.
     */
    public function getPreferences(Request $request)
    {
        $user = $request->user();
        $student = $user->student;
        
        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Profil étudiant non trouvé'
            ], 404);
        }

        return response()->json([
            'preferred_disciplines' => $student->preferred_disciplines ?? [],
            'preferred_levels' => $student->preferred_levels ?? [],
            'preferred_formats' => $student->preferred_formats ?? [],
            'location' => $student->location ?? null,
            'max_price' => $student->max_price ?? null,
            'max_distance' => $student->max_distance ?? null,
            'notifications_enabled' => $student->notifications_enabled ?? true,
        ]);
    }

    /**
     * Sauvegarde les préférences de l'étudiant.
     */
    public function savePreferences(Request $request)
    {
        $request->validate([
            'preferred_disciplines' => 'nullable|array',
            'preferred_levels' => 'nullable|array',
            'preferred_formats' => 'nullable|array',
            'location' => 'nullable|string',
            'max_price' => 'nullable|numeric',
            'max_distance' => 'nullable|integer',
            'notifications_enabled' => 'nullable|boolean',
        ]);

        $user = $request->user();
        $student = $user->student;
        
        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Profil étudiant non trouvé'
            ], 404);
        }

        $student->update($request->only([
            'preferred_disciplines',
            'preferred_levels',
            'preferred_formats',
            'location',
            'max_price',
            'max_distance',
            'notifications_enabled',
        ]));

        return response()->json(['message' => 'Préférences sauvegardées avec succès.']);
    }
}
