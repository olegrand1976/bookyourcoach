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
            $oldStatus = $lesson->getOriginal('status');
            $newStatus = $lesson->status;
            
            // Si le cours passe en cancelled, détacher de l'abonnement et recalculer
            if ($newStatus === 'cancelled' && $oldStatus !== 'cancelled') {
                $this->handleLessonCancellation($lesson);
            } else {
                // Pour les autres changements de statut, juste recalculer
                $this->recalculateSubscriptionsForLesson($lesson);
            }
        }
    }
    
    /**
     * Gère l'annulation d'un cours : détache de l'abonnement sans décrémenter lessons_used.
     * Le total ne doit jamais diminuer (ni être inférieur au nombre de cours d'initialisation).
     */
    private function handleLessonCancellation(Lesson $lesson): void
    {
        $subscriptionInstances = SubscriptionInstance::whereHas('lessons', function ($query) use ($lesson) {
            $query->where('lesson_id', $lesson->id);
        })->get();

        foreach ($subscriptionInstances as $instance) {
            $instance->lessons()->detach($lesson->id);

            Log::info("🚫 Cours {$lesson->id} détaché de l'abonnement {$instance->id} (annulé, pas de décrémentation)", [
                'lesson_id' => $lesson->id,
                'subscription_instance_id' => $instance->id,
                'lessons_used' => $instance->lessons_used,
            ]);

            $instance->saveQuietly();
        }
    }

    /**
     * Handle the Lesson "deleted" event.
     * Détache le cours de l'abonnement sans décrémenter lessons_used.
     */
    public function deleted(Lesson $lesson): void
    {
        $subscriptionInstances = SubscriptionInstance::whereHas('lessons', function ($query) use ($lesson) {
            $query->where('lesson_id', $lesson->id);
        })->get();

        foreach ($subscriptionInstances as $instance) {
            $instance->lessons()->detach($lesson->id);

            Log::info("🗑️ Cours {$lesson->id} détaché de l'abonnement {$instance->id} (supprimé, pas de décrémentation)", [
                'lesson_id' => $lesson->id,
                'subscription_instance_id' => $instance->id,
                'lessons_used' => $instance->lessons_used,
            ]);

            $instance->saveQuietly();
        }
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

