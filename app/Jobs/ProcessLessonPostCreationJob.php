<?php

namespace App\Jobs;

use App\Models\Lesson;
use App\Models\SubscriptionInstance;
use App\Notifications\LessonBookedNotification;
use App\Services\ClubClosureDayService;
use App\Services\LessonBookingNotificationService;
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
 * - Envoi des notifications
 * - Programmation des rappels
 *
 * La récurrence (slot + cours futurs) est créée en sync dans LessonController::store.
 */
class ProcessLessonPostCreationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Lesson $lesson;

    protected int $recurringInterval;

    protected bool $deductFromSubscription;

    protected bool $forceSubscriptionOverride;

    public function __construct(
        Lesson $lesson,
        int $recurringInterval = 1,
        bool $deductFromSubscription = true,
        bool $forceSubscriptionOverride = false
    ) {
        $this->lesson = $lesson;
        $this->recurringInterval = $recurringInterval;
        $this->deductFromSubscription = $deductFromSubscription;
        $this->forceSubscriptionOverride = $forceSubscriptionOverride;
    }

    public function handle(): void
    {
        try {
            Log::info("🚀 [ProcessLessonPostCreation] Début traitement asynchrone pour le cours {$this->lesson->id}");

            if ($this->deductFromSubscription && $this->lesson->hasParticipants()) {
                $this->tryConsumeSubscription();
            }

            $this->sendBookingNotifications();
            $this->scheduleReminder();

            Log::info("✅ [ProcessLessonPostCreation] Traitement asynchrone terminé pour le cours {$this->lesson->id}");
        } catch (\Exception $e) {
            Log::error("❌ [ProcessLessonPostCreation] Erreur lors du traitement asynchrone du cours {$this->lesson->id}: ".$e->getMessage(), [
                'lesson_id' => $this->lesson->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->setRecurrenceSkippedReason('Erreur lors de la création de la récurrence : '.$e->getMessage());
        }
    }

    private function setRecurrenceSkippedReason(?string $reason): void
    {
        try {
            $this->lesson->updateQuietly(['recurrence_skipped_reason' => $reason]);
        } catch (\Throwable $e) {
            Log::warning("Impossible d'enregistrer recurrence_skipped_reason: ".$e->getMessage());
        }
    }

    private function tryConsumeSubscription(): void
    {
        try {
            if (! $this->lesson->course_type_id) {
                return;
            }

            $closureService = app(ClubClosureDayService::class);
            if ($closureService->shouldSkipSubscriptionConsumption($this->lesson)) {
                Log::info("⏭️ Cours {$this->lesson->id} sur jour de fermeture club : pas de consommation d'abonnement", [
                    'lesson_id' => $this->lesson->id,
                    'club_id' => $this->lesson->club_id,
                ]);

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
                if ($this->lesson->subscriptionInstances()->count() > 0) {
                    Log::info("⏭️ Cours {$this->lesson->id} déjà lié à un abonnement, on passe", [
                        'student_id' => $studentId,
                    ]);

                    continue;
                }

                $clubId = $this->lesson->club_id ?? null;
                $asOf = $this->lesson->start_time
                    ? Carbon::parse($this->lesson->start_time)
                    : null;
                $subscriptionInstance = SubscriptionInstance::findActiveSubscriptionForLesson(
                    $studentId,
                    $this->lesson->course_type_id,
                    $clubId,
                    $asOf,
                    $this->forceSubscriptionOverride
                );

                if ($subscriptionInstance) {
                    try {
                        $subscriptionInstance->consumeLesson($this->lesson, $this->forceSubscriptionOverride);

                        $subscriptionInstance->refresh();
                        $subscriptionInstance->checkAndUpdateStatus();

                        Log::info("✅ Cours {$this->lesson->id} consommé depuis l'abonnement {$subscriptionInstance->id}", [
                            'lesson_id' => $this->lesson->id,
                            'subscription_instance_id' => $subscriptionInstance->id,
                            'student_id' => $studentId,
                            'lessons_used' => $subscriptionInstance->lessons_used,
                            'remaining_lessons' => $subscriptionInstance->remaining_lessons,
                        ]);

                        break;
                    } catch (\Exception $e) {
                        Log::error('❌ Erreur lors de la consommation: '.$e->getMessage(), [
                            'lesson_id' => $this->lesson->id,
                            'student_id' => $studentId,
                            'subscription_instance_id' => $subscriptionInstance->id ?? null,
                        ]);

                        continue;
                    }
                } else {
                    Log::info("ℹ️ Aucun abonnement actif disponible pour le cours {$this->lesson->id}", [
                        'student_id' => $studentId,
                        'course_type_id' => $this->lesson->course_type_id,
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Erreur tryConsumeSubscription: '.$e->getMessage());
        }
    }

    private function sendBookingNotifications(): void
    {
        try {
            $this->lesson->load(['teacher.user', 'student.user']);

            if ($this->lesson->teacher && $this->lesson->teacher->user) {
                $this->lesson->teacher->user->notify(new LessonBookedNotification($this->lesson));
            }

            if ($this->lesson->student && $this->lesson->student->user) {
                $this->lesson->student->user->notify(new LessonBookedNotification($this->lesson));
            }

            app(LessonBookingNotificationService::class)->notifyClubStakeholdersOfNewBooking($this->lesson);

            Log::info("✅ Notifications envoyées pour le cours {$this->lesson->id}");
        } catch (\Exception $e) {
            Log::error('Erreur sendBookingNotifications: '.$e->getMessage());
        }
    }

    private function scheduleReminder(): void
    {
        try {
            $reminderTime = Carbon::parse($this->lesson->start_time)->subHours(24);
            if ($reminderTime->isFuture()) {
                SendLessonReminderJob::dispatch($this->lesson)->delay($reminderTime);
                Log::info("✅ Rappel programmé pour le cours {$this->lesson->id} à {$reminderTime}");
            }
        } catch (\Exception $e) {
            Log::warning("Impossible de programmer le rappel pour le cours {$this->lesson->id}: ".$e->getMessage());
        }
    }
}
