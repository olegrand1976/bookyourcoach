<?php

namespace App\Mail;

use App\Models\Club;
use App\Models\Teacher;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;

class VolunteerLetterMail extends Mailable
{
    use Queueable, SerializesModels;

    public Club $club;
    public Teacher $teacher;
    public string $pdfPath;

    /**
     * Create a new message instance.
     */
    public function __construct(Club $club, Teacher $teacher, string $pdfPath)
    {
        $this->club = $club;
        $this->teacher = $teacher;
        $this->pdfPath = $pdfPath;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Note d\'Information au Volontaire - ' . $this->club->name,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.volunteer-letter',
            with: [
                'clubName' => $this->club->name,
                'teacherName' => $this->teacher->user->name,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromPath($this->pdfPath)
                ->as('Note_Information_Volontaire.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
