<?php

namespace App\Services;

use App\Models\Lesson;
use App\Models\SubscriptionInstance;
use App\Models\SubscriptionRecurringSlot;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class SubscriptionRecurringSlotRelocationService
{
    public function __construct(
        private readonly RecurringSlotValidator $recurringSlotValidator,
    ) {}

    public function hasSlotScheduleChanged(
        Carbon $oldStart,
        Carbon $newStart,
        ?Carbon $oldEnd = null,
        ?Carbon $newEnd = null,
    ): bool {
        if ($oldStart->dayOfWeek !== $newStart->dayOfWeek) {
            return true;
        }

        if ($oldStart->format('H:i:s') !== $newStart->format('H:i:s')) {
            return true;
        }

        $oldEndTime = ($oldEnd ?? $oldStart)->format('H:i:s');
        $newEndTime = ($newEnd ?? $newStart)->format('H:i:s');

        return $oldEndTime !== $newEndTime;
    }

    /**
     * @return Collection<int, SubscriptionRecurringSlot>
     */
    public function findActiveSlotsForSchedule(
        SubscriptionInstance $subscriptionInstance,
        int $studentId,
        int $teacherId,
        Carbon $slotStart,
        Carbon $slotEnd,
    ): Collection {
        return SubscriptionRecurringSlot::query()
            ->where('subscription_instance_id', $subscriptionInstance->id)
            ->where('student_id', $studentId)
            ->where('teacher_id', $teacherId)
            ->where('day_of_week', $slotStart->dayOfWeek)
            ->where('start_time', $slotStart->format('H:i:s'))
            ->where('end_time', $slotEnd->format('H:i:s'))
            ->where('status', 'active')
            ->get();
    }

    /**
     * Libère les séries actives correspondant à l'ancien créneau (horaire / jour).
     */
    public function releaseActiveSlotsForSchedule(
        SubscriptionInstance $subscriptionInstance,
        int $studentId,
        int $teacherId,
        Carbon $slotStart,
        Carbon $slotEnd,
        string $reason,
    ): int {
        $slots = $this->findActiveSlotsForSchedule(
            $subscriptionInstance,
            $studentId,
            $teacherId,
            $slotStart,
            $slotEnd,
        );

        foreach ($slots as $slot) {
            $slot->release($reason);
            Log::info('🔓 Créneau récurrent libéré (ancien horaire)', [
                'recurring_slot_id' => $slot->id,
                'reason' => $reason,
            ]);
        }

        return $slots->count();
    }

    /**
     * Valide le déplacement de série (26 semaines) sans modifier la base.
     *
     * @return array<int, mixed> Liste des conflits (vide si OK ou rien à déplacer)
     */
    public function validateRelocation(
        Lesson $lesson,
        SubscriptionInstance $subscriptionInstance,
        int $studentId,
        int $oldTeacherId,
        Carbon $oldStart,
        Carbon $oldEnd,
        Carbon $newStart,
        Carbon $newEnd,
        ?int $newTeacherId = null,
    ): array {
        if (!$this->hasSlotScheduleChanged($oldStart, $newStart, $oldEnd, $newEnd)) {
            return [];
        }

        $slots = $this->findActiveSlotsForSchedule(
            $subscriptionInstance,
            $studentId,
            $oldTeacherId,
            $oldStart,
            $oldEnd,
        );

        if ($slots->isEmpty()) {
            return [];
        }

        $teacherId = $newTeacherId ?? $oldTeacherId;
        $recurringInterval = max(1, min(52, (int) ($slots->first()->recurring_interval ?? 1)));

        $validation = $this->recurringSlotValidator->validateRecurringAvailabilityWithoutOpenSlot(
            $teacherId,
            $studentId,
            $newStart->copy()->startOfDay()->format('Y-m-d'),
            $newStart->dayOfWeek,
            $newStart->format('H:i:s'),
            $newEnd->format('H:i:s'),
            $recurringInterval,
            (int) $lesson->id,
            $lesson->club_id ? (int) $lesson->club_id : null,
        );

        return $validation['valid'] ? [] : ($validation['conflicts'] ?? []);
    }

    /**
     * Déplace en place la série récurrente vers le nouveau créneau (après validation).
     *
     * @return array{relocated: int}|null null si aucun déplacement nécessaire
     */
    public function relocateForAllFutureUpdate(
        Lesson $lesson,
        SubscriptionInstance $subscriptionInstance,
        int $studentId,
        int $oldTeacherId,
        Carbon $oldStart,
        Carbon $oldEnd,
        Carbon $newStart,
        Carbon $newEnd,
        ?int $newTeacherId = null,
    ): ?array {
        if (!$this->hasSlotScheduleChanged($oldStart, $newStart, $oldEnd, $newEnd)) {
            return null;
        }

        $slots = $this->findActiveSlotsForSchedule(
            $subscriptionInstance,
            $studentId,
            $oldTeacherId,
            $oldStart,
            $oldEnd,
        );

        if ($slots->isEmpty()) {
            return ['relocated' => 0];
        }

        $teacherId = $newTeacherId ?? $oldTeacherId;
        $relocated = 0;
        foreach ($slots as $slot) {
            $note = sprintf(
                'Déplacé depuis %s %s–%s via modification cours #%d',
                $this->dayLabel($oldStart->dayOfWeek),
                $oldStart->format('H:i'),
                $oldEnd->format('H:i'),
                $lesson->id,
            );

            $slot->update([
                'day_of_week' => $newStart->dayOfWeek,
                'start_time' => $newStart->format('H:i:s'),
                'end_time' => $newEnd->format('H:i:s'),
                'teacher_id' => $teacherId,
                'notes' => ($slot->notes ? $slot->notes . "\n" : '') . $note,
            ]);

            Log::info('✅ Créneau récurrent déplacé', [
                'recurring_slot_id' => $slot->id,
                'old_day' => $oldStart->dayOfWeek,
                'new_day' => $newStart->dayOfWeek,
                'old_time' => $oldStart->format('H:i:s'),
                'new_time' => $newStart->format('H:i:s'),
            ]);

            $relocated++;
        }

        return ['relocated' => $relocated];
    }

    private function dayLabel(int $dayOfWeek): string
    {
        return match ($dayOfWeek) {
            0 => 'dimanche',
            1 => 'lundi',
            2 => 'mardi',
            3 => 'mercredi',
            4 => 'jeudi',
            5 => 'vendredi',
            6 => 'samedi',
            default => 'jour ' . $dayOfWeek,
        };
    }
}
