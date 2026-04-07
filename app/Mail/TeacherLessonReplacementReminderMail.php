<?php

namespace App\Mail;

use App\Models\Club;
use App\Models\Lesson;
use App\Models\Teacher;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Automatic reminder to the substitute teacher; original teacher and club stakeholders in CC.
 */
class TeacherLessonReplacementReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  '48h'|'24h'  $reminderTier
     * @param  list<string>  $ccEmails
     */
    public function __construct(
        public Club $club,
        public Lesson $lesson,
        public Teacher $originalTeacher,
        public Teacher $replacementTeacher,
        public string $reason,
        public ?string $notes,
        public string $reminderTier,
        public array $ccEmails = [],
        public string $teacherDashboardUrl = '',
    ) {}

    public function envelope(): Envelope
    {
        $cc = [];
        foreach ($this->ccEmails as $email) {
            $email = trim((string) $email);
            if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $cc[] = new Address($email);
            }
        }

        $when = $this->reminderTier === '48h' ? '48 heures' : '24 heures';

        return new Envelope(
            subject: "[{$this->club->name}] Rappel automatique — demande de remplacement ({$when} avant le cours)",
            cc: $cc,
        );
    }

    public function content(): Content
    {
        $this->lesson->loadMissing(['courseType', 'student.user']);

        return new Content(
            view: 'emails.teacher-lesson-replacement-reminder',
            with: [
                'clubName' => $this->club->name,
                'originalTeacherName' => $this->originalTeacher->user?->name ?? 'Enseignant',
                'replacementTeacherName' => $this->replacementTeacher->user?->name ?? 'Vous',
                'reason' => $this->reason,
                'notes' => $this->notes,
                'lesson' => $this->lesson,
                'reminderTier' => $this->reminderTier,
                'teacherDashboardUrl' => $this->teacherDashboardUrl,
            ],
        );
    }
}
