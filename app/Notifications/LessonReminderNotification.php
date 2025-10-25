<?php

namespace App\Notifications;

use App\Models\Lesson;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LessonReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Lesson $lesson
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $timeUntil = $this->lesson->start_time->diffForHumans();

        // Obtenir le prénom de manière sécurisée
        $firstName = $notifiable->profile?->first_name ?? $notifiable->name ?? 'Cher utilisateur';
        
        // Obtenir le nom de l'enseignant de manière sécurisée
        $teacherName = 'Non spécifié';
        if ($this->lesson->teacher && $this->lesson->teacher->user) {
            if ($this->lesson->teacher->user->profile) {
                $teacherName = $this->lesson->teacher->user->profile->first_name . ' ' . 
                              $this->lesson->teacher->user->profile->last_name;
            } else {
                $teacherName = $this->lesson->teacher->user->name;
            }
        }

        // Obtenir le lieu de manière sécurisée
        $locationName = $this->lesson->location?->name ?? 'À définir';

        return (new MailMessage)
            ->subject('Rappel de cours - activibe')
            ->greeting("Bonjour {$firstName},")
            ->line("Votre cours approche ! ({$timeUntil})")
            ->line("**Cours** : {$this->lesson->courseType->name}")
            ->line("**Date** : {$this->lesson->start_time->format('d/m/Y à H:i')}")
            ->line("**Durée** : {$this->lesson->duration} minutes")
            ->line("**Lieu** : {$locationName}")
            ->line("**Enseignant** : {$teacherName}")
            ->action('Voir les détails', url("/api/lessons/{$this->lesson->id}"))
            ->line('À bientôt !');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'lesson_id' => $this->lesson->id,
            'message' => 'Rappel de cours',
            'course_type' => $this->lesson->courseType->name,
            'start_time' => $this->lesson->start_time,
        ];
    }
}
