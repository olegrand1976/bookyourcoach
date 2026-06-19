<?php

namespace App\Observers;

use App\Models\Lesson;
use App\Models\SubscriptionInstance;
use Illuminate\Support\Facades\Log;

class LessonObserver
{
    /**
     * Handle the Lesson "created" event.
     * Si un cours est créé et qu'il est déjà lié à un abonnement, recalculer
     */
    public function created(Lesson $lesson): void
    {
        dispatch(function () use ($lesson) {
            $this->recalculateSubscriptionsForLesson($lesson);
        })->afterResponse();
    }

    /**
     * Handle the Lesson "updated" event.
     * Recalcule lessons_used pour tous les abonnements liés si le statut change
     */
    public function updated(Lesson $lesson): void
    {
        if ($lesson->isDirty('status')) {
            $oldStatus = $lesson->getOriginal('status');
            $newStatus = $lesson->status;

            if ($newStatus === 'cancelled' && $oldStatus !== 'cancelled') {
                $this->handleLessonCancellation($lesson);
            } elseif ($oldStatus === 'cancelled' && $newStatus !== 'cancelled') {
                $this->handleLessonReactivation($lesson);
            } else {
                $this->recalculateSubscriptionsForLesson($lesson);
            }

            return;
        }

        if (
            $lesson->status === 'cancelled'
            && $lesson->isDirty('cancellation_count_in_subscription')
        ) {
            $this->syncCancelledLessonSubscriptionLink($lesson);
        }
    }

    /**
     * Gère l'annulation d'un cours selon cancellation_count_in_subscription.
     */
    private function handleLessonCancellation(Lesson $lesson): void
    {
        $subscriptionInstances = SubscriptionInstance::whereHas('lessons', function ($query) use ($lesson) {
            $query->where('lesson_id', $lesson->id);
        })->get();

        if (empty($lesson->cancelled_subscription_instance_ids) && $subscriptionInstances->isNotEmpty()) {
            $lesson->cancelled_subscription_instance_ids = $subscriptionInstances
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all();
            $lesson->saveQuietly();
        }

        $this->syncCancelledLessonSubscriptionLink($lesson, $subscriptionInstances);
    }

    /**
     * Ré-attache le cours aux abonnements mémorisés lors d'une réactivation (cancelled → autre statut).
     */
    private function handleLessonReactivation(Lesson $lesson): void
    {
        $instanceIds = collect($lesson->cancelled_subscription_instance_ids ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($instanceIds === []) {
            $this->recalculateSubscriptionsForLesson($lesson);

            return;
        }

        foreach (SubscriptionInstance::whereIn('id', $instanceIds)->get() as $instance) {
            if ($instance->lessons()->where('lesson_id', $lesson->id)->exists()) {
                $instance->recalculateLessonsUsed();
                continue;
            }

            try {
                $instance->consumeLesson($lesson);
            } catch (\Exception $e) {
                Log::warning("Impossible de ré-attacher le cours {$lesson->id} à l'abonnement {$instance->id}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $lesson->cancelled_subscription_instance_ids = null;
        $lesson->saveQuietly();
    }

    /**
     * Conserve ou libère le pivot selon cancellation_count_in_subscription, puis recalcule.
     */
    private function syncCancelledLessonSubscriptionLink(Lesson $lesson, $subscriptionInstances = null): void
    {
        $countInSubscription = (bool) $lesson->cancellation_count_in_subscription;

        if ($subscriptionInstances === null) {
            $instanceIds = collect($lesson->cancelled_subscription_instance_ids ?? [])
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->unique()
                ->values()
                ->all();

            $subscriptionInstances = $instanceIds !== []
                ? SubscriptionInstance::whereIn('id', $instanceIds)->get()
                : SubscriptionInstance::whereHas('lessons', function ($query) use ($lesson) {
                    $query->where('lesson_id', $lesson->id);
                })->get();
        }

        foreach ($subscriptionInstances as $instance) {
            $isAttached = $instance->lessons()->where('lesson_id', $lesson->id)->exists();

            if ($countInSubscription) {
                if (! $isAttached) {
                    $instance->lessons()->attach($lesson->id);
                }

                Log::info("🚫 Cours {$lesson->id} conservé sur l'abonnement {$instance->id} (annulation comptée)", [
                    'lesson_id' => $lesson->id,
                    'subscription_instance_id' => $instance->id,
                ]);
            } else {
                if ($isAttached) {
                    $instance->lessons()->detach($lesson->id);
                }

                Log::info("🚫 Cours {$lesson->id} détaché de l'abonnement {$instance->id} (annulation libérée)", [
                    'lesson_id' => $lesson->id,
                    'subscription_instance_id' => $instance->id,
                ]);
            }

            $instance->recalculateLessonsUsed();
        }
    }

    /**
     * Handle the Lesson "deleted" event.
     */
    public function deleted(Lesson $lesson): void
    {
        $instanceIds = collect($lesson->cancelled_subscription_instance_ids ?? [])
            ->merge(
                SubscriptionInstance::whereHas('lessons', function ($query) use ($lesson) {
                    $query->where('lesson_id', $lesson->id);
                })->pluck('id')
            )
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->filter()
            ->values()
            ->all();

        foreach (SubscriptionInstance::whereIn('id', $instanceIds)->get() as $instance) {
            $instance->lessons()->detach($lesson->id);
            $instance->recalculateLessonsUsed();

            Log::info("🗑️ Cours {$lesson->id} détaché de l'abonnement {$instance->id} (supprimé)", [
                'lesson_id' => $lesson->id,
                'subscription_instance_id' => $instance->id,
            ]);
        }
    }

    /**
     * Recalcule lessons_used pour tous les abonnements liés à ce cours
     */
    private function recalculateSubscriptionsForLesson(Lesson $lesson): void
    {
        $subscriptionInstances = SubscriptionInstance::whereHas('lessons', function ($query) use ($lesson) {
            $query->where('lesson_id', $lesson->id);
        })->get();

        foreach ($subscriptionInstances as $instance) {
            $instance->recalculateLessonsUsed();
        }
    }
}
