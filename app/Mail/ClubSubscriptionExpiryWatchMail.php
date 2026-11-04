<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Veille abonnements : élèves qui ont cours le lendemain et dont l'abonnement arrive
 * en fin de vie, regroupés par urgence (critique → urgent → à surveiller).
 */
class ClubSubscriptionExpiryWatchMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array<string, mixed>  $report  Résultat de SubscriptionExpiryWatchService::buildReport
     */
    public function __construct(
        public string $clubName,
        public string $targetDateLabel,
        public array $report,
        public ?string $manageUrl = null,
    ) {}

    public function envelope(): Envelope
    {
        $counts = $this->report['counts'] ?? [];
        $critical = (int) ($counts['critical'] ?? 0);
        $urgent = (int) ($counts['urgent'] ?? 0);
        $prefix = $critical > 0
            ? "[Action requise — {$critical} critique(s)] "
            : ($urgent > 0 ? "[{$urgent} urgent(s)] " : '');

        return new Envelope(
            subject: $prefix.sprintf(
                '[BookYourCoach] Abonnements en fin de vie — cours du %s — %s',
                $this->targetDateLabel,
                $this->clubName
            ),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.club-subscription-expiry-watch',
            with: [
                'clubName' => $this->clubName,
                'targetDateLabel' => $this->targetDateLabel,
                'report' => $this->report,
                'manageUrl' => $this->manageUrl,
            ],
        );
    }
}
