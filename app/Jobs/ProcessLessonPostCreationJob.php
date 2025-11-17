<?php

namespace App\Jobs;

use App\Models\Lesson;
use App\Models\SubscriptionInstance;
use App\Models\SubscriptionRecurringSlot;
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

    /**
     * Create a new job instance.
     */
    public function __construct(Lesson $lesson)
    {
        $this->lesson = $lesson;
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
                
                // 2. Créer un créneau récurrent si l'élève a un abonnement
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
            // Ne pas faire échouer le job, juste logger l'erreur
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
     * Crée un créneau récurrent si l'élève a un abonnement actif
     */
    private function createRecurringSlotIfSubscription(): void
    {
        try {
            if (!$this->lesson->student_id || !$this->lesson->teacher_id) {
                return;
            }

            $activeSubscription = SubscriptionInstance::where('status', 'active')
                ->whereHas('students', function ($query) {
                    $query->where('students.id', $this->lesson->student_id);
                })
                ->with('subscription')
                ->orderBy('started_at', 'asc')
                ->first();

            if (!$activeSubscription) {
                Log::info("🔄 Pas de récurrence créée : aucun abonnement actif pour l'élève {$this->lesson->student_id}");
                return;
            }

            $startTime = Carbon::parse($this->lesson->start_time);
            $dayOfWeek = $startTime->dayOfWeek;
            $timeStart = $startTime->format('H:i:s');
            
            // Calculer la durée depuis start_time et end_time (les lessons n'ont pas de colonne duration)
            $durationMinutes = 60; // Par défaut
            if ($this->lesson->end_time) {
                $endTime = Carbon::parse($this->lesson->end_time);
                $durationMinutes = $startTime->diffInMinutes($endTime);
            }
            
            $timeEnd = $startTime->copy()->addMinutes($durationMinutes)->format('H:i:s');

            $recurringStartDate = Carbon::parse($this->lesson->start_time)->startOfDay();
            $recurringEndDate = now()->addMonths(6);
            
            if ($activeSubscription->expires_at && Carbon::parse($activeSubscription->expires_at)->lessThan($recurringEndDate)) {
                $recurringEndDate = Carbon::parse($activeSubscription->expires_at);
            }

            // Vérifier si une récurrence existe déjà (sans filtre status car la colonne n'existe pas)
            $existingRecurring = SubscriptionRecurringSlot::where('subscription_instance_id', $activeSubscription->id)
                ->where('student_id', $this->lesson->student_id)
                ->where('teacher_id', $this->lesson->teacher_id)
                ->where('day_of_week', $dayOfWeek)
                ->where('start_time', $timeStart)
                ->first();

            if ($existingRecurring) {
                Log::info("🔄 Récurrence déjà existante pour ce créneau");
                return;
            }

            // ✅ OPTIMISATION : Ne pas vérifier les conflits - les créer directement
            // Les conflits seront gérés manuellement par le club via l'interface
            // Note: La table n'a que les colonnes de base (pas de status, open_slot_id, ni notes)
            $recurringSlot = SubscriptionRecurringSlot::create([
                'subscription_instance_id' => $activeSubscription->id,
                'teacher_id' => $this->lesson->teacher_id,
                'student_id' => $this->lesson->student_id,
                'day_of_week' => $dayOfWeek,
                'start_time' => $timeStart,
                'end_time' => $timeEnd,
                'start_date' => $recurringStartDate,
                'end_date' => $recurringEndDate,
            ]);

            Log::info("✅ Créneau récurrent RÉSERVÉ", [
                'recurring_slot_id' => $recurringSlot->id,
                'lesson_id' => $this->lesson->id,
                'student_id' => $this->lesson->student_id,
                'teacher_id' => $this->lesson->teacher_id,
                'day_of_week' => $dayOfWeek
            ]);

            // Générer automatiquement les cours pour toute la période de validité de la récurrence
            try {
                $legacyService = new \App\Services\LegacyRecurringSlotService();
                // Générer à partir de la semaine prochaine, mais jusqu'à la fin de la récurrence
                // Le service ajustera automatiquement si la date de début de la récurrence est dans le futur
                $startDate = Carbon::now()->addWeek(); // Commencer à partir de la semaine prochaine
                // endDate sera automatiquement limité à la fin de la récurrence dans le service
                $stats = $legacyService->generateLessonsForSlot($recurringSlot, $startDate, null);
                
                Log::info("✅ Cours générés automatiquement depuis créneau récurrent", [
                    'recurring_slot_id' => $recurringSlot->id,
                    'generated' => $stats['generated'],
                    'skipped' => $stats['skipped'],
                    'errors' => $stats['errors']
                ]);
            } catch (\Exception $e) {
                // Ne pas faire échouer le job si la génération échoue
                Log::error("Erreur lors de la génération automatique des cours: " . $e->getMessage(), [
                    'recurring_slot_id' => $recurringSlot->id,
                    'trace' => $e->getTraceAsString()
                ]);
            }

        } catch (\Exception $e) {
            Log::error("Erreur createRecurringSlotIfSubscription: " . $e->getMessage());
        }
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



