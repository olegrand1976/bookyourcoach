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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DashboardController extends Controller
{
    /**
     * Récupère l'étudiant actif depuis le contexte de la requête.
     *
     * @param Request $request
     * @return Student|null
     */
    protected function getActiveStudent(Request $request)
    {
        $user = $request->user();
        
        if (!$user || !$user->student) {
            return null;
        }

        $activeStudentId = $request->input('active_student_id', $user->student->id);
        if ($activeStudentId === 'all' || $activeStudentId === null || $activeStudentId === '') {
            return $user->student;
        }

        $linkedStudents = $user->getLinkedStudents();
        $isLinked = $linkedStudents->contains('id', (int) $activeStudentId)
                 || $user->student->id === (int) $activeStudentId;

        if (!$isLinked) {
            return $user->student;
        }

        return Student::with('user')->find($activeStudentId) ?? $user->student;
    }

    /**
     * Retourne les IDs des élèves à prendre en compte (vue globale = tous les liés, sinon un seul).
     * Priorité : paramètre de requête active_student_id (pour le front), puis session.
     *
     * @param Request $request
     * @return array<int>
     */
    protected function getActiveStudentIds(Request $request): array
    {
        $user = $request->user();
        if (!$user || !$user->student) {
            return [];
        }

        $linkedIds = collect([$user->student->id])->merge($user->getLinkedStudents()->pluck('id'))->unique()->values()->all();
        // Priorité au paramètre de requête (choix frontend vue globale / un élève)
        $param = $request->query('active_student_id') ?? $request->input('active_student_id');

        if ($param === 'all' || $param === null || $param === '') {
            return $linkedIds;
        }

        $id = (int) $param;
        if (in_array($id, $linkedIds, true)) {
            return [$id];
        }

        $fromSession = session('active_student_id', $user->student->id);
        if (in_array((int) $fromSession, $linkedIds, true)) {
            return [(int) $fromSession];
        }

        return $linkedIds;
    }

    /**
     * Récupère les statistiques pour le tableau de bord de l'élève.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStats(Request $request)
    {
        $user = $request->user();

        // Récupérer l'étudiant actif depuis le contexte
        $student = $this->getActiveStudent($request);
        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Profil étudiant non trouvé.'
            ], 404);
        }

        $studentId = $student->id;

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
     * Récupère les réservations de l'étudiant (ou de tous les élèves liés si vue globale).
     */
    public function getBookings(Request $request)
    {
        $user = $request->user();
        if (!$user || !$user->student) {
            return response()->json([
                'success' => false,
                'message' => 'Profil étudiant non trouvé'
            ], 404);
        }

        $studentIds = $this->getActiveStudentIds($request);
        if (empty($studentIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Profil étudiant non trouvé'
            ], 404);
        }

        $query = Lesson::with(['teacher.user', 'courseType', 'location', 'club', 'student.user'])
            ->whereIn('student_id', $studentIds);

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
        
        // Récupérer l'étudiant actif depuis le contexte
        $student = $this->getActiveStudent($request);
        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Profil étudiant non trouvé'
            ], 404);
        }
        
        $studentId = $student->id;

        $lesson = Lesson::findOrFail($request->lesson_id);
        
        if ($lesson->status !== 'available') {
            return response()->json([
                'success' => false,
                'message' => 'Ce cours n\'est pas disponible.'
            ], 400);
        }

        $lesson->update([
            'student_id' => $studentId,
            'status' => 'confirmed',
            'notes' => $request->notes,
        ]);

        return response()->json([
            'success' => true,
            'data' => $lesson->load(['teacher.user', 'courseType', 'location', 'club']),
            'message' => 'Réservation créée avec succès'
        ], 201);
    }

    /**
     * Annule une réservation avec envoi d'emails au responsable du club et à l'enseignant.
     * - Annulation >= 8h avant le cours : annulation simple, le cours n'est pas compté dans l'abonnement.
     * - Annulation < 8h avant : raison obligatoire (médical / autre). Si médical, certificat PDF/photo obligatoire.
     *   Avec certificat : le cours n'est pas compté. Sans certificat (médical ou autre) : le cours est compté dans l'abonnement.
     */
    public function cancelBooking(Request $request, string $id)
    {
        $user = $request->user();
        $student = $this->getActiveStudent($request);
        if (!$student) {
            return response()->json(['success' => false, 'message' => 'Profil étudiant non trouvé'], 404);
        }
        $studentId = $student->id;

        $lesson = Lesson::where('id', $id)
            ->where('student_id', $studentId)
            ->with(['teacher.user', 'club', 'courseType', 'location', 'student.user'])
            ->firstOrFail();

        if ($lesson->status === 'cancelled') {
            return response()->json(['success' => false, 'message' => 'Ce cours est déjà annulé.'], 400);
        }
        if ($lesson->status === 'completed') {
            return response()->json(['success' => false, 'message' => 'Impossible d\'annuler un cours déjà terminé.'], 400);
        }
        if (Carbon::parse($lesson->start_time)->isPast()) {
            return response()->json(['success' => false, 'message' => 'Impossible d\'annuler un cours qui a déjà commencé.'], 400);
        }

        $hoursUntilStart = Carbon::parse($lesson->start_time)->diffInHours(Carbon::now(), false);
        $isLateCancel = $hoursUntilStart < 8;

        $rules = [];
        if ($isLateCancel) {
            $rules['cancellation_reason'] = 'required|in:medical,other';
            $rules['reason'] = 'nullable|string|max:500';
        } else {
            $rules['reason'] = 'nullable|string|max:500';
        }
        $validated = $request->validate($rules);
        if ($isLateCancel && ($request->input('cancellation_reason') === 'medical') && !$request->hasFile('cancellation_certificate')) {
            return response()->json([
                'success' => false,
                'message' => 'Pour une annulation pour raison médicale à moins de 8 h du cours, un certificat médical (PDF ou photo) est obligatoire.',
                'errors' => ['cancellation_certificate' => ['Le certificat médical est obligatoire.']]
            ], 422);
        }

        $reasonText = $request->input('reason', '');
        $cancellationReason = $request->input('cancellation_reason');
        $certificatePath = null;

        if ($isLateCancel && $cancellationReason === 'medical' && $request->hasFile('cancellation_certificate')) {
            $file = $request->file('cancellation_certificate');
            $ext = $file->getClientOriginalExtension() ?: 'pdf';
            $certificatePath = $file->storeAs(
                'cancellation_certificates',
                'lesson_' . $lesson->id . '_' . Str::random(8) . '.' . $ext,
                'public'
            );
        }

        $countInSubscription = false;
        if ($isLateCancel) {
            $countInSubscription = !($cancellationReason === 'medical' && $certificatePath !== null);
        }

        $notePart = "[Annulé par l'élève]";
        if ($cancellationReason) {
            $notePart .= " Raison: " . ($cancellationReason === 'medical' ? 'médicale' : 'autre');
        }
        if ($reasonText) {
            $notePart .= " " . $reasonText;
        }

        $updateData = [
            'status' => 'cancelled',
            'notes' => ($lesson->notes ? $lesson->notes . "\n\n" : '') . $notePart,
            'cancellation_reason' => $cancellationReason,
            'cancellation_certificate_path' => $certificatePath,
            'cancellation_count_in_subscription' => $countInSubscription,
        ];
        $hasCancellationColumns = \Illuminate\Support\Facades\Schema::hasColumn('lessons', 'cancellation_count_in_subscription');
        if (!$hasCancellationColumns) {
            unset($updateData['cancellation_reason'], $updateData['cancellation_certificate_path'], $updateData['cancellation_count_in_subscription']);
        }
        $lesson->update($updateData);

        $shouldReleaseSubscription = $hasCancellationColumns ? !$countInSubscription : true;
        if ($shouldReleaseSubscription) {
            try {
                foreach ($lesson->subscriptionInstances as $instance) {
                    $instance->recalculateLessonsUsed();
                }
            } catch (\Exception $e) {
                Log::warning("Erreur lors de la libération de l'abonnement: " . $e->getMessage());
            }
        }

        $reasonForNotification = $reasonText ?: ($cancellationReason === 'medical' ? 'Raison médicale' : 'Autre raison');
        try {
            if ($lesson->teacher && $lesson->teacher->user) {
                $lesson->teacher->user->notify(
                    new LessonCancelledByStudentNotification($lesson, $reasonForNotification, $user->student)
                );
            }
            if ($lesson->club) {
                $clubManagers = \Illuminate\Support\Facades\DB::table('club_user')
                    ->where('club_id', $lesson->club->id)
                    ->where(function ($query) {
                        $query->whereIn('role', ['owner', 'manager', 'admin'])->orWhere('is_admin', true);
                    })
                    ->pluck('user_id');
                foreach (User::whereIn('id', $clubManagers)->get() as $manager) {
                    $manager->notify(new LessonCancelledByStudentNotification($lesson, $reasonForNotification, $user->student));
                }
            }
        } catch (\Exception $e) {
            Log::error("Erreur envoi notifications annulation: " . $e->getMessage(), ['lesson_id' => $lesson->id]);
        }

        $message = 'Réservation annulée avec succès. Les responsables du club et l\'enseignant ont été notifiés.';
        if ($countInSubscription) {
            $message .= ' Ce cours sera compté dans votre abonnement (annulation à moins de 8 h sans certificat médical).';
        }
        return response()->json(['success' => true, 'message' => $message]);
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
     * Récupère l'historique des cours (vue globale ou un seul élève).
     */
    public function getLessonHistory(Request $request)
    {
        $user = $request->user();
        if (!$user || !$user->student) {
            return response()->json([
                'success' => false,
                'message' => 'Profil étudiant non trouvé'
            ], 404);
        }

        $studentIds = $this->getActiveStudentIds($request);
        if (empty($studentIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Profil étudiant non trouvé'
            ], 404);
        }

        // Historique : cours terminés et annulés
        $lessons = Lesson::with(['teacher.user', 'courseType', 'location', 'club', 'student.user'])
            ->whereIn('student_id', $studentIds)
            ->whereIn('status', ['completed', 'cancelled'])
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
        
        // Récupérer l'étudiant actif depuis le contexte
        $student = $this->getActiveStudent($request);
        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Profil étudiant non trouvé'
            ], 404);
        }
        
        $studentId = $student->id;

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
        
        // Récupérer l'étudiant actif depuis le contexte
        $student = $this->getActiveStudent($request);
        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Profil étudiant non trouvé'
            ], 404);
        }
        
        $studentId = $student->id;

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
