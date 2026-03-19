<?php

namespace App\Notifications;

use App\Models\Lesson;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CertificateRequestClosedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Lesson $lesson,
        public ?string $closeReason
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $courseTypeName = $this->lesson->courseType->name ?? 'Cours';
        $lessonDate = Carbon::parse($this->lesson->start_time)->format('d/m/Y à H:i');

        $message = (new MailMessage)
            ->subject('Demande de certificat médical clôturée - ' . $courseTypeName)
            ->greeting("Bonjour {$notifiable->name},")
            ->line('Le club a clôturé la demande de certificat médical pour le cours annulé suivant. Il ne sera plus possible de renvoyer un certificat pour ce cours.')
            ->line("**Cours:** {$courseTypeName}")
            ->line("**Date prévue:** {$lessonDate}");

        if (!empty($this->closeReason)) {
            $message->line('**Message du club:** ' . $this->closeReason);
        }

        $message->line('Ce cours sera décompté de votre abonnement.');

        return $message
            ->line('Merci de votre compréhension.')
            ->salutation('Cordialement, L\'équipe activibe');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'lesson_id' => $this->lesson->id,
            'message' => 'Demande de certificat clôturée',
            'close_reason' => $this->closeReason,
        ];
    }
}
