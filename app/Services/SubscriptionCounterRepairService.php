<?php

namespace App\Services;

use App\Models\Club;
use App\Models\LessonActionLog;
use App\Models\Subscription;
use App\Models\SubscriptionInstance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Vérification des compteurs d'abonnements d'un club : simulation sans écriture,
 * puis application aux seuls carnets validés par le club.
 */
class SubscriptionCounterRepairService
{
    public function __construct(
        private readonly ClubClosureDayService $closureDayService,
        private readonly LessonActionLogService $actionLogService,
    ) {}

    /**
     * Carnets du club dont le compteur changerait, ou qui ont des cours futurs en excédent,
     * ou dont les cours passés dépassent déjà le plafond (alerte, aucune correction automatique).
     *
     * @return array{items: list<array<string, mixed>>, summary: array<string, int>}
     */
    public function preview(Club $club): array
    {
        $items = [];
        $instances = $this->instancesForClub($club);

        foreach ($instances as $instance) {
            $item = $this->describe($instance);
            if ($item['changes_counter'] || $item['future_excess_lessons'] !== [] || $item['past_overflow']) {
                $items[] = $item;
            }
        }

        return [
            'items' => $items,
            'summary' => [
                'checked' => $instances->count(),
                'counter_changes' => count(array_filter($items, fn ($i) => $i['changes_counter'])),
                'with_future_excess' => count(array_filter($items, fn ($i) => $i['future_excess_lessons'] !== [])),
                'past_overflow' => count(array_filter($items, fn ($i) => $i['past_overflow'])),
            ],
        ];
    }

    /**
     * Recalcule les carnets choisis ; les cours futurs en excédent ne sont détachés
     * que pour les carnets explicitement listés dans $detachExcessFor.
     *
     * @param  list<int>  $instanceIds
     * @param  list<int>  $detachExcessFor
     * @return list<array{instance_id: int, subscription_number: ?string, old_lessons_used: int, new_lessons_used: int, detached_lesson_ids: list<int>}>
     */
    public function apply(Club $club, array $instanceIds, array $detachExcessFor, ?User $actor = null): array
    {
        $detachSet = array_fill_keys(array_map('intval', $detachExcessFor), true);
        $results = [];

        DB::transaction(function () use ($club, $instanceIds, $detachSet, $actor, &$results) {
            $instances = $this->instancesForClub($club)->whereIn('id', array_map('intval', $instanceIds));

            foreach ($instances as $instance) {
                $oldLessonsUsed = (int) $instance->lessons_used;
                $detached = [];

                if (isset($detachSet[$instance->id])) {
                    $detached = $this->futureExcessLessons($instance)->pluck('id')->all();
                    if ($detached !== []) {
                        $instance->lessons()->detach($detached);
                        foreach ($detached as $lessonId) {
                            $this->actionLogService->logForClub(
                                (int) $club->id,
                                LessonActionLog::ACTION_SUBSCRIPTION_UNLINKED,
                                $actor,
                                $actor?->role,
                                (int) $lessonId,
                                null,
                                (int) $instance->id,
                                ['reason' => 'counter_repair_future_excess'],
                            );
                        }
                    }
                }

                $instance->refresh();
                $instance->recalculateLessonsUsed();
                $instance->checkAndUpdateStatus();

                $results[] = [
                    'instance_id' => (int) $instance->id,
                    'subscription_number' => $instance->subscription?->subscription_number,
                    'old_lessons_used' => $oldLessonsUsed,
                    'new_lessons_used' => (int) $instance->lessons_used,
                    'detached_lesson_ids' => array_map('intval', $detached),
                ];
            }
        });

        Log::info('Compteurs d\'abonnements corrigés', [
            'club_id' => $club->id,
            'performed_by_user_id' => $actor?->id,
            'results' => $results,
        ]);

        return $results;
    }

