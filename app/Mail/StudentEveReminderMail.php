<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Rappel de la veille au soir à l'élève (ou au foyer) : cours du lendemain, heure et moniteur.
 */
class StudentEveReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array{name: string, lessons: list<array{student: string, start: string, end: string, teacher: string}>}  $recipient
     */
    public function __construct(
        public string $clubName,
        public string $dateLabel,
        public array $recipient,
    ) {}

    public function envelope(): Envelope
    {
        $first = $this->recipient['lessons'][0]['start'] ?? '';
        $what = count($this->recipient['lessons']) > 1 ? 'Vos cours de demain' : 'Votre cours de demain';

        return new Envelope(
            subject: "Rappel : {$what} à {$first} ({$this->dateLabel}) — {$this->clubName}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.student-eve-reminder',
            with: ['clubName' => $this->clubName, 'dateLabel' => $this->dateLabel, 'recipient' => $this->recipient],
        );
    }
}
