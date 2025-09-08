<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentConfirmedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Payment $payment
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Paiement confirmé - activibe')
            ->greeting("Bonjour {$notifiable->profile->first_name},")
            ->line('Votre paiement a été confirmé avec succès.')
            ->line("**Montant** : {$this->payment->amount} €")
            ->line("**Cours** : {$this->payment->lesson->courseType->name}")
            ->line("**Date du cours** : {$this->payment->lesson->start_time->format('d/m/Y à H:i')}")
            ->line("**Transaction** : {$this->payment->stripe_payment_intent_id}")
            ->action('Voir mes cours', url('/api/lessons'))
            ->line('Merci pour votre confiance !');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'payment_id' => $this->payment->id,
            'amount' => $this->payment->amount,
            'lesson_id' => $this->payment->lesson_id,
            'message' => 'Paiement confirmé',
        ];
    }
}
