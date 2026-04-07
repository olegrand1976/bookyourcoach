<?php

namespace App\Notifications;

use App\Models\Lesson;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LessonCancellationConfirmationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Lesson $lesson,
        public bool $countInSubscription,
        public ?string $certificateStatus = null,
        public int $cancellationDeadlineHours = 8,
        public string $reasonFreeText = '',
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $courseTypeName = $this->lesson->courseType->name ?? 'Cours';
        $lessonDate = Carbon::parse($this->lesson->start_time)->format('d/m/Y à H:i');
        $locationName = $this->lesson->location->name ?? 'Non spécifié';

        $message = (new MailMessage)
            ->subject('Confirmation d\'annulation de cours - ' . $courseTypeName)
            ->greeting("Bonjour {$notifiable->name},")
            ->line('Votre annulation de cours a bien été enregistrée.')
            ->line("**Cours:** {$courseTypeName}")
            ->line("**Date et heure:** {$lessonDate}")
            ->line("**Lieu:** {$locationName}");

        $trimmed = trim($this->reasonFreeText);
        if ($trimmed !== '') {
            $message->line("**Message transmis au club et à l'enseignant :** {$trimmed}");
        }

        if ($this->countInSubscription) {
            $message->line("Ce cours sera compté dans votre abonnement (annulation à moins de {$this->cancellationDeadlineHours} h sans certificat médical accepté).");
        } else {
            if ($this->certificateStatus === 'pending') {
                $message->line('Votre certificat médical a été transmis au club. Il sera examiné sous peu ; le cours ne sera pas déduit de votre abonnement si le certificat est accepté.');
            } else {
                $message->line('Ce cours ne sera pas déduit de votre abonnement.');
            }
        }

        return $message
            ->line('Merci de votre compréhension.')
            ->salutation('Cordialement, L\'équipe activibe');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'lesson_id' => $this->lesson->id,
            'message' => 'Confirmation annulation de cours',
            'course_type' => $this->lesson->courseType->name ?? 'Cours',
            'start_time' => $this->lesson->start_time,
            'count_in_subscription' => $this->countInSubscription,
            'certificate_status' => $this->certificateStatus,
            'reason_free_text' => $this->reasonFreeText,
        ];
    }
}
