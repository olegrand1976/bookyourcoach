<?php

namespace App\Notifications;

use App\Models\Lesson;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LessonBookedNotification extends Notification implements ShouldQueue
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
        return (new MailMessage)
            ->subject('Nouvelle réservation de cours - BookYourCoach')
            ->greeting("Bonjour {$notifiable->profile->first_name},")
            ->line('Une nouvelle réservation a été effectuée.')
            ->line("**Cours** : {$this->lesson->courseType->name}")
            ->line("**Date** : {$this->lesson->start_time->format('d/m/Y à H:i')}")
            ->line("**Lieu** : {$this->lesson->location->name}")
            ->line("**Élève** : {$this->lesson->student->user->profile->full_name}")
            ->action('Voir les détails', url("/api/lessons/{$this->lesson->id}"))
            ->line('Merci d\'utiliser BookYourCoach !');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'lesson_id' => $this->lesson->id,
            'message' => 'Nouvelle réservation de cours',
            'course_type' => $this->lesson->courseType->name,
            'start_time' => $this->lesson->start_time,
        ];
    }
}
