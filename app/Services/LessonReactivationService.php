<?php

namespace App\Services;

use App\Models\Lesson;
use App\Models\SubscriptionInstance;
use App\Models\SubscriptionRecurringSlot;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LessonReactivationService
{
    public function __construct(
        private readonly LessonDeletionService $lessonDeletionService,
    ) {}

    /**
     * @param  array{
     *     reactivate_scope?: string,
     *     restore_recurring_slot?: bool,
     *     reattach_subscription?: bool,
     *     reason?: string|null,
     *     target_status?: string
     * }  $options
     * @return array<string, mixed>
     */
    public function reactivate(Lesson $lesson, User $actor, array $options = []): array
    {
        $scope = $options['reactivate_scope'] ?? 'single';
        $restoreRecurring = (bool) ($options['restore_recurring_slot'] ?? true);
        $reattachSubscription = (bool) ($options['reattach_subscription'] ?? true);
        $reason = $options['reason'] ?? null;
        $targetStatus = $options['target_status'] ?? 'confirmed';

        if ($lesson->status !== 'cancelled') {
            return [
                'success' => false,
                'message' => 'Seuls les cours annulés peuvent être réactivés.',
            ];
        }

        $targetStudentId = $this->lessonDeletionService->resolveParticipantStudentId($lesson);

        $lessons = $this->resolveCancelledLessonsToReactivate($lesson, $scope, $targetStudentId);

        if ($lessons->isEmpty()) {
            return [
                'success' => false,
                'message' => 'Aucun cours annulé à réactiver.',
            ];
        }

        $conflicts = $this->collectReactivationConflicts($lessons);

        if ($conflicts !== []) {
            return [
                'success' => false,
                'message' => 'Impossible de réactiver : conflits de planning détectés.',
                'conflicts' => $conflicts,
            ];
        }

        $reactivatedIds = [];

        DB::transaction(function () use (
            $lessons,
            $actor,
            $targetStatus,
            $reattachSubscription,
            $reason,
            $restoreRecurring,
            $lesson,
            &$reactivatedIds
        ) {
            foreach ($lessons as $lessonToReactivate) {
                $this->reactivateOneLesson(
                    $lessonToReactivate,
                    $actor,
                    $targetStatus,
                    $reattachSubscription,
                    $reason
                );
                $reactivatedIds[] = $lessonToReactivate->id;
            }

            if ($restoreRecurring) {
                $this->restoreRecurringSlotIfNeeded($lesson);
            }
        });

        $count = count($reactivatedIds);

        return [
            'success' => true,
            'message' => $count === 1
                ? 'Cours réactivé avec succès.'
                : "{$count} cours réactivés avec succès.",
            'data' => [
                'reactivated_count' => $count,
                'reactivated_lesson_ids' => $reactivatedIds,
            ],
        ];
    }

    /**
     * @return Collection<int, Lesson>
     */
    private function resolveCancelledLessonsToReactivate(
        Lesson $lesson,
        string $scope,
        ?int $targetStudentId
    ): Collection {
        if ($scope === 'single') {
            return collect([$lesson])->filter(fn (Lesson $l) => $l->status === 'cancelled');
        }

        $candidates = $this->lessonDeletionService->resolveLessonsToProcess(
            $lesson,
            'all_future',
            'cancel',
            null,
            $targetStudentId
        );

        return $candidates
            ->filter(fn (Lesson $l) => $l->status === 'cancelled')
            ->filter(function (Lesson $l) use ($lesson) {
                return Carbon::parse($l->start_time)->gte(Carbon::parse($lesson->start_time));
            })
            ->unique('id')
            ->values();
    }

    /**
     * @param  Collection<int, Lesson>  $lessons
     * @return array<int, array<string, mixed>>
     */
    private function collectReactivationConflicts(Collection $lessons): array
    {
        $conflicts = [];

        foreach ($lessons as $lesson) {
            $conflict = $this->detectSchedulingConflict($lesson);
            if ($conflict !== null) {
                $conflicts[] = $conflict;
            }
        }

        return $conflicts;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function detectSchedulingConflict(Lesson $lesson): ?array
    {
        $start = Carbon::parse($lesson->start_time);
        $end = Carbon::parse($lesson->end_time ?? $lesson->start_time);
        $studentId = $this->lessonDeletionService->resolveParticipantStudentId($lesson);

        $baseQuery = Lesson::query()
            ->where('club_id', $lesson->club_id)
            ->where('id', '!=', $lesson->id)
            ->whereIn('status', ['pending', 'confirmed'])
            ->where('start_time', '<', $end)
            ->where('end_time', '>', $start);

        if ($lesson->teacher_id && (clone $baseQuery)->where('teacher_id', $lesson->teacher_id)->exists()) {
            return [
                'type' => 'teacher_unavailable',
                'date' => $start->format('Y-m-d'),
                'message' => 'L\'enseignant a déjà un cours actif sur ce créneau.',
                'lesson_id' => (int) $lesson->id,
            ];
        }

        if ($studentId) {
            $studentBusy = (clone $baseQuery)->where(function ($q) use ($studentId) {
                $q->where('student_id', $studentId)
                    ->orWhereHas('students', function ($sq) use ($studentId) {
                        $sq->where('students.id', $studentId);
                    });
            })->exists();

            if ($studentBusy) {
                return [
                    'type' => 'student_unavailable',
                    'date' => $start->format('Y-m-d'),
                    'message' => 'L\'élève a déjà un cours actif sur ce créneau.',
                    'lesson_id' => (int) $lesson->id,
                ];
            }
        }

        return null;
    }

    private function reactivateOneLesson(
        Lesson $lesson,
        User $actor,
        string $targetStatus,
        bool $reattachSubscription,
        ?string $reason
    ): void {
        $noteLine = '[Réactivé par le club le ' . now()->format('d/m/Y H:i') . ']';
        if ($reason) {
            $noteLine .= ' ' . $reason;
        }

        $lesson->status = $targetStatus;
        $lesson->notes = ($lesson->notes ? $lesson->notes . "\n" : '') . $noteLine;
        $lesson->save();

        if ($reattachSubscription) {
            $this->reattachSubscriptions($lesson);
        }
    }

    private function reattachSubscriptions(Lesson $lesson): void
    {
        $instanceIds = $lesson->cancelled_subscription_instance_ids ?? [];

        if ($instanceIds === [] || $instanceIds === null) {
            $instanceIds = $this->inferSubscriptionInstanceIds($lesson);
        }

        if ($instanceIds === []) {
            return;
        }

        foreach ($instanceIds as $instanceId) {
            $instance = SubscriptionInstance::query()->find($instanceId);
            if (! $instance) {
                continue;
            }

            try {
                $instance->consumeLesson($lesson->fresh());
            } catch (\Exception $e) {
                Log::warning('Réactivation : impossible de rattacher l\'abonnement', [
                    'lesson_id' => $lesson->id,
                    'subscription_instance_id' => $instanceId,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * @return array<int, int>
     */
    private function inferSubscriptionInstanceIds(Lesson $lesson): array
    {
        $studentId = $this->lessonDeletionService->resolveParticipantStudentId($lesson);
        if (! $studentId || ! $lesson->club_id) {
            return [];
        }

        $instances = SubscriptionInstance::query()
            ->whereHas('subscription', fn ($q) => $q->where('club_id', $lesson->club_id))
            ->where('status', 'active')
            ->whereHas('students', fn ($q) => $q->where('students.id', $studentId))
            ->get();

        return $instances->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
    }

    private function restoreRecurringSlotIfNeeded(Lesson $referenceLesson): void
    {
        $slot = $this->findMatchingRecurringSlot($referenceLesson);

        if ($slot && $slot->status === 'cancelled') {
            $slot->reactivate('Réactivé avec les cours annulés');
        }
    }

    public function findMatchingRecurringSlot(Lesson $lesson): ?SubscriptionRecurringSlot
    {
        $lesson->loadMissing('subscriptionInstances');
        $start = Carbon::parse($lesson->start_time);
        $dayOfWeek = $start->dayOfWeek;
        $timeStr = $start->format('H:i:s');

        $instanceIds = $lesson->cancelled_subscription_instance_ids
            ?? $lesson->subscriptionInstances->pluck('id')->all();

        if ($instanceIds === [] || ! $lesson->student_id || ! $lesson->teacher_id) {
            return null;
        }

        return SubscriptionRecurringSlot::query()
            ->whereIn('subscription_instance_id', $instanceIds)
            ->where('student_id', $lesson->student_id)
            ->where('teacher_id', $lesson->teacher_id)
            ->where('day_of_week', $dayOfWeek)
            ->whereRaw('TIME(start_time) = ?', [$timeStr])
            ->first();
    }
}
