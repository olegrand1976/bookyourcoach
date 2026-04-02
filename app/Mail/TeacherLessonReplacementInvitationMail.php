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
use Illuminate\Support\Collection;

/**
 * Sent to the proposed substitute teacher; club admins are CC'd for transparency.
 *
 * @param  Collection<int, Lesson>  $lessons
 */
class TeacherLessonReplacementInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  list<string>  $clubAdminCcEmails
     */
    public function __construct(
        public Club $club,
        public Teacher $originalTeacher,
        public Teacher $replacementTeacher,
        public string $reason,
        public ?string $notes,
        public Collection $lessons,
        public array $clubAdminCcEmails = [],
        public string $teacherDashboardUrl = '',
    ) {}

    public function envelope(): Envelope
    {
        $count = $this->lessons->count();
        $cc = [];
        foreach ($this->clubAdminCcEmails as $email) {
            $email = trim((string) $email);
            if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $cc[] = new Address($email);
            }
        }

        return new Envelope(
            subject: $count > 1
                ? "[{$this->club->name}] On vous propose de remplacer sur {$count} cours"
                : "[{$this->club->name}] Demande de remplacement — action requise",
            cc: $cc,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.teacher-lesson-replacement-invitation',
            with: [
                'clubName' => $this->club->name,
                'originalTeacherName' => $this->originalTeacher->user?->name ?? 'Enseignant',
                'replacementTeacherName' => $this->replacementTeacher->user?->name ?? 'Vous',
                'reason' => $this->reason,
                'notes' => $this->notes,
                'lessons' => $this->lessons,
                'teacherDashboardUrl' => $this->teacherDashboardUrl,
            ],
        );
    }
}
