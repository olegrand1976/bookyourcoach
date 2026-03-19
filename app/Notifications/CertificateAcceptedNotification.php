<?php

namespace App\Notifications;

use App\Models\Lesson;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CertificateAcceptedNotification extends Notification implements ShouldQueue
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
        $courseTypeName = $this->lesson->courseType->name ?? 'Cours';
        $lessonDate = Carbon::parse($this->lesson->start_time)->format('d/m/Y à H:i');

        return (new MailMessage)
            ->subject('Certificat médical accepté - ' . $courseTypeName)
            ->greeting("Bonjour {$notifiable->name},")
            ->line('Le club a accepté votre certificat médical pour le cours annulé suivant.')
            ->line("**Cours:** {$courseTypeName}")
            ->line("**Date prévue:** {$lessonDate}")
            ->line('Ce cours ne sera pas décompté de votre abonnement.')
            ->line('Merci de votre compréhension.')
            ->salutation('Cordialement, L\'équipe activibe');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'lesson_id' => $this->lesson->id,
            'message' => 'Certificat médical accepté',
        ];
    }
}
