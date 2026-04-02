<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ClubGeneralCommunicationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $clubName,
        public string $mailSubject,
        public string $bodyText,
        public ?string $replyToEmail = null
    ) {}

    public function envelope(): Envelope
    {
        $envelope = new Envelope(
            subject: $this->mailSubject,
        );

        if ($this->replyToEmail && filter_var($this->replyToEmail, FILTER_VALIDATE_EMAIL)) {
            $envelope->replyTo($this->replyToEmail, $this->clubName);
        }

        return $envelope;
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.club-general-communication',
            with: [
                'clubName' => $this->clubName,
                'bodyText' => $this->bodyText,
            ],
        );
    }
}
