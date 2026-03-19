<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Notifications\CertificateAcceptedNotification;
use App\Notifications\CertificateRejectedNotification;
use App\Notifications\CertificateRequestClosedNotification;
use App\Services\CancellationCertificateReviewService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClubCancellationCertificateController extends Controller
{
    public function __construct(
        private CancellationCertificateReviewService $reviewService
    ) {}

    /**
     * Vérifie que la leçon appartient au club de l'utilisateur et que l'utilisateur peut valider (owner, manager, admin).
     */
    private function authorizeLessonForReview(Request $request, Lesson $lesson): void
    {
        $user = $request->user();
        $clubId = $lesson->club_id;

        $pivot = DB::table('club_user')
            ->where('user_id', $user->id)
            ->where('club_id', $clubId)
            ->first();

        if (!$pivot) {
            throw ValidationException::withMessages(['lesson' => ['Ce cours n\'appartient pas à votre club.']]);
        }

        $allowedRoles = ['owner', 'manager', 'admin'];
        $hasRole = in_array($pivot->role ?? '', $allowedRoles) || !empty($pivot->is_admin);
        if (!$hasRole) {
            throw ValidationException::withMessages(['lesson' => ['Vous n\'êtes pas autorisé à valider les certificats médicaux.']]);
        }
    }

    /**
     * POST /club/lessons/{id}/cancellation-certificate/accept
     */
    public function accept(Request $request, string $id)
    {
        $lesson = Lesson::with(['student.user', 'courseType', 'location'])->findOrFail($id);

        if ($lesson->status !== 'cancelled') {
            return response()->json(['success' => false, 'message' => 'Ce cours n\'est pas annulé.'], 400);
        }
        if ($lesson->cancellation_reason !== 'medical') {
            return response()->json(['success' => false, 'message' => 'Ce cours n\'a pas été annulé pour raison médicale.'], 400);
        }
        if (empty($lesson->cancellation_certificate_path)) {
            return response()->json(['success' => false, 'message' => 'Aucun certificat joint à ce cours.'], 400);
        }
        if ($lesson->cancellation_certificate_status === 'accepted') {
            return response()->json(['success' => false, 'message' => 'Ce certificat a déjà été accepté.'], 400);
        }

        $this->authorizeLessonForReview($request, $lesson);

        $this->reviewService->accept($lesson, $request->user());

        try {
            if ($lesson->student?->user) {
                $lesson->student->user->notify(new CertificateAcceptedNotification($lesson->fresh()));
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Erreur envoi notification acceptation certificat: ' . $e->getMessage(), ['lesson_id' => $lesson->id]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Certificat accepté. Le cours ne sera pas décompté de l\'abonnement de l\'élève.',
        ]);
    }

    /**
     * POST /club/lessons/{id}/cancellation-certificate/reject
     */
    public function reject(Request $request, string $id)
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        $lesson = Lesson::with(['student.user', 'courseType', 'location'])->findOrFail($id);

        if ($lesson->status !== 'cancelled') {
            return response()->json(['success' => false, 'message' => 'Ce cours n\'est pas annulé.'], 400);
        }
        if ($lesson->cancellation_reason !== 'medical') {
            return response()->json(['success' => false, 'message' => 'Ce cours n\'a pas été annulé pour raison médicale.'], 400);
        }
        if (empty($lesson->cancellation_certificate_path)) {
            return response()->json(['success' => false, 'message' => 'Aucun certificat joint à ce cours.'], 400);
        }
        if ($lesson->cancellation_certificate_status === 'rejected') {
            return response()->json(['success' => false, 'message' => 'Ce certificat a déjà été refusé. L\'élève peut renvoyer un nouveau certificat.'], 400);
        }

        $this->authorizeLessonForReview($request, $lesson);

        $this->reviewService->reject($lesson, $request->user(), $request->input('rejection_reason'));

        try {
            if ($lesson->student?->user) {
                $lesson->student->user->notify(new CertificateRejectedNotification(
                    $lesson->fresh(),
                    $request->input('rejection_reason')
                ));
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Erreur envoi notification refus certificat: ' . $e->getMessage(), ['lesson_id' => $lesson->id]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Certificat refusé. Le cours sera compté dans l\'abonnement de l\'élève.',
        ]);
    }

    /**
     * GET /club/lessons/{id}/cancellation-certificate/download
     */
    public function download(Request $request, string $id): StreamedResponse|\Illuminate\Http\JsonResponse
    {
        $lesson = Lesson::findOrFail($id);

        if (empty($lesson->cancellation_certificate_path)) {
            return response()->json(['success' => false, 'message' => 'Aucun certificat pour ce cours.'], 404);
        }

        $this->authorizeLessonForReview($request, $lesson);

        $path = ltrim($lesson->cancellation_certificate_path, '/\\');
        if (!Storage::disk('public')->exists($path)) {
            $fullPath = Storage::disk('public')->path($path);
            Log::warning('Certificat d\'annulation introuvable sur le disque.', [
                'lesson_id' => $lesson->id,
                'stored_path' => $lesson->cancellation_certificate_path,
                'resolved_path' => $fullPath,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Fichier introuvable. Le certificat n\'est peut-être plus sur ce serveur (environnement ou stockage différent).',
            ], 404);
        }

        $filename = 'certificat_lesson_' . $lesson->id . '_' . basename($path);
        return Storage::disk('public')->download($path, $filename);
    }

    /**
     * POST /club/lessons/{id}/cancellation-certificate/close
     * Clôture la demande après plusieurs aller-retour ; l'élève ne peut plus renvoyer de certificat.
     */
    public function close(Request $request, string $id)
    {
        $request->validate([
            'close_reason' => 'nullable|string|max:500',
        ]);

        $lesson = Lesson::with(['student.user', 'courseType', 'location'])->findOrFail($id);

        if ($lesson->status !== 'cancelled') {
            return response()->json(['success' => false, 'message' => 'Ce cours n\'est pas annulé.'], 400);
        }
        if ($lesson->cancellation_reason !== 'medical') {
            return response()->json(['success' => false, 'message' => 'Ce cours n\'a pas été annulé pour raison médicale.'], 400);
        }
        if (empty($lesson->cancellation_certificate_path)) {
            return response()->json(['success' => false, 'message' => 'Aucun certificat joint à ce cours.'], 400);
        }
        if ($lesson->cancellation_certificate_status === 'accepted') {
            return response()->json(['success' => false, 'message' => 'Ce certificat a déjà été accepté.'], 400);
        }
        if ($lesson->cancellation_certificate_status === 'closed') {
            return response()->json(['success' => false, 'message' => 'Cette demande est déjà clôturée.'], 400);
        }

        $this->authorizeLessonForReview($request, $lesson);

        $closeReason = $request->input('close_reason');
        $this->reviewService->close($lesson, $request->user(), $closeReason);

        try {
            if ($lesson->student?->user) {
                $lesson->student->user->notify(new CertificateRequestClosedNotification(
                    $lesson->fresh(),
                    $closeReason
                ));
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Erreur envoi notification clôture certificat: ' . $e->getMessage(), ['lesson_id' => $lesson->id]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Demande clôturée. L\'élève a été notifié et ne pourra plus renvoyer de certificat pour ce cours.',
        ]);
    }

    /**
     * GET /club/lessons/pending-certificates
     * Liste des cours annulés avec certificat médical en attente de validation (pour le bloc planning).
     * Même critère de périmètre que la liste des cours du club : enseignant appartenant au club
     * (lesson.club_id peut être null sur d'anciennes données).
     */
    public function pendingCertificates(Request $request)
    {
        $user = $request->user();
        $club = $user->getFirstClub();
        if (!$club) {
            return response()->json(['success' => true, 'data' => []]);
        }

        $hasColumn = \Illuminate\Support\Facades\Schema::hasColumn('lessons', 'cancellation_certificate_status');
        if (!$hasColumn) {
            return response()->json(['success' => true, 'data' => []]);
        }

        $lessons = Lesson::where('status', 'cancelled')
            ->where('cancellation_certificate_status', 'pending')
            ->where(function ($q) use ($club) {
                $q->where('club_id', $club->id)
                    ->orWhereHas('teacher', function ($teacherQuery) use ($club) {
                        $teacherQuery->whereHas('clubs', function ($clubQuery) use ($club) {
                            $clubQuery->where('clubs.id', $club->id);
                        });
                    });
            })
            ->with(['student.user', 'cancellationCertificateSubmittedByStudent.user', 'courseType', 'teacher.user'])
            ->orderBy('start_time', 'desc')
            ->get();

        $payload = [
            'success' => true,
            'data' => $lessons,
        ];

        if (app()->environment('local')) {
            $payload['_debug'] = [
                'has_column' => $hasColumn,
                'club_id' => $club->id,
                'total_cancelled' => Lesson::where('status', 'cancelled')->count(),
                'total_pending_cert' => Lesson::where('status', 'cancelled')->where('cancellation_certificate_status', 'pending')->count(),
                'with_club_id' => Lesson::where('status', 'cancelled')->where('cancellation_certificate_status', 'pending')->where('club_id', $club->id)->count(),
                'with_teacher_in_club' => Lesson::where('status', 'cancelled')->where('cancellation_certificate_status', 'pending')->whereHas('teacher', fn ($q) => $q->whereHas('clubs', fn ($cq) => $cq->where('clubs.id', $club->id)))->count(),
                'returned' => $lessons->count(),
            ];
        }

        return response()->json($payload);
    }
}
