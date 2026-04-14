<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\StudentLessonCalendarResource;
use App\Models\Lesson;
use App\Models\User;
use App\Models\Teacher;
use App\Models\Student;
use App\Models\CourseType;
use App\Models\Location;
use App\Models\Club;
use App\Models\SubscriptionInstance;
use App\Notifications\LessonCancellationConfirmationNotification;
use App\Notifications\LessonCancellationSubscriptionParticipantMismatchNotification;
use App\Notifications\LessonCancelledByStudentStakeholderNotification;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
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

        $householdIds = $user ? $user->getHouseholdStudentIds() : [];
        if (! $user || $householdIds === []) {
            return null;
        }

        $defaultStudentId = $user->student?->id ?? ($householdIds[0] ?? null);
        if ($defaultStudentId === null) {
            return null;
        }

        $activeStudentId = $request->input('active_student_id', $defaultStudentId);
        if ($activeStudentId === 'all' || $activeStudentId === null || $activeStudentId === '') {
            return $user->student ?? Student::with('user')->find($defaultStudentId);
        }

        $targetId = (int) $activeStudentId;
        if (! in_array($targetId, $householdIds, true)) {
            return $user->student ?? Student::with('user')->find($defaultStudentId);
        }

        return Student::with('user')->find($activeStudentId) ?? ($user->student ?? Student::with('user')->find($defaultStudentId));
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
        if (! $user) {
            return [];
        }

        $linkedIds = $user->getHouseholdStudentIds();
        if ($linkedIds === []) {
            return [];
        }

        // Priorité au paramètre de requête (choix frontend vue globale / un élève)
        $param = $request->query('active_student_id') ?? $request->input('active_student_id');

        if ($param === 'all' || $param === null || $param === '') {
            return $linkedIds;
        }

        $id = (int) $param;
        if (in_array($id, $linkedIds, true)) {
            return [$id];
        }

        $defaultSessionStudent = $user->student?->id ?? ($linkedIds[0] ?? null);
        $fromSession = session('active_student_id', $defaultSessionStudent);
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
        if (! $user) {
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

        $query = Lesson::with(['teacher.user', 'courseType', 'location', 'club', 'student.user', 'students.user'])
            ->forParticipantStudents($studentIds);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $bookings = $query->orderBy('start_time', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => StudentLessonCalendarResource::collection($bookings),
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
            ->forParticipantStudents([$studentId])
            ->with(['teacher.user', 'club', 'courseType', 'location', 'student.user', 'students.user', 'subscriptionInstances.students', 'subscriptionInstances.subscription.template'])
            ->firstOrFail();

        $cancellationDeadlineHours = $this->resolveCancellationDeadlineHours($lesson, $studentId);
        if ($cancellationDeadlineHours === null) {
            $cancellationDeadlineHours = 8;
        }

        if ($lesson->status === 'cancelled') {
            return response()->json(['success' => false, 'message' => 'Ce cours est déjà annulé.'], 400);
        }
        if ($lesson->status === 'completed') {
            return response()->json(['success' => false, 'message' => 'Impossible d\'annuler un cours déjà terminé.'], 400);
        }
        if (Carbon::parse($lesson->start_time)->isPast()) {
            return response()->json(['success' => false, 'message' => 'Impossible d\'annuler un cours qui a déjà commencé.'], 400);
        }

        $hoursUntilStart = Carbon::now()->diffInHours(Carbon::parse($lesson->start_time));
        $isLateCancel = $hoursUntilStart < $cancellationDeadlineHours;

        $rules = [];
        if ($isLateCancel) {
            $rules['cancellation_reason'] = 'required|in:medical,other';
            $rules['reason'] = 'nullable|string|max:500';
        } else {
            $rules['reason'] = 'nullable|string|max:500';
        }

        // Log inconditionnel pour diagnostic (multipart mal parsé = request_all vide)
        Log::info('cancelBooking request', [
            'method' => $request->method(),
            'content_type' => $request->header('Content-Type'),
            'request_all' => $request->all(),
            'has_file' => $request->hasFile('cancellation_certificate'),
            'POST_keys' => array_keys($_POST ?? []),
        ]);

        // Correctif : si multipart mal reçu (certificat présent mais raison absente), considérer médical
        if ($isLateCancel && $request->hasFile('cancellation_certificate') && !$request->filled('cancellation_reason')) {
            $request->merge(['cancellation_reason' => 'medical']);
        }

        $validated = $request->validate($rules);
        if ($isLateCancel && ($request->input('cancellation_reason') === 'medical') && !$request->hasFile('cancellation_certificate')) {
            return response()->json([
                'success' => false,
                'message' => "Pour une annulation pour raison médicale à moins de {$cancellationDeadlineHours} h du cours, un certificat médical (PDF ou photo) est obligatoire.",
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

        $certificateStatus = ($isLateCancel && $cancellationReason === 'medical' && $certificatePath) ? 'pending' : null;

        $cancellingStudentId = (int) $student->id;
        $linkedInstanceIds = DB::table('subscription_lessons')
            ->where('lesson_id', $lesson->id)
            ->pluck('subscription_instance_id');
        $mismatchSubscriptionInstances = SubscriptionInstance::query()
            ->whereIn('id', $linkedInstanceIds)
            ->with('students')
            ->get()
            ->filter(fn (SubscriptionInstance $instance) => ! $instance->students->pluck('id')->map(fn ($id) => (int) $id)->contains($cancellingStudentId))
            ->values();

        $updateData = [
            'status' => 'cancelled',
            'notes' => ($lesson->notes ? $lesson->notes . "\n\n" : '') . $notePart,
            'cancellation_reason' => $cancellationReason,
            'cancellation_certificate_path' => $certificatePath,
            'cancellation_count_in_subscription' => $countInSubscription,
            'cancellation_certificate_status' => $certificateStatus,
            'cancellation_certificate_reviewed_at' => null,
            'cancellation_certificate_reviewed_by' => null,
            'cancellation_certificate_rejection_reason' => null,
            'cancellation_certificate_resubmitted_at' => null,
            'cancellation_certificate_submitted_by_student_id' => $certificateStatus ? $studentId : null,
        ];
        $hasCancellationColumns = \Illuminate\Support\Facades\Schema::hasColumn('lessons', 'cancellation_count_in_subscription');
        if (!$hasCancellationColumns) {
            unset($updateData['cancellation_reason'], $updateData['cancellation_certificate_path'], $updateData['cancellation_count_in_subscription']);
        }
        $hasReviewColumns = \Illuminate\Support\Facades\Schema::hasColumn('lessons', 'cancellation_certificate_status');
        if (!$hasReviewColumns) {
            unset($updateData['cancellation_certificate_status'], $updateData['cancellation_certificate_reviewed_at'], $updateData['cancellation_certificate_reviewed_by'], $updateData['cancellation_certificate_rejection_reason'], $updateData['cancellation_certificate_resubmitted_at']);
        }
        if (!\Illuminate\Support\Facades\Schema::hasColumn('lessons', 'cancellation_certificate_submitted_by_student_id')) {
            unset($updateData['cancellation_certificate_submitted_by_student_id']);
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

        $lesson->refresh();
        $lesson->loadMissing(['courseType', 'club', 'student.user', 'students.user']);
        $this->notifyClubSubscriptionParticipantMismatch(
            $lesson,
            $student,
            $mismatchSubscriptionInstances
        );

        $reasonFreeText = trim((string) $reasonText);

        try {
            $stakeholderUsers = collect();
            if ($lesson->teacher && $lesson->teacher->user) {
                $stakeholderUsers->push($lesson->teacher->user);
            }
            if ($lesson->club) {
                $clubManagerIds = \Illuminate\Support\Facades\DB::table('club_user')
                    ->where('club_id', $lesson->club->id)
                    ->where(function ($query) {
                        $query->whereIn('role', ['owner', 'manager', 'admin'])->orWhere('is_admin', true);
                    })
                    ->pluck('user_id');
                $stakeholderUsers = $stakeholderUsers->merge(User::whereIn('id', $clubManagerIds)->get());
            }

            if ($stakeholderUsers->isNotEmpty()) {
                $lessonForMail = $lesson->fresh(['teacher.user', 'courseType', 'location', 'club']);
                Notification::send(
                    $stakeholderUsers->unique('id')->values(),
                    new LessonCancelledByStudentStakeholderNotification(
                        $lessonForMail,
                        $student,
                        $isLateCancel,
                        (bool) $certificatePath,
                        $countInSubscription,
                        $cancellationDeadlineHours,
                        $reasonFreeText,
                        $isLateCancel ? ($cancellationReason ?: null) : null,
                    )
                );
            }
        } catch (\Exception $e) {
            Log::error("Erreur envoi notifications annulation: " . $e->getMessage(), ['lesson_id' => $lesson->id]);
        }

        try {
            $studentUser = $student->user ?? $user;
            if ($studentUser) {
                $studentUser->notify(new LessonCancellationConfirmationNotification(
                    $lesson->fresh(),
                    $countInSubscription,
                    $certificateStatus,
                    $cancellationDeadlineHours,
                    $reasonFreeText
                ));
            }
        } catch (\Exception $e) {
            Log::error("Erreur envoi confirmation annulation à l'élève: " . $e->getMessage(), ['lesson_id' => $lesson->id]);
        }

        $message = 'Réservation annulée avec succès. Les responsables du club et l\'enseignant ont été notifiés.';
        if ($countInSubscription) {
            $message .= " Ce cours sera compté dans votre abonnement (annulation à moins de {$cancellationDeadlineHours} h sans certificat médical).";
        }
        return response()->json(['success' => true, 'message' => $message]);
    }

    /**
     * Résout le délai d'annulation en heures (template > défaut club > 8).
     */
    private function resolveCancellationDeadlineHours(Lesson $lesson, ?int $cancellingStudentId = null): ?int
    {
        $template = null;
        $instance = $lesson->subscriptionInstances->first();
        if ($instance && $instance->relationLoaded('subscription') && $instance->subscription) {
            $sub = $instance->subscription;
            if ($sub->relationLoaded('template') && $sub->template) {
                $template = $sub->template;
            } else {
                $template = $sub->template()->first();
            }
        }
        $subscriptionLookupStudentId = $cancellingStudentId ?? $lesson->student_id;
        if (!$template && $subscriptionLookupStudentId && $lesson->course_type_id && $lesson->club_id) {
            $activeInstance = SubscriptionInstance::findActiveSubscriptionForLesson(
                (int) $subscriptionLookupStudentId,
                (int) $lesson->course_type_id,
                (int) $lesson->club_id
            );
            if ($activeInstance && $activeInstance->subscription) {
                $template = $activeInstance->subscription->template ?? $activeInstance->subscription->template()->first();
            }
        }
        if ($template && $template->cancellation_deadline_hours !== null) {
            return (int) $template->cancellation_deadline_hours;
        }
        $club = $lesson->relationLoaded('club') ? $lesson->club : $lesson->club()->first();
        if ($club && \Illuminate\Support\Facades\Schema::hasColumn('clubs', 'default_cancellation_deadline_hours') && $club->default_cancellation_deadline_hours !== null) {
            return (int) $club->default_cancellation_deadline_hours;
        }
        return null;
    }

    /**
     * Alerte les responsables du club si l'élève qui annule participe au cours mais n'était pas rattaché
     * à l'instance d'abonnement liée (données incohérentes). Les instances sont capturées avant le détachement
     * déclenché par LessonObserver à l'annulation.
     *
     * @param  Collection<int, SubscriptionInstance>  $instancesMissingStudent
     */
    private function notifyClubSubscriptionParticipantMismatch(Lesson $lesson, Student $cancellingStudent, Collection $instancesMissingStudent): void
    {
        if ($instancesMissingStudent->isEmpty()) {
            return;
        }

        Log::warning('Annulation élève : participant absent des bénéficiaires de l\'instance d\'abonnement liée au cours', [
            'lesson_id' => $lesson->id,
            'cancelling_student_id' => $cancellingStudent->id,
            'subscription_instance_ids' => $instancesMissingStudent->pluck('id')->all(),
        ]);

        $club = $lesson->club ?? $lesson->club()->first();
        if (! $club) {
            return;
        }

        $stakeholders = $club->stakeholderUsersNotifiableByMail();

        if ($stakeholders->isEmpty()) {
            return;
        }

        try {
            Notification::send(
                $stakeholders->unique('id')->values(),
                new LessonCancellationSubscriptionParticipantMismatchNotification(
                    $lesson,
                    $cancellingStudent->loadMissing('user'),
                    $instancesMissingStudent
                )
            );
        } catch (\Exception $e) {
            Log::error('Erreur envoi e-mail incohérence abonnement / annulation: '.$e->getMessage(), [
                'lesson_id' => $lesson->id,
            ]);
        }
    }

    /**
     * Renvoi d'un certificat médical pour un cours annulé dont le certificat a été refusé par le club.
     */
    public function resubmitCancellationCertificate(Request $request, string $id)
    {
        $student = $this->getActiveStudent($request);
        if (!$student) {
            return response()->json(['success' => false, 'message' => 'Profil étudiant non trouvé'], 404);
        }

        $lesson = Lesson::where('id', $id)
            ->forParticipantStudents([$student->id])
            ->firstOrFail();

        if ($lesson->status !== 'cancelled') {
            return response()->json(['success' => false, 'message' => 'Ce cours n\'est pas annulé.'], 400);
        }
        if ($lesson->cancellation_reason !== 'medical') {
            return response()->json(['success' => false, 'message' => 'Ce cours n\'a pas été annulé pour raison médicale.'], 400);
        }
        if ($lesson->cancellation_certificate_status !== 'rejected') {
            return response()->json(['success' => false, 'message' => 'Le certificat de ce cours n\'a pas été refusé, a déjà été renvoyé ou la demande a été clôturée par le club.'], 400);
        }

        $request->validate([
            'cancellation_certificate' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $file = $request->file('cancellation_certificate');
        $ext = $file->getClientOriginalExtension() ?: 'pdf';
        $certificatePath = $file->storeAs(
            'cancellation_certificates',
            'lesson_' . $lesson->id . '_resubmit_' . Str::random(8) . '.' . $ext,
            'public'
        );

        $hasReviewColumns = \Illuminate\Support\Facades\Schema::hasColumn('lessons', 'cancellation_certificate_status');
        if (!$hasReviewColumns) {
            return response()->json(['success' => false, 'message' => 'Fonctionnalité non disponible.'], 500);
        }

        $oldPath = $lesson->cancellation_certificate_path;
        $lesson->cancellation_certificate_path = $certificatePath;
        $lesson->cancellation_certificate_status = 'pending';
        $lesson->cancellation_certificate_reviewed_at = null;
        $lesson->cancellation_certificate_reviewed_by = null;
        $lesson->cancellation_certificate_rejection_reason = null;
        $lesson->cancellation_certificate_resubmitted_at = now();
        if (\Illuminate\Support\Facades\Schema::hasColumn('lessons', 'cancellation_certificate_submitted_by_student_id')) {
            $lesson->cancellation_certificate_submitted_by_student_id = $student->id;
        }
        $lesson->saveQuietly();

        if ($oldPath && Storage::disk('public')->exists($oldPath)) {
            try {
                Storage::disk('public')->delete($oldPath);
            } catch (\Exception $e) {
                Log::warning("Impossible de supprimer l'ancien certificat: " . $e->getMessage(), ['path' => $oldPath]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Votre nouveau certificat a été envoyé. Le club le réexaminera sous peu.',
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
     * Récupère l'historique des cours (vue globale ou un seul élève).
     */
    public function getLessonHistory(Request $request)
    {
        $user = $request->user();
        if (! $user) {
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
        $lessons = Lesson::with(['teacher.user', 'courseType', 'location', 'club', 'student.user', 'students.user'])
            ->forParticipantStudents($studentIds)
            ->whereIn('status', ['completed', 'cancelled'])
            ->orderBy('start_time', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => StudentLessonCalendarResource::collection($lessons),
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
