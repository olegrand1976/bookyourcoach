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
        $firstName = $notifiable->profile?->first_name ?? $notifiable->name ?? 'Utilisateur';
        $courseName = $this->lesson->courseType?->name ?? 'Cours';
        $dateStr = $this->lesson->start_time?->format('d/m/Y à H:i') ?? '—';
        $locationName = $this->lesson->location?->name ?? '—';
        $studentName = $this->lesson->student?->user?->profile?->full_name
            ?? $this->lesson->student?->user?->name
            ?? 'Élève';

        return (new MailMessage)
            ->subject('Nouvelle réservation de cours - activibe')
            ->greeting("Bonjour {$firstName},")
            ->line('Une nouvelle réservation a été effectuée.')
            ->line("**Cours** : {$courseName}")
            ->line("**Date** : {$dateStr}")
            ->line("**Lieu** : {$locationName}")
            ->line("**Élève** : {$studentName}")
            ->action('Voir les détails', url("/api/lessons/{$this->lesson->id}"))
            ->line('Merci d\'utiliser activibe !');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'lesson_id' => $this->lesson->id,
            'message' => 'Nouvelle réservation de cours',
            'course_type' => $this->lesson->courseType?->name ?? 'Cours',
            'start_time' => $this->lesson->start_time,
        ];
    }
}
