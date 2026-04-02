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
 * Sent to the requesting teacher when the substitute accepts or rejects; club admins CC'd.
 */
class TeacherLessonReplacementOutcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  list<string>  $clubAdminCcEmails
     */
    public function __construct(
        public Club $club,
        public Lesson $lesson,
        public Teacher $originalTeacher,
        public Teacher $replacementTeacher,
        public bool $accepted,
        public array $clubAdminCcEmails = [],
        public string $teacherDashboardUrl = '',
    ) {}

    public function envelope(): Envelope
    {
        $cc = [];
        foreach ($this->clubAdminCcEmails as $email) {
            $email = trim((string) $email);
            if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $cc[] = new Address($email);
            }
        }

        $subject = $this->accepted
            ? "[{$this->club->name}] Votre demande de remplacement a été acceptée"
            : "[{$this->club->name}] Votre demande de remplacement a été refusée";

        return new Envelope(
            subject: $subject,
            cc: $cc,
        );
    }

    public function content(): Content
    {
        $this->lesson->loadMissing(['courseType', 'student.user']);

        return new Content(
            view: 'emails.teacher-lesson-replacement-outcome',
            with: [
                'clubName' => $this->club->name,
                'lesson' => $this->lesson,
                'accepted' => $this->accepted,
                'originalTeacherName' => $this->originalTeacher->user?->name ?? 'Enseignant',
                'replacementTeacherName' => $this->replacementTeacher->user?->name ?? 'Remplaçant',
                'teacherDashboardUrl' => $this->teacherDashboardUrl,
            ],
        );
    }
}
