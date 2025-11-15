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
     * Gère l'annulation d'un cours : détache de l'abonnement et décrémente lessons_used
     */
    private function handleLessonCancellation(Lesson $lesson): void
    {
        // Récupérer toutes les instances d'abonnements liées à ce cours
        $subscriptionInstances = SubscriptionInstance::whereHas('lessons', function ($query) use ($lesson) {
            $query->where('lesson_id', $lesson->id);
        })->get();

        foreach ($subscriptionInstances as $instance) {
            $oldLessonsUsed = $instance->lessons_used;
            $oldStatus = $instance->status;
            
            // Détacher le cours annulé de l'abonnement
            $instance->lessons()->detach($lesson->id);
            
            // ⚠️ LOGIQUE CRITIQUE : Décrémenter directement lessons_used au lieu de recalculer
            // Cela préserve la valeur manuelle initiale
            // Exemple : 6 (5 manuel + 1 cours) - 1 cours annulé = 5 (valeur manuelle préservée)
            if ($instance->lessons_used > 0) {
                $instance->lessons_used = $instance->lessons_used - 1;
            }
            
            Log::info("🚫 Cours {$lesson->id} détaché de l'abonnement {$instance->id} (annulé, décrémentation)", [
                'lesson_id' => $lesson->id,
                'subscription_instance_id' => $instance->id,
                'old_lessons_used' => $oldLessonsUsed,
                'new_lessons_used' => $instance->lessons_used,
                'calculation' => "{$oldLessonsUsed} - 1 = {$instance->lessons_used}",
                'old_status' => $oldStatus,
                'note' => 'Décrémentation directe pour préserver la valeur manuelle'
            ]);
            
            // Sauvegarder la décrémentation
            $instance->saveQuietly();
            
            // Si l'abonnement était completed et qu'il redevient disponible, le réouvrir
            if ($oldStatus === 'completed' && $instance->lessons_used < $instance->subscription->total_available_lessons) {
                $instance->status = 'active';
                $instance->saveQuietly();
                
                Log::info("🔄 Abonnement {$instance->id} réouvert après annulation de cours", [
                    'subscription_instance_id' => $instance->id,
                    'lessons_used' => $instance->lessons_used,
                    'total_available' => $instance->subscription->total_available_lessons,
                    'cancelled_lesson_id' => $lesson->id
                ]);
            }
        }
    }

    /**
     * Handle the Lesson "deleted" event.
     * Si un cours est supprimé, le détacher de l'abonnement et décrémenter lessons_used
     */
    public function deleted(Lesson $lesson): void
    {
        // Récupérer toutes les instances d'abonnements liées à ce cours
        $subscriptionInstances = SubscriptionInstance::whereHas('lessons', function ($query) use ($lesson) {
            $query->where('lesson_id', $lesson->id);
        })->get();

        foreach ($subscriptionInstances as $instance) {
            $oldLessonsUsed = $instance->lessons_used;
            $oldStatus = $instance->status;
            
            // Détacher le cours supprimé de l'abonnement
            $instance->lessons()->detach($lesson->id);
            
            // ⚠️ LOGIQUE CRITIQUE : Décrémenter directement lessons_used au lieu de recalculer
            // Cela préserve la valeur manuelle initiale
            if ($instance->lessons_used > 0) {
                $instance->lessons_used = $instance->lessons_used - 1;
            }
            
            Log::info("🗑️ Cours {$lesson->id} détaché de l'abonnement {$instance->id} (supprimé, décrémentation)", [
                'lesson_id' => $lesson->id,
                'subscription_instance_id' => $instance->id,
                'old_lessons_used' => $oldLessonsUsed,
                'new_lessons_used' => $instance->lessons_used,
                'calculation' => "{$oldLessonsUsed} - 1 = {$instance->lessons_used}",
                'old_status' => $oldStatus,
                'note' => 'Décrémentation directe pour préserver la valeur manuelle'
            ]);
            
            // Sauvegarder la décrémentation
            $instance->saveQuietly();
            
            // Si l'abonnement était completed et qu'il redevient disponible, le réouvrir
            if ($oldStatus === 'completed' && $instance->lessons_used < $instance->subscription->total_available_lessons) {
                $instance->status = 'active';
                $instance->saveQuietly();
                
                Log::info("🔄 Abonnement {$instance->id} réouvert après suppression de cours", [
                    'subscription_instance_id' => $instance->id,
                    'lessons_used' => $instance->lessons_used,
                    'total_available' => $instance->subscription->total_available_lessons,
                    'deleted_lesson_id' => $lesson->id
                ]);
            }
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