    /**
     * Cours futurs rattachés au-delà de la capacité restante, les plus lointains d'abord.
     * Capacité inconnue (carnet legacy sans modèle) : rien n'est considéré en excédent.
     *
     * @return Collection<int, object{id: int, start_time: string}>
     */
    public function futureExcessLessons(SubscriptionInstance $instance): Collection
    {
        $instance->loadMissing('subscription.template');
        $capacity = (int) ($instance->subscription?->total_available_lessons ?? 0);
        if ($capacity <= 0) {
            return collect();
        }

        $maxAttached = max(0, $capacity - max(0, (int) ($instance->manual_lessons_used ?? 0)));
        $excess = $instance->getAttachedCountableLessonsCount() - $maxAttached;
        if ($excess <= 0) {
            return collect();
        }

        return DB::table('subscription_lessons')
            ->join('lessons', 'subscription_lessons.lesson_id', '=', 'lessons.id')
            ->where('subscription_lessons.subscription_instance_id', $instance->id)
            ->whereNull('lessons.deleted_at')
            ->whereIn('lessons.status', ['pending', 'confirmed', 'completed'])
            ->where('lessons.start_time', '>', Carbon::now())
            ->orderByDesc('lessons.start_time')
            ->limit($excess)
            ->get(['lessons.id', 'lessons.start_time'])
            ->map(fn ($row) => (object) ['id' => (int) $row->id, 'start_time' => (string) $row->start_time]);
    }

    /**
     * @return array<string, mixed>
     */
    private function describe(SubscriptionInstance $instance): array
    {
        $current = (int) $instance->lessons_used;
        $manual = max(0, (int) ($instance->manual_lessons_used ?? 0));
        $recalculated = $manual + $instance->getConsumedLessonsCount();
        $overflow = $instance->getPastOverflowInfo();

        return [
            'instance_id' => (int) $instance->id,
            'subscription_number' => $instance->subscription?->subscription_number,
            'status' => $instance->status,
            'students' => $instance->students
                ->map(fn ($s) => $s->name)
                ->filter()
                ->values()
                ->all(),
            'capacity' => (int) $overflow['capacity'],
            'manual_lessons_used' => $manual,
            'current_lessons_used' => $current,
            'recalculated_lessons_used' => $recalculated,
            'changes_counter' => $current !== $recalculated,
            'closure_day_lessons' => $this->closureDayLessons($instance),
            'future_excess_lessons' => $this->futureExcessLessons($instance)
                ->map(fn ($l) => ['id' => $l->id, 'start_time' => $l->start_time])
                ->values()
                ->all(),
            // Alerte réservée aux carnets actifs : l'historique des carnets clos ne se corrige plus.
            'past_overflow' => $instance->status === 'active' && $overflow['past_exceeds_capacity'],
        ];
    }

    /**
     * Cours rattachés tombant un jour de congé club : ils ne sont plus décomptés.
     *
     * @return list<array{id: int, start_time: string}>
     */
    private function closureDayLessons(SubscriptionInstance $instance): array
    {
        $query = DB::table('subscription_lessons')
            ->join('lessons', 'subscription_lessons.lesson_id', '=', 'lessons.id')
            ->where('subscription_lessons.subscription_instance_id', $instance->id)
            ->whereNull('lessons.deleted_at')
            ->whereIn('lessons.status', ['pending', 'confirmed', 'completed'])
            ->when($instance->lessons_used_reset_at, fn ($q, $resetAt) => $q->where('lessons.start_time', '>', $resetAt));
        $this->closureDayService->includeOnlyClosedDaysFromQuery($query);

        return $query->orderBy('lessons.start_time')
            ->get(['lessons.id', 'lessons.start_time'])
            ->map(fn ($row) => ['id' => (int) $row->id, 'start_time' => (string) $row->start_time])
            ->all();
    }

    /**
     * @return Collection<int, SubscriptionInstance>
     */
    private function instancesForClub(Club $club): Collection
    {
        return SubscriptionInstance::query()
            ->whereIn('subscription_id', Subscription::forClub($club->id)->select('id'))
            ->with(['subscription.template', 'students.user'])
            ->orderBy('id')
            ->get();
    }
}
