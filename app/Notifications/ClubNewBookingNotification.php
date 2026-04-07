<?php

namespace App\Notifications;

use App\Models\Lesson;
use App\Models\User;
use App\Support\FrontendUrl;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Informs club stakeholders that a new lesson was booked (complement to teacher/student LessonBookedNotification).
 */
class ClubNewBookingNotification extends Notification implements ShouldQueue
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
        $this->lesson->loadMissing(['courseType', 'location', 'teacher.user.profile', 'student.user.profile', 'club']);

        $clubName = $this->lesson->club?->name ?? 'Votre club';
        $courseName = $this->lesson->courseType?->name ?? 'Cours';
        $dateStr = $this->lesson->start_time?->format('d/m/Y à H:i') ?? '—';
        $locationName = $this->lesson->location?->name ?? '—';
        $teacherName = $this->lesson->teacher?->user?->profile?->full_name
            ?? $this->lesson->teacher?->user?->name
            ?? '—';
        $studentName = $this->lesson->student?->user?->profile?->full_name
            ?? $this->lesson->student?->user?->name
            ?? '—';

        $greeting = 'Bonjour,';
        if ($notifiable instanceof User) {
            $greeting = 'Bonjour '.($notifiable->profile?->first_name ?? $notifiable->name).',';
        }

        return (new MailMessage)
            ->subject("Nouvelle réservation — {$clubName} — activibe")
            ->greeting($greeting)
            ->line("Une **nouvelle réservation** a été enregistrée pour le club **{$clubName}**.")
            ->line("**Cours** : {$courseName}")
            ->line("**Date** : {$dateStr}")
            ->line("**Lieu** : {$locationName}")
            ->line("**Enseignant** : {$teacherName}")
            ->line("**Élève** : {$studentName}")
            ->action('Voir le planning club', FrontendUrl::login('/club/planning'))
            ->line('Ce message est envoyé aux comptes responsables du club pour information.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'lesson_id' => $this->lesson->id,
            'message' => 'Nouvelle réservation (information club)',
            'course_type' => $this->lesson->courseType?->name ?? 'Cours',
            'start_time' => $this->lesson->start_time,
        ];
    }
}
