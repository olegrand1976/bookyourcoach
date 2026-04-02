<?php

namespace App\Services;

use App\Models\Lesson;
use App\Models\SubscriptionInstance;
use App\Models\SubscriptionRecurringSlot;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Service unique pour la création d'un créneau récurrent et la génération des cours.
 * Règle des 26 semaines, sans prise en compte de la date de fin d'abonnement.
 */
class RecurrenceCreationService
{
    /**
     * Crée un créneau récurrent et génère les Lesson futures si l'élève a un abonnement actif.
     * Ne tient pas compte de expires_at pour la récurrence (26 semaines à partir du premier cours).
     *
     * @param Lesson $lesson Cours déclencheur (déjà créé)
     * @param int $recurringInterval Fréquence en semaines (1=hebdo, 2=bi-hebdo, etc.)
     */
    public function createRecurrenceAndGenerateLessons(Lesson $lesson, int $recurringInterval): void
    {
        if ($recurringInterval < 1) {
            Log::info('RecurrenceCreationService: recurring_interval < 1, aucune récurrence ni génération de cours futurs', [
                'lesson_id' => $lesson->id,
            ]);

            return;
        }

        $recurringInterval = max(1, min(52, $recurringInterval));

        try {
            if (!$lesson->student_id || !$lesson->teacher_id || !$lesson->course_type_id) {
                return;
            }

            $clubId = $lesson->club_id ?? null;
            $lesson->refresh();
            $lesson->load('subscriptionInstances');

            $activeSubscription = SubscriptionInstance::findActiveSubscriptionForLesson(
                (int) $lesson->student_id,
                (int) $lesson->course_type_id,
                $clubId
            );
            if (!$activeSubscription && $clubId !== null) {
                $activeSubscription = SubscriptionInstance::findActiveSubscriptionForLesson(
                    (int) $lesson->student_id,
                    (int) $lesson->course_type_id,
                    null
                );
            }
            if (!$activeSubscription) {
                $activeSubscription = $lesson->subscriptionInstances()->first();
            }

            if (!$activeSubscription) {
                Log::warning("RecurrenceCreationService: aucun abonnement actif", [
                    'lesson_id' => $lesson->id,
                    'student_id' => $lesson->student_id,
                    'course_type_id' => $lesson->course_type_id,
                    'club_id' => $clubId,
                ]);
                $this->setReason($lesson, "Récurrence non créée : aucun abonnement actif pour cet élève et ce type de cours.");
                return;
            }

            $startTime = Carbon::parse($lesson->start_time);
            $dayOfWeek = $startTime->dayOfWeek;
            $timeStart = $startTime->format('H:i:s');
            $durationMinutes = 60;
            if ($lesson->end_time) {
                $endTime = Carbon::parse($lesson->end_time);
                $durationMinutes = $startTime->diffInMinutes($endTime);
            }
            $timeEnd = $startTime->copy()->addMinutes($durationMinutes)->format('H:i:s');

            $recurringStartDate = Carbon::parse($lesson->start_time)->startOfDay();
            $recurringEndDate = $recurringStartDate->copy()->addWeeks(26);

            $existingRecurringCandidates = SubscriptionRecurringSlot::where('subscription_instance_id', $activeSubscription->id)
                ->where('student_id', $lesson->student_id)
                ->where('teacher_id', $lesson->teacher_id)
                ->where('day_of_week', $dayOfWeek)
                ->where('start_time', $timeStart)
                ->where('recurring_interval', $recurringInterval)
                ->where('status', 'active')
                ->where('start_date', '<=', $recurringEndDate)
                ->where('end_date', '>=', $recurringStartDate)
                ->get();

            // Doublon "exact" = même cadence ET même phase d'occurrence
            $existingRecurring = $existingRecurringCandidates->first(function (SubscriptionRecurringSlot $slot) use ($recurringStartDate) {
                return $this->subscriptionRecurringSlotFiresOnDate($slot, $recurringStartDate);
            });

            if ($existingRecurring) {
                Log::info("Récurrence déjà existante pour ce créneau : génération des cours manquants", [
                    'recurring_slot_id' => $existingRecurring->id,
                    'lesson_id' => $lesson->id,
                    'recurring_interval' => $recurringInterval,
                ]);

                $this->setReason($lesson, null);
                $this->generateFutureLessons($existingRecurring, $lessonDate = Carbon::parse($lesson->start_time), $recurringInterval, $lesson);
                return;
            }

            $validator = new RecurringSlotValidator();
            $validation = $validator->validateRecurringAvailabilityWithoutOpenSlot(
                (int) $lesson->teacher_id,
                (int) $lesson->student_id,
                $recurringStartDate->format('Y-m-d'),
                $dayOfWeek,
                $timeStart,
                $timeEnd,
                $recurringInterval,
                (int) $lesson->id,
                $lesson->club_id ? (int) $lesson->club_id : null
            );

            if (!$validation['valid']) {
                $reason = 'Récurrence non créée : ' . ($validation['message'] ?? 'conflits sur 26 semaines.');
                $this->setReason($lesson, $reason);
                return;
            }

            $recurringSlot = SubscriptionRecurringSlot::create([
                'subscription_instance_id' => $activeSubscription->id,
                'teacher_id' => $lesson->teacher_id,
                'student_id' => $lesson->student_id,
                'day_of_week' => $dayOfWeek,
                'start_time' => $timeStart,
                'end_time' => $timeEnd,
                'recurring_interval' => $recurringInterval,
                'start_date' => $recurringStartDate,
                'end_date' => $recurringEndDate,
                'status' => 'active',
            ]);

            $this->setReason($lesson, null);

            Log::info("RecurrenceCreationService: créneau récurrent créé, génération des cours", [
                'recurring_slot_id' => $recurringSlot->id,
                'lesson_id' => $lesson->id,
                'start_date' => $recurringSlot->start_date?->format('Y-m-d'),
                'end_date' => $recurringSlot->end_date?->format('Y-m-d'),
                'recurring_interval' => $recurringInterval,
            ]);

            $lessonDate = Carbon::parse($lesson->start_time);
            $this->generateFutureLessons($recurringSlot, $lessonDate, $recurringInterval, $lesson);
        } catch (\Exception $e) {
            Log::error("RecurrenceCreationService: " . $e->getMessage(), [
                'lesson_id' => $lesson->id,
                'trace' => $e->getTraceAsString(),
            ]);
            $this->setReason($lesson, 'Erreur lors de la création de la récurrence : ' . $e->getMessage());
        }
    }

