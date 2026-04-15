<?php

namespace App\Notifications;

use App\Models\Lesson;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Same rich content for teacher and club managers when a student cancels a lesson:
 * optional message, late-cancel type, subscription impact, certificate context.
 */
class LessonCancelledByStudentStakeholderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Lesson $lesson,
        public Student $student,
        public bool $isLateCancel,
        public bool $hasCertificate,
        public bool $countInSubscription,
        public int $cancellationDeadlineHours,
        public string $reasonFreeText,
        public ?string $cancellationReasonCategory,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->lesson->loadMissing(['courseType', 'location', 'teacher.user']);
        $this->student->loadMissing('user');

        $studentName = $this->resolveStudentDisplayName();
        $courseTypeName = $this->lesson->courseType->name ?? 'Cours';
        $lessonDate = Carbon::parse($this->lesson->start_time)->format('d/m/Y à H:i');
        $locationName = $this->lesson->location->name ?? 'Non spécifié';

        $message = (new MailMessage)
            ->subject('Annulation de cours — '.$courseTypeName)
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Un cours a été annulé par {$studentName}.")
            ->line("**Cours :** {$courseTypeName}")
            ->line("**Date et heure :** {$lessonDate}")
            ->line("**Lieu :** {$locationName}");

        if ($this->lesson->teacher && $this->lesson->teacher->user) {
            $message->line("**Enseignant prévu :** {$this->lesson->teacher->user->name}");
        }

        $trimmedMessage = trim($this->reasonFreeText);
        if ($trimmedMessage !== '') {
            $message->line("**Message de l'élève :** {$trimmedMessage}");
        } else {
            $message->line("**Message de l'élève :** non renseigné.");
        }

        if ($this->isLateCancel && $this->cancellationReasonCategory) {
            $label = $this->cancellationReasonCategory === 'medical' ? 'médicale' : 'autre';
            $message->line("**Type d'annulation tardive (< {$this->cancellationDeadlineHours} h) :** {$label}");
        }

        if ($this->isLateCancel) {
            $message->line("**Contexte :** annulation à moins de {$this->cancellationDeadlineHours} h du cours.");
            if ($this->hasCertificate) {
                $message->line('Un certificat médical a été joint. Il est en attente de validation par le club (historique de l\'élève).');
                $message->line('**Impact abonnement :** le cours ne sera pas décompté si le certificat est accepté par le club.');
            } else {
                $message->line('Aucun certificat médical joint.');
                $message->line($this->countInSubscription
                    ? '**Impact abonnement :** ce cours est décompté de l\'abonnement de l\'élève.'
                    : '**Impact abonnement :** ce cours n\'est pas décompté de l\'abonnement.');
            }
        } else {
            $message->line("**Contexte :** annulation au moins {$this->cancellationDeadlineHours} h avant le cours.");
            $message->line('**Impact abonnement :** ce cours n\'est pas décompté de l\'abonnement.');
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
            'reason_free_text' => $this->reasonFreeText,
            'student_id' => $this->student->id,
            'is_late_cancel' => $this->isLateCancel,
            'has_certificate' => $this->hasCertificate,
            'count_in_subscription' => $this->countInSubscription,
        ];
    }

    /**
     * Resolve a clear student name for stakeholder emails.
     * Falls back to Student accessor (first_name + last_name) and finally a stable label with ID.
     */
    private function resolveStudentDisplayName(): string
    {
        $fromUser = trim((string) ($this->student->user?->name ?? ''));
        if ($fromUser !== '') {
            return $fromUser;
        }

        $fromStudent = trim((string) ($this->student->name ?? ''));
        if ($fromStudent !== '') {
            return $fromStudent;
        }

        return "Élève #{$this->student->id}";
    }
}
