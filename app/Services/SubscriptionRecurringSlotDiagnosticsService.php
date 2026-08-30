<?php

namespace App\Services;

use App\Models\Lesson;
use App\Models\SubscriptionRecurringSlot;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SubscriptionRecurringSlotDiagnosticsService
{
    public const ISSUE_NO_FUTURE_LESSONS = 'no_future_lessons';

    public const ISSUE_TEACHER_MISMATCH = 'teacher_mismatch';

    public const ISSUE_ORPHAN_LESSONS = 'orphan_lessons';

    public const ISSUE_CANCELLED_SRS_WITH_FUTURES = 'cancelled_srs_with_futures';

    public const ISSUE_SCHEDULE_DRIFT = 'schedule_drift';

    public const ISSUE_GAP_IN_SERIES = 'gap_in_series';

    public function __construct(
        private readonly RecurringSlotValidator $recurringSlotValidator,
        private readonly LegacyRecurringSlotService $legacyRecurringSlotService,
    ) {}

    /**
     * @param  array{status?: string, issue?: string, teacher_id?: int, student_id?: int}  $filters
     * @return array{items: list<array<string, mixed>>, summary: array<string, int>}
     */
    public function diagnoseForClub(int $clubId, array $filters = []): array
    {
        $query = SubscriptionRecurringSlot::query()
            ->whereHas('subscriptionInstance', function ($q) use ($clubId) {
                $q->whereHas('subscription', fn ($s) => $s->where('club_id', $clubId));
            })
            ->with([
                'subscriptionInstance.subscription.template',
                'teacher.user',
                'student.user',
            ])
            ->orderBy('day_of_week')
            ->orderBy('start_time');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['teacher_id'])) {
            $query->where('teacher_id', (int) $filters['teacher_id']);
        }
        if (! empty($filters['student_id'])) {
            $query->where('student_id', (int) $filters['student_id']);
        }

        $slots = $query->get();
        $issueFilter = $filters['issue'] ?? null;

        // Index des SRS actifs du club (pour orphan)
        $activeSlotsByInstance = $slots
            ->where('status', 'active')
            ->groupBy('subscription_instance_id');

        $items = [];
        foreach ($slots as $slot) {
            $row = $this->diagnoseSlot($slot, $activeSlotsByInstance);
            if ($issueFilter) {
                $has = collect($row['issues'])->contains(fn ($i) => ($i['code'] ?? null) === $issueFilter);
                if (! $has) {
                    continue;
                }
            }
            $items[] = $row;
        }

        $summary = [
            'total' => count($items),
            'with_issues' => 0,
            self::ISSUE_NO_FUTURE_LESSONS => 0,
            self::ISSUE_TEACHER_MISMATCH => 0,
            self::ISSUE_ORPHAN_LESSONS => 0,
            self::ISSUE_CANCELLED_SRS_WITH_FUTURES => 0,
            self::ISSUE_SCHEDULE_DRIFT => 0,
            self::ISSUE_GAP_IN_SERIES => 0,
        ];

        foreach ($items as $row) {
            if ($row['issues'] !== []) {
                $summary['with_issues']++;
                foreach ($row['issues'] as $issue) {
                    $code = $issue['code'] ?? null;
                    if (is_string($code) && array_key_exists($code, $summary)) {
                        $summary[$code]++;
                    }
                }
            }
        }

        return [
            'items' => $items,
            'summary' => $summary,
        ];
    }

    /**
     * @param  Collection<string|int, Collection<int, SubscriptionRecurringSlot>>|null  $activeSlotsByInstance
     * @return array<string, mixed>
     */
    public function diagnoseSlot(
        SubscriptionRecurringSlot $slot,
        ?Collection $activeSlotsByInstance = null,
    ): array {
        $now = now();
        $auditEnd = $this->resolveAuditEnd($slot, $now);
        $alignedLessons = $this->queryAlignedFutureLessons($slot, $now, $auditEnd);
        $relatedLessons = $this->queryRelatedFutureLessons($slot, $now, $auditEnd);
        $issues = [];

        $futureCount = $alignedLessons->count();
        $nextExpected = $this->nextExpectedOccurrence($slot, $now);
        $needsRegen = false;

        if ($slot->status === 'active' && $auditEnd->greaterThan($now) && $futureCount === 0) {
            $issues[] = [
                'code' => self::ISSUE_NO_FUTURE_LESSONS,
                'message' => 'Série active sans cours futur planifié.',
            ];
            $needsRegen = true;
        }

        if ($slot->status === 'cancelled' && $relatedLessons->count() > 0) {
            $issues[] = [
                'code' => self::ISSUE_CANCELLED_SRS_WITH_FUTURES,
                'message' => 'Série annulée mais '.$relatedLessons->count().' cours futur(s) encore présents.',
            ];
        }

        if ($slot->status === 'active') {
            $mismatchedTeachers = $alignedLessons
                ->filter(fn (Lesson $l) => (int) $l->teacher_id !== (int) $slot->teacher_id)
                ->count();
            if ($mismatchedTeachers > 0) {
                $issues[] = [
                    'code' => self::ISSUE_TEACHER_MISMATCH,
                    'message' => "{$mismatchedTeachers} cours futur(s) aligné(s) avec un moniteur différent de la série.",
                ];
            }

            $slotStart = Carbon::parse($slot->start_time)->format('H:i:s');
            $slotEnd = Carbon::parse($slot->end_time)->format('H:i:s');
            $otherSignatures = $this->otherActiveSlotSignatures($slot, $activeSlotsByInstance);
            $driftCount = $relatedLessons->filter(function (Lesson $l) use ($slot, $slotStart, $slotEnd, $otherSignatures) {
                $start = Carbon::parse($l->start_time);
                $sig = ((int) $start->dayOfWeek).'|'.$start->format('H:i:s');

                // Cours d'une autre série active de la même instance → pas un drift de celle-ci
                if (isset($otherSignatures[$sig])) {
                    return false;
                }

                $sameDay = (int) $start->dayOfWeek === (int) $slot->day_of_week;
                $sameStart = $start->format('H:i:s') === $slotStart;

                if ($sameDay && $sameStart) {
                    $end = Carbon::parse($l->end_time ?? $l->start_time);

                    return $end->format('H:i:s') !== $slotEnd;
                }

                // Même jour, autre heure, sans série active correspondante → drift candidat
                return $sameDay;
            })->count();

            if ($driftCount > 0) {
                $issues[] = [
                    'code' => self::ISSUE_SCHEDULE_DRIFT,
                    'message' => "{$driftCount} cours futur(s) liés à l'abonnement hors jour/horaire de la série.",
                ];
            }

            $gapCount = $this->countGaps($slot, $alignedLessons, $now, $auditEnd);
            if ($gapCount > 0) {
                $issues[] = [
                    'code' => self::ISSUE_GAP_IN_SERIES,
                    'message' => "{$gapCount} occurrence(s) manquante(s) sur la fenêtre d'audit.",
                ];
                $needsRegen = true;
            }

            $orphanCount = $this->countOrphanLessons(
                $slot,
                $relatedLessons,
                $activeSlotsByInstance,
            );
            $isCanonicalForOrphans = $this->isCanonicalSlotForInstance($slot, $activeSlotsByInstance);
            if ($orphanCount > 0 && $isCanonicalForOrphans) {
                $issues[] = [
                    'code' => self::ISSUE_ORPHAN_LESSONS,
                    'message' => "{$orphanCount} cours futur(s) de l'abonnement sans série active alignée (jour/heure).",
                ];
            }
        }

        return [
            'id' => $slot->id,
            'status' => $slot->status,
            'day_of_week' => $slot->day_of_week,
            'start_time' => is_string($slot->start_time) ? $slot->start_time : Carbon::parse($slot->start_time)->format('H:i:s'),
            'end_time' => is_string($slot->end_time) ? $slot->end_time : Carbon::parse($slot->end_time)->format('H:i:s'),
            'recurring_interval' => $slot->recurring_interval,
            'start_date' => optional($slot->start_date)->format('Y-m-d'),
            'end_date' => optional($slot->end_date)->format('Y-m-d'),
            'student_id' => $slot->student_id,
            'teacher_id' => $slot->teacher_id,
            'student' => $this->serializePerson($slot->student),
            'teacher' => $this->serializePerson($slot->teacher),
            'subscription_instance' => $this->serializeSubscriptionInstance($slot),
            'future_lessons_count' => $futureCount,
            'next_expected_date' => $nextExpected?->format('Y-m-d'),
            'can_regenerate' => $slot->status === 'active' && $needsRegen,
            'issues' => $issues,
        ];
    }

    /**
     * Régénère les cours manquants sans soft-delete des existants.
     *
     * @return array{generated: int, skipped: int, errors: int, future_lessons_count: int}
     */
    public function regenerateFutureLessons(SubscriptionRecurringSlot $slot, ?Carbon $fromDate = null): array
    {
        if ($slot->status !== 'active') {
            throw new \InvalidArgumentException('Seules les séries actives peuvent être régénérées.');
        }

        $from = ($fromDate ?? now())->copy()->startOfDay();
        $stats = $this->legacyRecurringSlotService->generateLessonsForSlot($slot, $from, null);
        $auditEnd = $this->resolveAuditEnd($slot, now());
        $futureCount = $this->queryAlignedFutureLessons($slot, now(), $auditEnd)->count();

        return [
            'generated' => (int) ($stats['generated'] ?? 0),
            'skipped' => (int) ($stats['skipped'] ?? 0),
            'errors' => (int) ($stats['errors'] ?? 0),
            'future_lessons_count' => $futureCount,
        ];
    }

    private function resolveAuditEnd(SubscriptionRecurringSlot $slot, Carbon $now): Carbon
    {
        $horizon = $now->copy()->addWeeks(RecurringSlotValidator::VALIDATION_WEEKS);
        $slotEnd = $slot->end_date ? Carbon::parse($slot->end_date)->endOfDay() : $horizon;

        return $slotEnd->lessThan($horizon) ? $slotEnd : $horizon;
    }

    /**
     * Cours futurs alignés jour + heure de début de la série.
     *
     * @return Collection<int, Lesson>
     */
    private function queryAlignedFutureLessons(
        SubscriptionRecurringSlot $slot,
        Carbon $from,
        Carbon $to,
    ): Collection {
        return $this->baseRelatedFutureQuery($slot, $from, $to)
            ->when(
                DB::connection()->getDriverName() === 'sqlite',
                fn ($q) => $q->whereRaw(
                    "CAST(strftime('%w', start_time) AS INTEGER) = ?",
                    [(int) $slot->day_of_week]
                ),
                function ($q) use ($slot) {
                    $dayOfWeekMySQL = ((int) $slot->day_of_week) === 0 ? 1 : ((int) $slot->day_of_week + 1);

                    return $q->whereRaw('DAYOFWEEK(start_time) = ?', [$dayOfWeekMySQL]);
                }
            )
            ->whereRaw('TIME(start_time) = ?', [Carbon::parse($slot->start_time)->format('H:i:s')])
            ->orderBy('start_time')
            ->get();
    }

    /**
     * Tous les cours futurs de l'élève liés à l'instance (pour drift / orphan).
     *
     * @return Collection<int, Lesson>
     */
    private function queryRelatedFutureLessons(
        SubscriptionRecurringSlot $slot,
        Carbon $from,
        Carbon $to,
    ): Collection {
        return $this->baseRelatedFutureQuery($slot, $from, $to)
            ->orderBy('start_time')
            ->get();
    }

    private function baseRelatedFutureQuery(SubscriptionRecurringSlot $slot, Carbon $from, Carbon $to)
    {
        $studentId = (int) $slot->student_id;
        $instanceId = (int) $slot->subscription_instance_id;

        return Lesson::query()
            ->where('start_time', '>', $from)
            ->where('start_time', '<=', $to)
            ->where('status', '!=', 'cancelled')
            ->where(function ($q) use ($studentId) {
                $q->where('student_id', $studentId)
                    ->orWhereHas('students', fn ($sq) => $sq->where('students.id', $studentId));
            })
            ->whereHas('subscriptionInstances', fn ($q) => $q->where('subscription_instances.id', $instanceId));
    }

    /**
     * @param  Collection<int, Lesson>  $relatedLessons
     * @param  Collection<string|int, Collection<int, SubscriptionRecurringSlot>>|null  $activeSlotsByInstance
     */
    private function countOrphanLessons(
        SubscriptionRecurringSlot $slot,
        Collection $relatedLessons,
        ?Collection $activeSlotsByInstance,
    ): int {
        $activeForInstance = $activeSlotsByInstance
            ? ($activeSlotsByInstance->get($slot->subscription_instance_id) ?? collect())
            : collect([$slot])->filter(fn ($s) => $s->status === 'active');

        if ($activeForInstance->isEmpty()) {
            return $relatedLessons->count();
        }

        $signatures = $activeForInstance->map(function (SubscriptionRecurringSlot $s) {
            return ((int) $s->day_of_week).'|'.Carbon::parse($s->start_time)->format('H:i:s');
        })->unique()->all();
        $signatureSet = array_flip($signatures);

        return $relatedLessons->filter(function (Lesson $l) use ($signatureSet) {
            $start = Carbon::parse($l->start_time);
            $sig = ((int) $start->dayOfWeek).'|'.$start->format('H:i:s');

            return ! isset($signatureSet[$sig]);
        })->count();
    }

    private function nextExpectedOccurrence(SubscriptionRecurringSlot $slot, Carbon $from): ?Carbon
    {
        if ($slot->status !== 'active') {
            return null;
        }

        $cursor = $from->copy()->startOfDay();
        $end = $this->resolveAuditEnd($slot, $from);
        $guard = 0;
        while ($cursor->lte($end) && $guard < 400) {
            if ($this->recurringSlotValidator->subscriptionRecurringSlotFiresOnDate($slot, $cursor)) {
                return $cursor->copy();
            }
            $cursor->addDay();
            $guard++;
        }

        return null;
    }

    /**
     * @param  Collection<int, Lesson>  $futureLessons
     */
    private function countGaps(
        SubscriptionRecurringSlot $slot,
        Collection $futureLessons,
        Carbon $from,
        Carbon $to,
    ): int {
        $coveredDates = $futureLessons
            ->map(fn (Lesson $l) => Carbon::parse($l->start_time)->format('Y-m-d'))
            ->unique()
            ->all();
        $covered = array_flip($coveredDates);

        $gaps = 0;
        $cursor = $from->copy()->startOfDay();
        $guard = 0;
        while ($cursor->lte($to) && $guard < 400) {
            if ($this->recurringSlotValidator->subscriptionRecurringSlotFiresOnDate($slot, $cursor)) {
                $ymd = $cursor->format('Y-m-d');
                if (! isset($covered[$ymd])) {
                    $gaps++;
                }
            }
            $cursor->addDay();
            $guard++;
        }

        return $gaps;
    }

    /**
     * @return array{id: int|null, name: string|null}|null
     */
    private function serializePerson(mixed $model): ?array
    {
        if ($model === null) {
            return null;
        }

        $name = $model->user?->name
            ?? (trim(($model->first_name ?? '').' '.($model->last_name ?? '')) ?: null);

        return [
            'id' => isset($model->id) ? (int) $model->id : null,
            'name' => $name,
            'user' => $model->user ? ['name' => $model->user->name] : null,
        ];
    }

    /**
     * Signatures jour|heure des autres séries actives de la même instance (hors slot courant).
     *
     * @param  Collection<string|int, Collection<int, SubscriptionRecurringSlot>>|null  $activeSlotsByInstance
     * @return array<string, true>
     */
    private function otherActiveSlotSignatures(
        SubscriptionRecurringSlot $slot,
        ?Collection $activeSlotsByInstance,
    ): array {
        $siblings = $activeSlotsByInstance
            ? ($activeSlotsByInstance->get($slot->subscription_instance_id) ?? collect())
            : collect();

        $signatures = [];
        foreach ($siblings as $sibling) {
            if ((int) $sibling->id === (int) $slot->id) {
                continue;
            }
            $sig = ((int) $sibling->day_of_week).'|'.Carbon::parse($sibling->start_time)->format('H:i:s');
            $signatures[$sig] = true;
        }

        return $signatures;
    }

    /**
     * @param  Collection<string|int, Collection<int, SubscriptionRecurringSlot>>|null  $activeSlotsByInstance
     */
    private function isCanonicalSlotForInstance(
        SubscriptionRecurringSlot $slot,
        ?Collection $activeSlotsByInstance,
    ): bool {
        if ($activeSlotsByInstance === null) {
            return true;
        }
        $siblings = $activeSlotsByInstance->get($slot->subscription_instance_id) ?? collect();
        if ($siblings->isEmpty()) {
            return true;
        }

        return (int) $siblings->min('id') === (int) $slot->id;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function serializeSubscriptionInstance(SubscriptionRecurringSlot $slot): ?array
    {
        $instance = $slot->subscriptionInstance;
        if (! $instance) {
            return null;
        }

        $subscription = $instance->subscription;

        return [
            'id' => (int) $instance->id,
            'status' => $instance->status,
            'subscription' => $subscription ? [
                'id' => (int) $subscription->id,
                'subscription_number' => $subscription->subscription_number ?? null,
                'template' => $subscription->template ? [
                    'name' => $subscription->template->name ?? null,
                    'model_number' => $subscription->template->model_number ?? null,
                ] : null,
            ] : null,
        ];
    }
}
