<?php

namespace App\Notifications;

use App\Models\Lesson;
use App\Models\User;
use App\Support\FrontendUrl;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class LessonCancelledNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public const RECIPIENT_STUDENT = 'student';

    public const RECIPIENT_TEACHER = 'teacher';

    public const RECIPIENT_CLUB_MANAGER = 'club_manager';

    /**
     * @param  array<int>  $lessonIds
     */
    public function __construct(
        public array $lessonIds,
        public string $reason,
        public int $cancelledByUserId,
        public string $recipientRole,
    ) {
        if (! in_array($recipientRole, [
            self::RECIPIENT_STUDENT,
            self::RECIPIENT_TEACHER,
            self::RECIPIENT_CLUB_MANAGER,
        ], true)) {
            throw new \InvalidArgumentException('Invalid recipient role for lesson cancellation notification.');
        }
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        /** @var Collection<int, Lesson> $lessons */
        $lessons = Lesson::query()
            ->whereIn('id', $this->lessonIds)
            ->with(['courseType', 'location', 'teacher.user.profile', 'student.user.profile', 'club'])
            ->orderBy('start_time')
            ->get();

        $first = $lessons->first();
        $clubName = $first?->club?->name ?? 'Votre club';

        $cancelledBy = User::query()->find($this->cancelledByUserId);
        $managerDisplayName = $this->resolveDisplayName($cancelledBy);

        $studentUser = $first?->student?->user;
        $teacherUser = $first?->teacher?->user;
        $studentDisplayName = $this->resolveDisplayName($studentUser);
        $teacherDisplayName = $this->resolveDisplayName($teacherUser);

        $greeting = $this->resolveFirstName($notifiable instanceof User ? $notifiable : null);

        return match ($this->recipientRole) {
            self::RECIPIENT_STUDENT => $this->buildStudentMail(
                $lessons,
                $clubName,
                $managerDisplayName,
                $greeting,
                $teacherDisplayName
            ),
            self::RECIPIENT_TEACHER => $this->buildTeacherMail(
                $lessons,
                $clubName,
                $greeting,
                $studentDisplayName
            ),
            self::RECIPIENT_CLUB_MANAGER => $this->buildClubManagerMail(
                $lessons,
                $clubName,
                $greeting,
                $studentDisplayName,
                $teacherDisplayName
            ),
        };
    }

    /**
     * @param  Collection<int, Lesson>  $lessons
     */
    private function buildStudentMail(
        Collection $lessons,
        string $clubName,
        string $managerDisplayName,
        string $greeting,
        string $teacherDisplayName
    ): MailMessage {
        $n = $lessons->count();
        $subject = $n > 1
            ? "Annulation de {$n} séances — {$clubName} — activibe"
            : "Annulation de cours — {$clubName} — activibe";

        $mail = (new MailMessage)
            ->subject($subject)
            ->greeting("Bonjour {$greeting},")
            ->line(
                $n > 1
                    ? "Le responsable de **{$clubName}** ({$managerDisplayName}) a annulé **{$n} séances** prévues avec **{$teacherDisplayName}**."
                    : "Le responsable de **{$clubName}** ({$managerDisplayName}) a annulé la séance suivante avec **{$teacherDisplayName}**."
            );

        $this->appendLessonLines($mail, $lessons);

        if ($this->reason !== '') {
            $mail->line('**Motif indiqué par le club** : '.$this->reason);
        }

        return $mail
            ->line('Si un paiement a été effectué, le remboursement sera traité selon les conditions du club.')
            ->action('Mon espace élève', FrontendUrl::login('/student/dashboard'))
            ->line('Nous nous excusons pour la gêne occasionnée.');
    }

    /**
     * @param  Collection<int, Lesson>  $lessons
     */
    private function buildTeacherMail(
        Collection $lessons,
        string $clubName,
        string $greeting,
        string $studentDisplayName
    ): MailMessage {
        $n = $lessons->count();
        $subject = $n > 1
            ? "{$n} séances annulées par le club — activibe"
            : 'Séance annulée par le club — activibe';

        $mail = (new MailMessage)
            ->subject($subject)
            ->greeting("Bonjour {$greeting},")
            ->line(
                $n > 1
                    ? "**{$clubName}** a annulé **{$n} séances** avec l'élève **{$studentDisplayName}**."
                    : "**{$clubName}** a annulé une séance avec l'élève **{$studentDisplayName}**."
            );

        $this->appendLessonLines($mail, $lessons);

        if ($this->reason !== '') {
            $mail->line('**Motif communiqué** : '.$this->reason);
        }

        return $mail
            ->action('Mon espace enseignant', FrontendUrl::login('/teacher/dashboard'))
            ->line('Merci de prendre note de cette mise à jour dans votre planning.');
    }

    /**
     * @param  Collection<int, Lesson>  $lessons
     */
    private function buildClubManagerMail(
        Collection $lessons,
        string $clubName,
        string $greeting,
        string $studentDisplayName,
        string $teacherDisplayName
    ): MailMessage {
        $n = $lessons->count();
        $subject = $n > 1
            ? "Confirmation : {$n} séances annulées — activibe"
            : 'Confirmation d\'annulation de séance — activibe';

        $mail = (new MailMessage)
            ->subject($subject)
            ->greeting("Bonjour {$greeting},")
            ->line(
                $n > 1
                    ? "Vous venez d'annuler **{$n} séances** au nom de **{$clubName}**."
                    : "Vous venez d'annuler une séance au nom de **{$clubName}**."
            )
            ->line("**Élève** : {$studentDisplayName}")
            ->line("**Enseignant** : {$teacherDisplayName}");

        $this->appendLessonLines($mail, $lessons);

        if ($this->reason !== '') {
            $mail->line('**Motif enregistré** : '.$this->reason);
        }

        return $mail
            ->action('Ouvrir le planning club', FrontendUrl::login('/club/planning'))
            ->line('Un courriel récapitulatif a également été envoyé à l\'élève et à l\'enseignant concernés.');
    }

    /**
     * @param  Collection<int, Lesson>  $lessons
     */
    private function appendLessonLines(MailMessage $mail, Collection $lessons): void
    {
        $mail->line($lessons->count() > 1 ? '**Détail des séances** :' : '**Détail de la séance** :');

        foreach ($lessons as $lesson) {
            $courseName = $lesson->courseType?->name ?? 'Cours';
            $dateStr = $lesson->start_time?->format('d/m/Y à H:i') ?? '—';
            $endStr = $lesson->end_time?->format('H:i') ?? '—';
            $loc = $lesson->location?->name ?? '—';
            $mail->line("• **{$courseName}** — {$dateStr} – {$endStr} — {$loc}");
        }
    }

    private function resolveFirstName(?User $user): string
    {
        if (! $user) {
            return 'Bonjour';
        }

        $fromProfile = $user->profile?->first_name;
        if (is_string($fromProfile) && trim($fromProfile) !== '') {
            return trim($fromProfile);
        }

        if (is_string($user->first_name) && trim($user->first_name) !== '') {
            return trim($user->first_name);
        }

        $name = trim((string) $user->name);
        if ($name !== '') {
            $parts = preg_split('/\s+/', $name);

            return $parts[0] ?? $name;
        }

        return 'Bonjour';
    }

    private function resolveDisplayName(?User $user): string
    {
        if (! $user) {
            return '—';
        }

        $full = $user->profile?->full_name ?? null;
        if (is_string($full) && trim($full) !== '') {
            return trim($full);
        }

        $name = trim((string) $user->name);
        if ($name !== '') {
            return $name;
        }

        $fn = trim((string) ($user->first_name ?? ''));
        $ln = trim((string) ($user->last_name ?? ''));
        $combined = trim($fn.' '.$ln);

        return $combined !== '' ? $combined : '—';
    }

    public function toArray(object $notifiable): array
    {
        return [
            'lesson_ids' => $this->lessonIds,
            'reason' => $this->reason,
            'recipient_role' => $this->recipientRole,
        ];
    }
}
