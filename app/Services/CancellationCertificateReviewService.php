<?php

namespace App\Services;

use App\Models\Lesson;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CancellationCertificateReviewService
{
    /**
     * Accepte le certificat médical : cours non compté dans l'abonnement, recalcul des instances.
     */
    public function accept(Lesson $lesson, User $reviewer): void
    {
        $this->applyReview($lesson, $reviewer, 'accepted', null);
    }

    /**
     * Refuse le certificat médical : cours compté dans l'abonnement, recalcul des instances.
     */
    public function reject(Lesson $lesson, User $reviewer, string $rejectionReason): void
    {
        $this->applyReview($lesson, $reviewer, 'rejected', $rejectionReason);
    }

    /**
     * Clôture la demande de certificat (après plusieurs aller-retour non concluants).
     * Le cours reste compté dans l'abonnement, l'élève ne peut plus renvoyer de certificat.
     */
    public function close(Lesson $lesson, User $reviewer, ?string $closeReason = null): void
    {
        DB::transaction(function () use ($lesson, $reviewer, $closeReason) {
            $lesson->cancellation_certificate_status = 'closed';
            $lesson->cancellation_certificate_reviewed_at = now();
            $lesson->cancellation_certificate_reviewed_by = $reviewer->id;
            $lesson->cancellation_certificate_rejection_reason = $closeReason;
            $lesson->cancellation_count_in_subscription = true;
            $lesson->saveQuietly();

            foreach ($lesson->subscriptionInstances as $instance) {
                $instance->recalculateLessonsUsed();
            }
        });
    }

    private function applyReview(Lesson $lesson, User $reviewer, string $status, ?string $rejectionReason): void
    {
        $countInSubscription = in_array($status, ['rejected', 'closed'], true);
        DB::transaction(function () use ($lesson, $reviewer, $status, $rejectionReason, $countInSubscription) {
            $lesson->cancellation_certificate_status = $status;
            $lesson->cancellation_certificate_reviewed_at = now();
            $lesson->cancellation_certificate_reviewed_by = $reviewer->id;
            $lesson->cancellation_certificate_rejection_reason = $rejectionReason;
            $lesson->cancellation_count_in_subscription = $countInSubscription;
            $lesson->saveQuietly();

            foreach ($lesson->subscriptionInstances as $instance) {
                $instance->recalculateLessonsUsed();
            }
        });
    }
}
