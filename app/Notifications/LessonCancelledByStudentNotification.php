<?php

namespace App\Notifications;

use App\Models\Lesson;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LessonCancelledByStudentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Lesson $lesson,
        public string $reason,
        public Student $student
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
            ->line("Nous vous informons qu'un cours a été annulé par {$studentName}.")
            ->line("**Détails du cours annulé:**")
            ->line("**Type de cours:** {$courseTypeName}")
            ->line("**Date et heure:** {$lessonDate}")
            ->line("**Lieu:** {$locationName}");

        if ($this->lesson->teacher && $this->lesson->teacher->user) {
            $message->line("**Enseignant prévu:** {$this->lesson->teacher->user->name}");
        }

        $message->line("**Raison de l'annulation:** {$this->reason}");

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
        ];
    }
}