    private function setReason(Lesson $lesson, ?string $reason): void
    {
        try {
            $lesson->updateQuietly(['recurrence_skipped_reason' => $reason]);
        } catch (\Throwable $e) {
            Log::warning("Impossible d'enregistrer recurrence_skipped_reason: " . $e->getMessage());
        }
    }

    /**
     * Même logique d'ancrage que le validator / legacy service.
     */
    private function subscriptionRecurringSlotFiresOnDate(SubscriptionRecurringSlot $slot, Carbon $occurrenceDate): bool
    {
        $interval = max(1, (int) ($slot->recurring_interval ?? 1));
        $occurrence = $occurrenceDate->copy()->startOfDay();
        $slotEnd = Carbon::parse($slot->end_date)->endOfDay();
        $anchorBase = Carbon::parse($slot->start_date)->startOfDay();

        if ($occurrence->lt($anchorBase) || $occurrence->gt($slotEnd)) {
            return false;
        }
        if ((int) $occurrence->dayOfWeek !== (int) $slot->day_of_week) {
            return false;
        }

        $anchor = $anchorBase->copy();
        while ($anchor->dayOfWeek !== (int) $slot->day_of_week) {
            $anchor->addDay();
        }
        if ($occurrence->lt($anchor)) {
            return false;
        }

        $daysBetween = $anchor->diffInDays($occurrence, false);
        if ($daysBetween < 0 || ($daysBetween % 7) !== 0) {
            return false;
        }

        $weekIndex = (int) ($daysBetween / 7);

        return ($weekIndex % $interval) === 0;
    }

    private function generateFutureLessons(
        SubscriptionRecurringSlot $recurringSlot,
        Carbon $lessonDate,
        int $recurringInterval,
        Lesson $lesson
    ): void {
        $legacyService = new LegacyRecurringSlotService();
        $startDate = $lessonDate->copy()->addWeeks($recurringInterval);

        try {
            $stats = $legacyService->generateLessonsForSlot($recurringSlot, $startDate, null);
            Log::info("RecurrenceCreationService: cours générés depuis créneau récurrent", [
                'recurring_slot_id' => $recurringSlot->id,
                'start_generation_date' => $startDate->format('Y-m-d'),
                'generated' => $stats['generated'],
                'skipped' => $stats['skipped'],
                'errors' => $stats['errors'],
            ]);

            if (($stats['generated'] ?? 0) <= 0 && ($stats['errors'] ?? 0) <= 0) {
                $this->setReason(
                    $lesson,
                    'Récurrence créée mais aucun cours futur n’a pu être généré automatiquement. Vérifiez les conflits ou cours déjà existants sur les dates cibles.'
                );
            }
        } catch (\Exception $e) {
            Log::error("Erreur génération cours récurrents: " . $e->getMessage(), [
                'recurring_slot_id' => $recurringSlot->id,
                'trace' => $e->getTraceAsString(),
            ]);
            $this->setReason($lesson, 'Récurrence créée mais erreur lors de la génération des cours suivants : ' . $e->getMessage());
        }
    }
}
