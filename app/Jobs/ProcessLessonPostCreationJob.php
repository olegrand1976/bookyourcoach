<?php

namespace App\Jobs;

use App\Models\Lesson;
use App\Models\SubscriptionInstance;
use App\Notifications\LessonBookedNotification;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job qui traite les actions post-création d'un cours de manière asynchrone
 * - Consommation d'abonnement
 * - Création de créneaux récurrents
 * - Envoi des notifications
 * - Programmation des rappels
 */
class ProcessLessonPostCreationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Lesson $lesson;
    protected int $recurringInterval;

    /**
     * Create a new job instance.
     */
    public function __construct(Lesson $lesson, int $recurringInterval = 1)
    {
        $this->lesson = $lesson;
        $this->recurringInterval = $recurringInterval;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info("🚀 [ProcessLessonPostCreation] Début traitement asynchrone pour le cours {$this->lesson->id}");

            // 1. Essayer de consommer un abonnement si l'élève en a un actif
            if ($this->lesson->student_id) {
                $this->tryConsumeSubscription();
                
                // 2. Créer un créneau récurrent si l'élève a un abonnement (et génère les cours suivants)
                $this->createRecurringSlotIfSubscription();

            }

            // 3. Envoyer les notifications
            $this->sendBookingNotifications();

            // 4. Programmer un rappel 24h avant le cours
            $this->scheduleReminder();

            Log::info("✅ [ProcessLessonPostCreation] Traitement asynchrone terminé pour le cours {$this->lesson->id}");
        } catch (\Exception $e) {
            Log::error("❌ [ProcessLessonPostCreation] Erreur lors du traitement asynchrone du cours {$this->lesson->id}: " . $e->getMessage(), [
                'lesson_id' => $this->lesson->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->setRecurrenceSkippedReason('Erreur lors de la création de la récurrence : ' . $e->getMessage());
        }
    }

    /**
     * Enregistre le motif pour lequel la récurrence n'a pas été créée (affiché à l'utilisateur via l'API).
     */
    private function setRecurrenceSkippedReason(?string $reason): void
    {
        try {
            $this->lesson->updateQuietly(['recurrence_skipped_reason' => $reason]);
        } catch (\Throwable $e) {
            Log::warning("Impossible d'enregistrer recurrence_skipped_reason: " . $e->getMessage());
        }
    }

    /**
     * Essaie de consommer un abonnement actif pour ce cours
     */
    private function tryConsumeSubscription(): void
    {
        try {
            if (!$this->lesson->course_type_id) {
                return;
            }

            $studentIds = [];
            if ($this->lesson->student_id) {
                $studentIds[] = $this->lesson->student_id;
            }
            
            $lessonStudents = $this->lesson->students()->pluck('students.id')->toArray();
            $studentIds = array_unique(array_merge($studentIds, $lessonStudents));
            
            if (empty($studentIds)) {
                return;
            }

            foreach ($studentIds as $studentId) {
                // Vérifier si le cours est déjà lié à un abonnement
                if ($this->lesson->subscriptionInstances()->count() > 0) {
                    Log::info("⏭️ Cours {$this->lesson->id} déjà lié à un abonnement, on passe", [
                        'student_id' => $studentId
                    ]);
                    continue;
                }

                // Trouver le bon abonnement actif pour cet élève et ce type de cours
                // (le plus ancien par date de création qui a encore des cours disponibles)
                $clubId = $this->lesson->club_id ?? null;
                $subscriptionInstance = SubscriptionInstance::findActiveSubscriptionForLesson(
                    $studentId,
                    $this->lesson->course_type_id,
                    $clubId
                );

                if ($subscriptionInstance) {
                    try {
                        $subscriptionInstance->consumeLesson($this->lesson);
                        
                        $studentNames = $subscriptionInstance->students->map(function ($student) {
                            if ($student->user) {
                                return $student->user->name;
                            }
                            $firstName = $student->first_name ?? '';
                            $lastName = $student->last_name ?? '';
                            $name = trim($firstName . ' ' . $lastName);
                            return !empty($name) ? $name : 'Élève sans nom';
                        })->filter()->join(', ');
                        
                        $subscriptionInstance->refresh();
                        
                        // Vérifier et mettre à jour le statut (peut passer en completed si plein)
                        // Cette méthode gère aussi la réouverture si l'abonnement redevient disponible
                        $subscriptionInstance->checkAndUpdateStatus();
                        
                        Log::info("✅ Cours {$this->lesson->id} consommé depuis l'abonnement {$subscriptionInstance->id} (ordre chronologique)", [
                            'lesson_id' => $this->lesson->id,
                            'subscription_instance_id' => $subscriptionInstance->id,
                            'subscription_created_at' => $subscriptionInstance->created_at,
                            'student_id' => $studentId,
                            'lessons_used' => $subscriptionInstance->lessons_used,
                            'remaining_lessons' => $subscriptionInstance->remaining_lessons
                        ]);
                        
                        // Un seul abonnement par cours, on arrête après le premier lien réussi
                        break;
                    } catch (\Exception $e) {
                        Log::error("❌ Erreur lors de la consommation: " . $e->getMessage(), [
                            'lesson_id' => $this->lesson->id,
                            'student_id' => $studentId,
                            'subscription_instance_id' => $subscriptionInstance->id ?? null
                        ]);
                        continue;
                    }
                } else {
                    Log::info("ℹ️ Aucun abonnement actif disponible pour le cours {$this->lesson->id}", [
                        'student_id' => $studentId,
                        'course_type_id' => $this->lesson->course_type_id
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error("Erreur tryConsumeSubscription: " . $e->getMessage());
        }
    }

    /**
     * Crée un créneau récurrent et génère les cours si l'élève a un abonnement.
     * Délégué à RecurrenceCreationService (26 semaines, sans expires_at).
     * Si le controller a déjà créé la récurrence en sync, le service ne refait rien.
     */
    private function createRecurringSlotIfSubscription(): void
    {
        if (!$this->lesson->student_id || !$this->lesson->teacher_id || !$this->lesson->course_type_id) {
            return;
        }

        $service = new \App\Services\RecurrenceCreationService();
        $service->createRecurrenceAndGenerateLessons($this->lesson, $this->recurringInterval);
    }

    /**
     * Envoie les notifications de réservation
     */
    private function sendBookingNotifications(): void
    {
        try {
            // Recharger les relations pour avoir les données à jour
            $this->lesson->load(['teacher.user', 'student.user']);

            if ($this->lesson->teacher && $this->lesson->teacher->user) {
                $this->lesson->teacher->user->notify(new LessonBookedNotification($this->lesson));
            }

            if ($this->lesson->student && $this->lesson->student->user) {
                $this->lesson->student->user->notify(new LessonBookedNotification($this->lesson));
            }

            Log::info("✅ Notifications envoyées pour le cours {$this->lesson->id}");
        } catch (\Exception $e) {
            Log::error("Erreur sendBookingNotifications: " . $e->getMessage());
        }
    }

    /**
     * Programme un rappel 24h avant le cours
     */
    private function scheduleReminder(): void
    {
        try {
            $reminderTime = Carbon::parse($this->lesson->start_time)->subHours(24);
            if ($reminderTime->isFuture()) {
                SendLessonReminderJob::dispatch($this->lesson)->delay($reminderTime);
                Log::info("✅ Rappel programmé pour le cours {$this->lesson->id} à {$reminderTime}");
            }
        } catch (\Exception $e) {
            Log::warning("Impossible de programmer le rappel pour le cours {$this->lesson->id}: " . $e->getMessage());
        }
    }
}



