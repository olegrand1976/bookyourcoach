<?php

namespace App\Notifications;

use App\Models\Lesson;
use App\Models\Student;
use App\Models\SubscriptionInstance;
use App\Models\User;
use App\Support\FrontendUrl;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

/**
 * Alert club managers when a student cancels a lesson linked to subscription instance(s)
 * but that student is not registered as a beneficiary on those instance(s) (data inconsistency).
 *
 * Sent synchronously so club alert is not lost if the queue worker is down.
 */
class LessonCancellationSubscriptionParticipantMismatchNotification extends Notification
{

    /**
     * @param  Collection<int, SubscriptionInstance>  $instancesMissingStudent
     */
    public function __construct(
        public Lesson $lesson,
        public Student $cancellingStudent,
        public Collection $instancesMissingStudent,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->lesson->loadMissing(['courseType', 'club', 'student.user']);
        $this->cancellingStudent->loadMissing('user');

        $clubName = $this->lesson->club?->name ?? 'Votre club';
        $courseName = $this->lesson->courseType?->name ?? 'Cours';
        $dateStr = $this->lesson->start_time?->format('d/m/Y à H:i') ?? '—';
        $cancellingName = $this->cancellingStudent->user?->name ?? 'Élève #'.$this->cancellingStudent->id;
        $instanceIds = $this->instancesMissingStudent->pluck('id')->implode(', ');

        $greeting = 'Bonjour,';
        if ($notifiable instanceof User) {
            $greeting = 'Bonjour '.($notifiable->profile?->first_name ?? $notifiable->name).',';
        }

        return (new MailMessage)
            ->subject("Incohérence abonnement / cours — {$clubName} — activibe")
            ->greeting($greeting)
            ->line('Une **incohérence de données** a été détectée lors de l’annulation d’un cours par un élève.')
            ->line("L’élève **{$cancellingName}** a annulé un cours lié à un ou plusieurs abonnements, mais **n’est pas enregistré comme bénéficiaire** sur l’instance d’abonnement concernée. Les règles de consommation d’abonnement peuvent alors être incorrectes.")
            ->line('**Détails du cours**')
            ->line("**Cours** : {$courseName}")
            ->line("**Date** : {$dateStr}")
            ->line("**ID cours** : {$this->lesson->id}")
            ->line("**ID instance(s) sans cet élève** : {$instanceIds}")
            ->line('**Action recommandée** : vérifier dans l’administration du club que tous les élèves inscrits au cours (y compris co-inscrits) sont bien **rattachés à l’abonnement** correspondant, comme pour la fin de cours et la consommation des séances.')
            ->action('Ouvrir le planning club', FrontendUrl::login('/club/planning'))
            ->line('Ce message est envoyé automatiquement aux responsables du club.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'lesson_id' => $this->lesson->id,
            'cancelling_student_id' => $this->cancellingStudent->id,
            'subscription_instance_ids' => $this->instancesMissingStudent->pluck('id')->values()->all(),
            'message' => 'Incohérence participant cours / instance abonnement',
        ];
    }
}
