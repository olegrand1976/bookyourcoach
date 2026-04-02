<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ClubClosureNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $clubName,
        public string $dateLabel,
        public ?string $planningUrl = null,
        public ?string $clubEmailBcc = null,
        public string $kind = 'closed',
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $firstName = null;
        if (isset($notifiable->profile) && $notifiable->profile?->first_name) {
            $firstName = $notifiable->profile->first_name;
        } elseif (property_exists($notifiable, 'name') && $notifiable->name) {
            $firstName = $notifiable->name;
        }

        $greeting = $firstName ? "Bonjour {$firstName}," : 'Bonjour,';

        if ($this->kind === 'reopened') {
            $mail = (new MailMessage)
                ->subject("Congé annulé — {$this->clubName} ({$this->dateLabel})")
                ->greeting($greeting)
                ->line("Le club **{$this->clubName}** a **annulé** le jour de congés / fermeture prévu le **{$this->dateLabel}**.")
                ->line('Les cours prévus à cette date sont à considérer comme maintenus selon le planning, sauf communication contraire du club.')
                ->line('Si vous aviez reçu un message précédent indiquant l’annulation de la journée, ce courrier confirme que cette fermeture n’a plus lieu.');
        } else {
            $mail = (new MailMessage)
                ->subject("Jour de fermeture — {$this->clubName} ({$this->dateLabel})")
                ->greeting($greeting)
                ->line("Le club **{$this->clubName}** a indiqué une fermeture / jour de congés le **{$this->dateLabel}**.")
                ->line('**Les cours habituellement prévus ce jour-là ne sont pas maintenus** (jour fermé). Les moniteurs et élèves concernés par les créneaux de la journée sont informés par ce message.')
                ->line('Les séances créées ce jour-là ne déduisent pas les abonnements tant que la fermeture est active.')
                ->line('Les crédits abonnement ont été restitués pour les cours déjà liés à un abonnement sur cette date.');
        }

        if ($this->planningUrl) {
            $mail->action('Voir le planning', $this->planningUrl);
        }

        $mail->line('Merci de votre compréhension.');

        if ($this->clubEmailBcc && (!isset($notifiable->email) || $notifiable->email !== $this->clubEmailBcc)) {
            $mail->bcc($this->clubEmailBcc);
        }

        return $mail;
    }
}
