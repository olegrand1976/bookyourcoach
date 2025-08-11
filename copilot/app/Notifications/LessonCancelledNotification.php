<?php

namespace App\Notifications;

use App\Models\Lesson;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LessonCancelledNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Lesson $lesson,
        public string $reason = ''
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('Cours annulé - BookYourCoach')
            ->greeting("Bonjour {$notifiable->profile->first_name},")
            ->line('Nous regrettons de vous informer que votre cours a été annulé.')
            ->line("**Cours** : {$this->lesson->courseType->name}")
            ->line("**Date** : {$this->lesson->start_time->format('d/m/Y à H:i')}")
            ->line("**Lieu** : {$this->lesson->location->name}");

        if ($this->reason) {
            $message->line("**Raison** : {$this->reason}");
        }

        return $message
            ->line('Si un paiement a été effectué, il sera remboursé sous 3-5 jours ouvrables.')
            ->action('Réserver un autre cours', url('/api/course-types'))
            ->line('Nous nous excusons pour la gêne occasionnée.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'lesson_id' => $this->lesson->id,
            'message' => 'Cours annulé',
            'course_type' => $this->lesson->courseType->name,
            'start_time' => $this->lesson->start_time,
            'reason' => $this->reason,
        ];
    }
}
