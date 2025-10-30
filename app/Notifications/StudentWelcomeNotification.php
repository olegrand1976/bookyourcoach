<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Password;

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
            ? url(config('app.frontend_url') . '/reset-password?token=' . $this->resetToken . '&email=' . urlencode($notifiable->email))
            : url(config('app.frontend_url') . '/login');

        return (new MailMessage)
            ->subject('Bienvenue dans ' . $this->clubName . ' - activibe')
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Félicitations ! Vous avez été inscrit(e) comme élève dans le club **{$this->clubName}**.")
            ->line('Pour commencer à utiliser la plateforme activibe, vous devez définir votre mot de passe.')
            ->action('Définir mon mot de passe', $resetUrl)
            ->line('Ce lien est valable pendant 24 heures.')
            ->line('Votre adresse email de connexion est : **' . $notifiable->email . '**')
            ->line('Une fois votre mot de passe défini, vous pourrez :')
            ->line('• Consulter vos cours et réservations')
            ->line('• Suivre votre progression')
            ->line('• Communiquer avec vos enseignants')
            ->line('• Gérer votre profil')
            ->line('Si vous n\'avez pas demandé à rejoindre ce club, veuillez ignorer cet email.')
            ->salutation('À bientôt sur activibe !');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'club_name' => $this->clubName,
            'message' => 'Bienvenue dans le club',
        ];
    }
}


