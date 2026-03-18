<?php

namespace App\Notifications;

use App\Models\Lesson;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LessonCancelledByStudentClubNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Lesson $lesson,
        public string $reason,
        public Student $student,
        public bool $isLateCancel,
        public bool $hasCertificate,
        public bool $countInSubscription,
        public int $cancellationDeadlineHours = 8
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $studentName = $this->student->user->name ?? 'Un élève';
        $courseTypeName = $this->lesson->courseType->name ?? 'Cours';
        $lessonDate = Carbon::parse($this->lesson->start_time)->format('d/m/Y à H:i');
        $locationName = $this->lesson->location->name ?? 'Non spécifié';

        $message = (new MailMessage)
            ->subject('Annulation de cours - ' . $courseTypeName)
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Un cours a été annulé par {$studentName}.")
            ->line("**Cours:** {$courseTypeName}")
            ->line("**Date et heure:** {$lessonDate}")
            ->line("**Lieu:** {$locationName}");

        if ($this->lesson->teacher && $this->lesson->teacher->user) {
            $message->line("**Enseignant prévu:** {$this->lesson->teacher->user->name}");
        }

        $message->line("**Raison indiquée:** {$this->reason}");

        if ($this->isLateCancel) {
            $message->line("**Contexte:** Annulation à moins de {$this->cancellationDeadlineHours} h du cours.");
            if ($this->hasCertificate) {
                $message->line('Un certificat médical a été joint. Il est en attente de validation par le club (onglet historique de l\'élève).');
                $message->line('Impact abonnement : le cours ne sera pas décompté si le certificat est accepté.');
            } else {
                $message->line('Aucun certificat médical joint.');
                $message->line($this->countInSubscription
                    ? '**Impact abonnement :** ce cours sera compté dans l\'abonnement de l\'élève.'
                    : '**Impact abonnement :** non décompté.');
            }
        } else {
            $message->line("**Contexte:** Annulation à plus de {$this->cancellationDeadlineHours} h du cours. Le cours ne sera pas décompté de l'abonnement.");
        }

        return $message
            ->line('Merci de votre compréhension.')
            ->salutation('Cordialement, L\'équipe activibe');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'lesson_id' => $this->lesson->id,
            'message' => 'Cours annulé par un élève',
            'course_type' => $this->lesson->courseType->name ?? 'Cours',
            'start_time' => $this->lesson->start_time,
            'reason' => $this->reason,
            'student_id' => $this->student->id,
            'is_late_cancel' => $this->isLateCancel,
            'has_certificate' => $this->hasCertificate,
            'count_in_subscription' => $this->countInSubscription,
        ];
    }
}
