<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Veille planning : incohérences détectées sur le planning du lendemain (capacité, doubles réservations,
 * cours générés ou réapparus à tort…), envoyée la veille aux responsables du club.
 */
class ClubPlanningConsistencyMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array<string, mixed>  $report  Résultat de PlanningConsistencyCheckService::checkDay
     */
    public function __construct(
        public string $clubName,
        public string $targetDateLabel,
        public array $report,
        public ?string $planningUrl = null,
    ) {}

    public function envelope(): Envelope
    {
        $critical = (int) ($this->report['counts']['critical'] ?? 0);
        $prefix = $critical > 0 ? "[Action requise — {$critical} incohérence(s)] " : '[À vérifier] ';

        return new Envelope(
            subject: $prefix.sprintf('[BookYourCoach] Planning du %s — %s', $this->targetDateLabel, $this->clubName),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.club-planning-consistency',
            with: [
                'clubName' => $this->clubName,
                'targetDateLabel' => $this->targetDateLabel,
                'report' => $this->report,
                'planningUrl' => $this->planningUrl,
            ],
        );
    }
}
