<?php

namespace App\Notifications;

use App\Support\FrontendUrl;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StudentWelcomeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $clubName;
    protected $resetToken;

    public function __construct($clubName, $resetToken = null)
    {
        $this->clubName = $clubName;
        $this->resetToken = $resetToken;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $resetUrl = $this->resetToken
            ? FrontendUrl::resetPassword($this->resetToken, (string) $notifiable->email)
            : FrontendUrl::login('/student/dashboard');

        $name = $notifiable->name ?? '';
        $firstName = trim((string) ($notifiable->first_name ?? '')) ?: (explode(' ', $name)[0] ?? '') ?: $name ?: 'vous';

        return (new MailMessage)
            ->subject("Bienvenue chez {$this->clubName} — Activibe")
            ->greeting("Bonjour {$firstName},")
            ->line("Bonne nouvelle : vous êtes inscrit(e) au club **{$this->clubName}** sur Activibe.")
            ->line("Pour accéder à votre espace (cours, réservations, suivi), il suffit de définir votre mot de passe en un clic.")
            ->action('Créer mon mot de passe', $resetUrl)
            ->line('**À savoir :** ce lien est valable 24 h. Votre adresse de connexion : **' . $notifiable->email . '**')
            ->line('Ensuite, vous pourrez :')
            ->line('• Voir vos cours et réservations')
            ->line('• Suivre votre progression')
            ->line('• Échanger avec vos enseignants')
            ->line('• Gérer votre profil')
            ->line('Si vous n’avez pas demandé cette inscription, vous pouvez ignorer ce message.')
            ->salutation("L’équipe Activibe\nÀ très bientôt !");
    }

    public function toArray(object $notifiable): array
    {
        return [
            'club_name' => $this->clubName,
            'message' => 'Bienvenue dans le club',
        ];
    }
}


