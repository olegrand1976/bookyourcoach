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
        // Attendre un peu pour que la liaison dans subscription_lessons soit faite
        // (consumeLesson est appelé juste après la création dans LessonController)
        // Utiliser un délai court pour laisser le temps à la transaction de se terminer
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
        // Si le statut a changé (surtout si annulé), recalculer les abonnements
        if ($lesson->isDirty('status')) {
            $this->recalculateSubscriptionsForLesson($lesson);
        }
    }

    /**
     * Handle the Lesson "deleted" event.
     * Si un cours est supprimé, recalculer les abonnements
     */
    public function deleted(Lesson $lesson): void
    {
        $this->recalculateSubscriptionsForLesson($lesson);
    }

    /**
     * Recalcule lessons_used pour tous les abonnements liés à ce cours
     */
    private function recalculateSubscriptionsForLesson(Lesson $lesson): void
    {
        // Récupérer toutes les instances d'abonnements liées à ce cours
        $subscriptionInstances = SubscriptionInstance::whereHas('lessons', function ($query) use ($lesson) {
            $query->where('lesson_id', $lesson->id);
        })->get();

        foreach ($subscriptionInstances as $instance) {
            $instance->recalculateLessonsUsed();
        }
    }
}

