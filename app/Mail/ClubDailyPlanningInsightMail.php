<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ClubDailyPlanningInsightMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array<string, mixed>|null  $analysis  Résultat Gemini (structure analyzeClubDailyPlanning)
     * @param  array<string, mixed>  $payload  Payload brut (résumé planning)
     */
    public function __construct(
        public string $clubName,
        public string $targetDateLabel,
        public ?array $analysis,
        public array $payload,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: sprintf(
                '[BookYourCoach] Planning du %s — %s',
                $this->targetDateLabel,
                $this->clubName
            ),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.club-daily-planning-insight',
            with: [
                'clubName' => $this->clubName,
                'targetDateLabel' => $this->targetDateLabel,
                'analysis' => $this->analysis ?? [],
                'payload' => $this->payload,
            ],
        );
    }
}
