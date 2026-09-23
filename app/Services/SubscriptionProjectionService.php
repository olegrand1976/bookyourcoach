<?php

namespace App\Services;

use App\Models\ClubClosureDay;
use App\Models\Lesson;
use App\Models\SubscriptionInstance;
use App\Models\SubscriptionRecurringSlot;
use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * Date de fin théorique d'un abonnement : quand le dernier crédit sera-t-il consommé ?
 *
 * On projette dans l'ordre chronologique sur les cours déjà planifiés, puis — s'il reste des
 * crédits — sur la cadence des séries récurrentes de l'abonnement. Sont écartées les occurrences
 * qui ne consomment rien : congés du club et annulations qui libèrent la place.
 *
 * Calcul à la demande (fiche d'un abonnement) : il parcourt les cours futurs et les congés,
 * il n'a pas sa place dans la sérialisation d'une liste.
 */
class SubscriptionProjectionService
{
    /** Garde-fou d'extrapolation : au-delà, la date est déclarée indéterminable. */
    private const MAX_EXTRAPOLATION_YEARS = 3;

    /**
     * @return array{
     *     remaining_consumed: int,
     *     projected_end_date: ?string,
     *     status: 'exhausted'|'planned'|'extrapolated'|'undetermined'|'no_schedule',
     *     planned_consuming_lessons: int,
     *     extrapolated_occurrences: int,
     *     skipped_closure_days: int,
     *     skipped_released_cancellations: int,
     *     expires_at: ?string,
     *     expires_before_exhaustion: bool
     * }
     */
    public function project(SubscriptionInstance $instance, ?CarbonInterface $from = null): array
    {
        $now = $from ? Carbon::parse($from) : Carbon::now();
        $remaining = max(0, (int) $instance->remaining_consumed);
        $expiresAt = $instance->expires_at ? Carbon::parse($instance->expires_at)->endOfDay() : null;

        $result = [
            'remaining_consumed' => $remaining,
            'projected_end_date' => null,
            'status' => 'exhausted',
            'planned_consuming_lessons' => 0,
            'extrapolated_occurrences' => 0,
            'skipped_closure_days' => 0,
            'skipped_released_cancellations' => 0,
            'expires_at' => $expiresAt?->toDateString(),
            'expires_before_exhaustion' => false,
        ];

        if ($remaining === 0) {
            return $result;
        }

        $clubId = $this->resolveClubId($instance);
        $closureCache = [];

        // 1. Cours déjà planifiés, dans l'ordre.
        [$endDate, $consumed, $lastPlannedDate, $skippedClosures, $skippedReleased] =
            $this->walkPlannedLessons($instance, $now, $remaining, $clubId, $closureCache);

        $result['planned_consuming_lessons'] = $consumed;
        $result['skipped_closure_days'] = $skippedClosures;
        $result['skipped_released_cancellations'] = $skippedReleased;

        if ($endDate !== null) {
            $result['projected_end_date'] = $endDate->toDateString();
            $result['status'] = 'planned';

            return $this->withExpiryComparison($result, $expiresAt, $endDate);
        }

        // 2. Extrapolation sur la cadence des séries récurrentes actives.
        $stillMissing = $remaining - $consumed;
        $extrapolationStart = $lastPlannedDate ? $lastPlannedDate->copy()->addDay()->startOfDay() : $now->copy()->startOfDay();

        [$endDate, $occurrences, $extraClosures] =
            $this->extrapolateFromRecurringSlots($instance, $extrapolationStart, $stillMissing, $clubId, $closureCache);

        $result['extrapolated_occurrences'] = $occurrences;
        $result['skipped_closure_days'] += $extraClosures;

        if ($endDate === null) {
            $result['status'] = $this->hasActiveRecurringSlots($instance) ? 'undetermined' : 'no_schedule';

            return $result;
        }

        $result['projected_end_date'] = $endDate->toDateString();
        $result['status'] = 'extrapolated';

        return $this->withExpiryComparison($result, $expiresAt, $endDate);
    }

    /**
     * Consomme les cours futurs déjà planifiés.
     *
     * @return array{0: ?Carbon, 1: int, 2: ?Carbon, 3: int, 4: int}
     */
    private function walkPlannedLessons(
        SubscriptionInstance $instance,
        Carbon $now,
        int $remaining,
        ?int $clubId,
        array &$closureCache
    ): array {
        $lessons = $instance->lessons()
            ->whereNull('lessons.deleted_at')
            ->where('lessons.start_time', '>', $now)
            ->orderBy('lessons.start_time')
            ->get(['lessons.id', 'lessons.start_time', 'lessons.status', 'lessons.cancellation_count_in_subscription']);

        $consumed = 0;
        $skippedClosures = 0;
        $skippedReleased = 0;
        $lastDate = null;

        foreach ($lessons as $lesson) {
            $date = Carbon::parse($lesson->start_time);
            $lastDate = $date;

            if ($lesson->status === 'cancelled') {
                // Annulation tardive : le crédit est déjà décompté dans lessons_used, on ne le
                // reprend pas ici. Annulation libérée : la place est rendue, elle ne consomme rien.
                if (! $lesson->cancellation_count_in_subscription) {
                    $skippedReleased++;
                }

                continue;
            }

            if ($this->isClosed($clubId, $date, $closureCache)) {
                $skippedClosures++;

                continue;
            }

            $consumed++;

            if ($consumed >= $remaining) {
                return [$date, $consumed, $lastDate, $skippedClosures, $skippedReleased];
            }
        }

        return [null, $consumed, $lastDate, $skippedClosures, $skippedReleased];
    }

    /**
     * Prolonge les séries récurrentes actives à leur cadence, au-delà des cours déjà générés.
     *
     * @return array{0: ?Carbon, 1: int, 2: int}
     */
    private function extrapolateFromRecurringSlots(
        SubscriptionInstance $instance,
        Carbon $start,
        int $missing,
        ?int $clubId,
        array &$closureCache
    ): array {
        if ($missing <= 0) {
            return [null, 0, 0];
        }

        $slots = $this->activeRecurringSlots($instance);
        if ($slots->isEmpty()) {
            return [null, 0, 0];
        }

        $validator = new RecurringSlotValidator;
        $horizon = $start->copy()->addYears(self::MAX_EXTRAPOLATION_YEARS);
        $cursor = $start->copy();
        $occurrences = 0;
        $skippedClosures = 0;

        while ($cursor->lte($horizon)) {
            $fires = $slots->contains(
                // `end_date` de la série ne borne pas la projection : on extrapole la cadence.
                fn (SubscriptionRecurringSlot $slot) => $this->slotFiresOnDate($validator, $slot, $cursor)
            );

            if ($fires) {
                if ($this->isClosed($clubId, $cursor, $closureCache)) {
                    $skippedClosures++;
                } else {
                    $occurrences++;

                    if ($occurrences >= $missing) {
                        return [$cursor->copy(), $occurrences, $skippedClosures];
                    }
                }
            }

            $cursor->addDay();
        }

        return [null, $occurrences, $skippedClosures];
    }

    /**
     * Une occurrence tombe-t-elle ce jour ? On réutilise la règle métier officielle, en neutralisant
     * la seule borne de fin de série (l'extrapolation va au-delà, c'est son objet).
     */
    private function slotFiresOnDate(RecurringSlotValidator $validator, SubscriptionRecurringSlot $slot, Carbon $date): bool
    {
        if ($date->lt(Carbon::parse($slot->start_date)->startOfDay())) {
            return false;
        }

        $probe = $slot->replicate();
        $probe->id = $slot->id;
        $probe->end_date = $date->copy()->endOfDay();

        return $validator->subscriptionRecurringSlotFiresOnDate($probe, $date);
    }

    private function activeRecurringSlots(SubscriptionInstance $instance)
    {
        return $instance->legacyRecurringSlots()
            ->where('status', 'active')
            ->get();
    }

    private function hasActiveRecurringSlots(SubscriptionInstance $instance): bool
    {
        return $this->activeRecurringSlots($instance)->isNotEmpty();
    }

    private function isClosed(?int $clubId, Carbon $date, array &$cache): bool
    {
        $ymd = $date->toDateString();

        return $cache[$ymd] ??= ClubClosureDay::clubIsClosedOn($clubId, $ymd);
    }

    private function resolveClubId(SubscriptionInstance $instance): ?int
    {
        $instance->loadMissing('subscription');
        $clubId = $instance->subscription?->club_id;

        if ($clubId) {
            return (int) $clubId;
        }

        // Repli : club porté par le cours le plus récent de l'abonnement.
        $lesson = $instance->lessons()->orderByDesc('lessons.start_time')->first(['lessons.club_id']);

        return $lesson?->club_id ? (int) $lesson->club_id : null;
    }

    /**
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    private function withExpiryComparison(array $result, ?Carbon $expiresAt, Carbon $endDate): array
    {
        $result['expires_before_exhaustion'] = $expiresAt !== null && $expiresAt->lt($endDate);

        return $result;
    }
}
