<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Rappel de la veille au soir au moniteur : ses cours du lendemain ; s'il est seul au premier horaire,
 * il est prévenu d'arriver un peu plus tôt pour ouvrir et préparer.
 */
class TeacherEveReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array{name: string, opens_alone: bool, lessons: list<array{start: string, end: string, students: string}>}  $teacher
     */
    public function __construct(
        public string $clubName,
        public string $dateLabel,
        public array $teacher,
    ) {}

    public function envelope(): Envelope
    {
        $first = $this->teacher['lessons'][0]['start'] ?? '';
        $prefix = $this->teacher['opens_alone'] ? '[Ouverture] ' : '';

        return new Envelope(
            subject: $prefix."Vos cours de demain ({$this->dateLabel}) — premier à {$first} — {$this->clubName}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.teacher-eve-reminder',
            with: ['clubName' => $this->clubName, 'dateLabel' => $this->dateLabel, 'teacher' => $this->teacher],
        );
    }
}
