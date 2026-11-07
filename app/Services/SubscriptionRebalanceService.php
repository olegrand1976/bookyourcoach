<?php

namespace App\Services;

use App\Models\Club;
use App\Models\ClubClosureDay;
use App\Models\Lesson;
use App\Models\LessonActionLog;
use App\Models\Student;
use App\Models\Subscription;
use App\Models\SubscriptionInstance;
use App\Models\SubscriptionRebalance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Remise en cohérence des rattachements cours ↔ abonnements d'un élève (ou d'une famille
 * partageant un carnet), selon la règle du club : les carnets se consomment l'un après l'autre.
 *
 * Pour chaque cours qui compte (passé ou réservé, hors jour de fermeture, hors séance libre),
 * dans l'ordre chronologique, le carnet retenu est le premier (début le plus ancien) qui a encore
 * une place et qui couvre le type de cours et l'élève :
 *   1. valide à la date du cours (début ≤ date ≤ expiration) ;
 *   2. sinon déjà commencé à cette date (même expiré : ses places restantes sont reprises) ;
 *   3. sinon n'importe quel carnet avec une place ;
 *   4. sinon le cours reste non couvert (à régulariser par un nouveau carnet).
 *
 * Les cours des jours de fermeture sont détachés. Les compteurs manuels sont conservés quand
 * ils sont cohérents ; ceux qui font doublon avec les cours enregistrés (dépassement de capacité)
 * ou qui provenaient d'une correction manuelle (lessons_used_reset_at) sont neutralisés.
 *
 * Chaque application enregistre l'état avant / après (SubscriptionRebalance) : annulable par élève.
 */
class SubscriptionRebalanceService
{
    private const COUNTABLE_STATUSES = ['pending', 'confirmed', 'completed'];

    public function __construct(
        private readonly LessonActionLogService $actionLogService,
    ) {}

    /**
     * Élèves du club partageant (directement ou de proche en proche) un carnet avec l'élève.
     *
     * @return list<int>
     */
    public function familyStudentIds(Club $club, int $studentId): array
    {
        $clubInstanceIds = $this->clubInstanceIdsQuery($club);
        $students = [$studentId => true];
        $queue = [$studentId];

        while ($queue !== []) {
            $instanceIds = DB::table('subscription_instance_students')
                ->whereIn('student_id', $queue)
                ->whereIn('subscription_instance_id', $clubInstanceIds)
                ->pluck('subscription_instance_id')
                ->all();
            if ($instanceIds === []) {
                break;
            }

            $queue = [];
            foreach (DB::table('subscription_instance_students')
                ->whereIn('subscription_instance_id', $instanceIds)
                ->pluck('student_id') as $sid) {
                $sid = (int) $sid;
                if (! isset($students[$sid])) {
                    $students[$sid] = true;
                    $queue[] = $sid;
                }
            }
        }

        $ids = array_map('intval', array_keys($students));
        sort($ids);

        return $ids;
    }

    /**
     * Simulation : ce que la remise en cohérence changerait (aucune écriture).
     *
     * @return array<string, mixed>
     */
    public function plan(Club $club, int $studentId): array
    {
        $studentIds = $this->familyStudentIds($club, $studentId);

        $instances = SubscriptionInstance::query()
            ->whereIn('id', $this->clubInstanceIdsQuery($club))
            ->whereHas('students', fn ($q) => $q->whereIn('students.id', $studentIds))
            ->with(['subscription.template.courseTypes', 'students:id'])
            ->get();
        $instances = $this->sortInstances($instances);
        $instanceById = $instances->keyBy('id');
        $instanceIds = $instances->pluck('id')->map(fn ($id) => (int) $id)->all();

        $lessons = $this->familyLessons($club, $studentIds, $instanceIds);
        $lessonIds = $lessons->pluck('id')->all();
        $links = $lessonIds === [] ? [] : DB::table('subscription_lessons')
            ->whereIn('lesson_id', $lessonIds)
            ->pluck('subscription_instance_id', 'lesson_id')
            ->map(fn ($id) => (int) $id)
            ->all();
        $participants = $this->participants($lessons);
        $closed = $this->closureDates($club);

        $profiles = [];
        foreach ($instances as $instance) {
            $profiles[(int) $instance->id] = $this->profile($instance);
        }

        // Cours concernés : non rattachés ou rattachés à un carnet de la famille (jamais un carnet tiers).
        $closureDetach = [];
        $countable = [];
        foreach ($lessons as $lesson) {
            $current = $links[$lesson->id] ?? null;
            if ($current !== null && ! isset($profiles[$current])) {
                continue;
            }
            $ymd = LessonCalendarDate::toYmd($lesson->start_time);
            $isClosed = $ymd !== null && isset($closed[$ymd]);

            if ($isClosed) {
                if ($current !== null) {
                    $closureDetach[] = ['lesson_id' => (int) $lesson->id, 'date' => $ymd, 'from' => $current];
                }

                continue;
            }
            if ($lesson->deduct_from_subscription === false) {
                continue;
            }
            $counts = in_array($lesson->status, self::COUNTABLE_STATUSES, true)
                || ($lesson->status === 'cancelled' && (bool) $lesson->cancellation_count_in_subscription);
            if (! $counts) {
                continue;
            }
            $countable[] = ['lesson' => $lesson, 'ymd' => $ymd, 'current' => $current];
        }

        usort($countable, fn ($a, $b) => [Carbon::parse($a['lesson']->start_time)->getTimestamp(), $a['lesson']->id]
            <=> [Carbon::parse($b['lesson']->start_time)->getTimestamp(), $b['lesson']->id]);

        // Compteurs manuels : conservés s'ils restent cohérents avec les cours enregistrés.
        $linkedCountable = [];
        foreach ($countable as $row) {
            if ($row['current'] !== null) {
                $linkedCountable[$row['current']] = ($linkedCountable[$row['current']] ?? 0) + 1;
            }
        }
        $left = [];
        foreach ($profiles as $id => &$p) {
            $p['linked_before'] = $linkedCountable[$id] ?? 0;
            if ($p['reset_at'] !== null) {
                $p['manual_after'] = 0;
                $p['manual_reason'] = 'correction_manuelle';
            } elseif ($p['manual'] > 0 && $p['capacity'] > 0 && $p['capacity'] < $p['manual'] + $p['linked_before']) {
                $p['manual_after'] = 0;
                $p['manual_reason'] = 'doublon';
            } else {
                $p['manual_after'] = $p['manual'];
                $p['manual_reason'] = null;
            }
            $left[$id] = $p['capacity'] > 0 ? $p['capacity'] - $p['manual_after'] : PHP_INT_MAX;
            $p['linked_after'] = 0;
        }
        unset($p);

        $moves = [];
        $uncovered = [];
        $targets = [];
        foreach ($countable as $row) {
            $lesson = $row['lesson'];
            $target = $this->pickInstance($lesson, $row['ymd'], $instanceById, $profiles, $left, $participants[$lesson->id] ?? []);
            if ($target !== null) {
                $left[$target] = $left[$target] === PHP_INT_MAX ? PHP_INT_MAX : $left[$target] - 1;
                $profiles[$target]['linked_after']++;
            } else {
                $uncovered[] = ['lesson_id' => (int) $lesson->id, 'date' => $row['ymd'], 'from' => $row['current']];
            }
            $targets[$lesson->id] = $target;
            if ($target !== $row['current']) {
                $moves[] = ['lesson_id' => (int) $lesson->id, 'date' => $row['ymd'], 'from' => $row['current'], 'to' => $target];
            }
        }

        $manualChanges = array_values(array_filter($profiles, fn ($p) => $p['manual_after'] !== $p['manual'] || $p['reset_at'] !== null));

        return [
            'student_ids' => $studentIds,
            'students' => Student::query()->whereIn('id', $studentIds)->with('user:id,name')->get()
                ->map(fn (Student $s) => ['id' => (int) $s->id, 'name' => $this->studentName($s)])->values()->all(),
            'instances' => array_values(array_map(fn ($p) => [
                'id' => $p['id'],
                'number' => $p['number'],
                'name' => $p['name'],
                'status' => $p['status'],
                'started_at' => $p['start'],
                'expires_at' => $p['end'],
                'capacity' => $p['capacity'],
                'manual_before' => $p['manual'],
                'manual_after' => $p['manual_after'],
                'manual_reason' => $p['manual_reason'],
                'lessons_used_before' => $p['lessons_used'],
                'linked_before' => $p['linked_before'],
                'linked_after' => $p['linked_after'],
            ], $profiles)),
            'moves' => $moves,
            'closure_detach' => $closureDetach,
            'uncovered' => $uncovered,
            'manual_changes' => count($manualChanges),
            'has_changes' => $moves !== [] || $closureDetach !== [] || $manualChanges !== [],
            'targets' => $targets,
        ];
    }

    /**
     * Applique la remise en cohérence (transaction) et enregistre l'état avant pour annulation.
     */
    public function apply(Club $club, int $studentId, ?User $actor = null): ?SubscriptionRebalance
    {
        return DB::transaction(function () use ($club, $studentId, $actor) {
            $plan = $this->plan($club, $studentId);
            if (! $plan['has_changes']) {
                return null;
            }

            $instanceIds = array_map(fn ($i) => (int) $i['id'], $plan['instances']);
            $changedLinks = [];
            foreach ($plan['moves'] as $move) {
                $changedLinks[$move['lesson_id']] = $move['to'];
            }
            foreach ($plan['closure_detach'] as $row) {
                $changedLinks[$row['lesson_id']] = null;
            }

            $before = [
                'links' => $this->currentLinks(array_keys($changedLinks)),
                'instances' => $this->instanceSnapshot($instanceIds),
            ];

            foreach ($changedLinks as $lessonId => $target) {
                DB::table('subscription_lessons')->where('lesson_id', $lessonId)->delete();
                if ($target !== null) {
                    DB::table('subscription_lessons')->insert([
                        'subscription_instance_id' => $target,
                        'lesson_id' => $lessonId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            $manualAfter = [];
            foreach ($plan['instances'] as $row) {
                $manualAfter[(int) $row['id']] = (int) $row['manual_after'];
            }
            foreach (SubscriptionInstance::query()->whereIn('id', $instanceIds)->get() as $instance) {
                if ($instance->lessons_used_reset_at !== null || (int) $instance->manual_lessons_used !== $manualAfter[$instance->id]) {
                    $instance->forceFill([
                        'manual_lessons_used' => $manualAfter[$instance->id],
                        'lessons_used_reset_at' => null,
                    ])->saveQuietly();
                }
                $instance->unsetRelation('lessons');
                $instance->recalculateLessonsUsed();
                $instance->refresh();
                $instance->checkAndUpdateStatus();
            }

            $after = [
                'links' => $this->currentLinks(array_keys($changedLinks)),
                'instances' => $this->instanceSnapshot($instanceIds),
            ];

            $rebalance = SubscriptionRebalance::create([
                'club_id' => $club->id,
                'student_ids' => $plan['student_ids'],
                'instance_ids' => $instanceIds,
                'before' => $before,
                'after' => $after,
                'summary' => [
                    'moved' => count($plan['moves']),
                    'closure_detached' => count($plan['closure_detach']),
                    'uncovered' => count($plan['uncovered']),
                    'manual_changes' => $plan['manual_changes'],
                ],
                'applied_by' => $actor?->id,
            ]);

            foreach ($changedLinks as $lessonId => $target) {
                $this->logLink($club, $actor, (int) $lessonId, $before['links'][$lessonId] ?? null, $target, 'rebalance', $rebalance->id);
            }

            Log::info('Remise en cohérence des abonnements appliquée', [
                'club_id' => $club->id,
                'rebalance_id' => $rebalance->id,
                'student_ids' => $plan['student_ids'],
                'summary' => $rebalance->summary,
            ]);

            return $rebalance;
        });
    }

    /**
     * Annule une remise en cohérence : liens et compteurs reviennent exactement à l'état d'avant.
     * Seule la dernière correction non annulée d'un élève peut l'être (sinon l'état « avant » est périmé).
     */
    public function revert(SubscriptionRebalance $rebalance, ?User $actor = null): void
    {
        if ($rebalance->reverted_at !== null) {
            throw new \RuntimeException('Cette correction a déjà été annulée.');
        }

        $newer = SubscriptionRebalance::query()
            ->where('club_id', $rebalance->club_id)
            ->where('id', '>', $rebalance->id)
            ->whereNull('reverted_at')
            ->get()
            ->first(fn (SubscriptionRebalance $r) => $r->concernsAnyStudent($rebalance->student_ids ?? []));
        if ($newer) {
            throw new \RuntimeException('Une correction plus récente existe pour cet élève : annulez-la d\'abord.');
        }

        DB::transaction(function () use ($rebalance, $actor) {
            $club = Club::find($rebalance->club_id);
            $instanceIds = array_map('intval', $rebalance->instance_ids ?? []);
            $before = $rebalance->before ?? [];
            $current = $this->currentLinks(array_map('intval', array_keys($before['links'] ?? [])));

            foreach ($before['links'] ?? [] as $lessonId => $instanceId) {
                $lessonId = (int) $lessonId;
                DB::table('subscription_lessons')->where('lesson_id', $lessonId)->delete();
                if ($instanceId !== null
                    && in_array((int) $instanceId, $instanceIds, true)
                    && Lesson::withTrashed()->whereKey($lessonId)->exists()) {
                    DB::table('subscription_lessons')->insert([
                        'subscription_instance_id' => (int) $instanceId,
                        'lesson_id' => $lessonId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
                if ($club) {
                    $this->logLink($club, $actor, $lessonId, $current[$lessonId] ?? null, $instanceId !== null ? (int) $instanceId : null, 'rebalance_reverted', $rebalance->id);
                }
            }

            foreach ($before['instances'] ?? [] as $instanceId => $fields) {
                SubscriptionInstance::query()->whereKey((int) $instanceId)->update([
                    'lessons_used' => $fields['lessons_used'],
                    'manual_lessons_used' => $fields['manual_lessons_used'],
                    'lessons_used_reset_at' => $fields['lessons_used_reset_at'],
                    'status' => $fields['status'],
                ]);
            }

            $rebalance->forceFill(['reverted_at' => now(), 'reverted_by' => $actor?->id])->save();
        });
    }

    /**
     * Contrôle du club : élèves (familles) dont les rattachements ne sont pas cohérents.
     *
     * @return list<array<string, mixed>>
     */
    public function audit(Club $club): array
    {
        $studentIds = DB::table('subscription_instance_students')
            ->whereIn('subscription_instance_id', $this->clubInstanceIdsQuery($club))
            ->distinct()
            ->orderBy('student_id')
            ->pluck('student_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $seen = [];
        $rows = [];
        foreach ($studentIds as $studentId) {
            if (isset($seen[$studentId])) {
                continue;
            }
            $plan = $this->plan($club, $studentId);
            foreach ($plan['student_ids'] as $sid) {
                $seen[$sid] = true;
            }
            if (! $plan['has_changes'] && $plan['uncovered'] === []) {
                continue;
            }
            $rows[] = [
                'student_ids' => $plan['student_ids'],
                'students' => $plan['students'],
                'moved' => count($plan['moves']),
                'closure_detached' => count($plan['closure_detach']),
                'uncovered' => count($plan['uncovered']),
                'manual_changes' => $plan['manual_changes'],
                'has_changes' => $plan['has_changes'],
            ];
        }

        return $rows;
    }

    /** @return Collection<int, SubscriptionRebalance> */
    public function historyForStudent(Club $club, int $studentId): Collection
    {
        return SubscriptionRebalance::query()
            ->where('club_id', $club->id)
            ->with(['appliedBy:id,name', 'revertedBy:id,name'])
            ->orderByDesc('id')
            ->limit(50)
            ->get()
            ->filter(fn (SubscriptionRebalance $r) => $r->concernsAnyStudent([$studentId]))
            ->values();
    }

    /**
     * @param  Collection<int, SubscriptionInstance>  $instanceById
     * @param  array<int, array<string, mixed>>  $profiles
     * @param  array<int, int>  $left
     * @param  list<int>  $participantIds
     */
    private function pickInstance(Lesson $lesson, ?string $ymd, Collection $instanceById, array $profiles, array $left, array $participantIds): ?int
    {
        $candidates = [];
        foreach ($profiles as $id => $p) {
            if (($left[$id] ?? 0) <= 0) {
                continue;
            }
            if ($p['course_type_ids'] !== [] && ! in_array((int) $lesson->course_type_id, $p['course_type_ids'], true)) {
                continue;
            }
            if ($p['student_ids'] !== [] && array_intersect($participantIds, $p['student_ids']) === []) {
                continue;
            }
            $candidates[] = $p;
        }

        foreach ($candidates as $p) {
            if ($ymd !== null && ($p['start'] === null || $p['start'] <= $ymd) && ($p['end'] === null || $ymd <= $p['end'])) {
                return $p['id'];
            }
        }
        foreach ($candidates as $p) {
            if ($ymd !== null && ($p['start'] === null || $p['start'] <= $ymd)) {
                return $p['id'];
            }
        }

        return $candidates[0]['id'] ?? null;
    }

    /** @return array<string, mixed> */
    private function profile(SubscriptionInstance $instance): array
    {
        $template = $instance->subscription?->template;

        return [
            'id' => (int) $instance->id,
            'number' => $instance->subscription?->subscription_number,
            'name' => $template?->model_number ?? $instance->subscription?->name,
            'status' => $instance->status,
            'start' => LessonCalendarDate::toYmd($instance->started_at ?? $instance->created_at),
            'end' => $instance->expires_at ? LessonCalendarDate::toYmd($instance->expires_at) : null,
            'capacity' => $instance->getTotalCapacity(),
            'manual' => (int) ($instance->manual_lessons_used ?? 0),
            'reset_at' => $instance->lessons_used_reset_at?->toIso8601String(),
            'lessons_used' => (int) $instance->lessons_used,
            'course_type_ids' => $template?->courseTypes?->pluck('id')->map(fn ($id) => (int) $id)->all() ?? [],
            'student_ids' => $instance->students->pluck('id')->map(fn ($id) => (int) $id)->all(),
        ];
    }

    /**
     * @param  Collection<int, SubscriptionInstance>  $instances
     * @return Collection<int, SubscriptionInstance>
     */
    private function sortInstances(Collection $instances): Collection
    {
        return $instances->sort(function (SubscriptionInstance $a, SubscriptionInstance $b) {
            $sa = Carbon::parse($a->started_at ?? $a->created_at)->getTimestamp();
            $sb = Carbon::parse($b->started_at ?? $b->created_at)->getTimestamp();

            return [$sa, $a->created_at?->getTimestamp() ?? 0, (int) $a->id]
                <=> [$sb, $b->created_at?->getTimestamp() ?? 0, (int) $b->id];
        })->values();
    }

    /**
     * Cours de la famille dans ce club (élève principal ou participant), plus ceux déjà rattachés
     * à un carnet de la famille.
     *
     * @param  list<int>  $studentIds
     * @param  list<int>  $instanceIds
     * @return Collection<int, Lesson>
     */
    private function familyLessons(Club $club, array $studentIds, array $instanceIds): Collection
    {
        $linked = $instanceIds === [] ? collect() : DB::table('subscription_lessons')
            ->whereIn('subscription_instance_id', $instanceIds)
            ->pluck('lesson_id');
        $participating = DB::table('lesson_student')->whereIn('student_id', $studentIds)->pluck('lesson_id');

        return Lesson::query()
            ->where(function ($q) use ($club, $studentIds, $participating) {
                $q->where(fn ($q2) => $q2->where('club_id', $club->id)->orWhereNull('club_id'))
                    ->where(fn ($q2) => $q2->whereIn('student_id', $studentIds)->orWhereIn('id', $participating));
            })
            ->orWhereIn('id', $linked)
            ->get(['id', 'club_id', 'student_id', 'course_type_id', 'start_time', 'status', 'deduct_from_subscription', 'cancellation_count_in_subscription']);
    }

    /**
     * @param  Collection<int, Lesson>  $lessons
     * @return array<int, list<int>>
     */
    private function participants(Collection $lessons): array
    {
        $out = [];
        foreach ($lessons as $lesson) {
            $out[$lesson->id] = $lesson->student_id ? [(int) $lesson->student_id] : [];
        }
        if ($out !== []) {
            foreach (DB::table('lesson_student')->whereIn('lesson_id', array_keys($out))->get(['lesson_id', 'student_id']) as $row) {
                $out[$row->lesson_id][] = (int) $row->student_id;
            }
        }

        return $out;
    }

    /** @return array<string, true> */
    private function closureDates(Club $club): array
    {
        return ClubClosureDay::query()
            ->where('club_id', $club->id)
            ->get(['closed_on'])
            ->mapWithKeys(fn (ClubClosureDay $d) => [Carbon::parse($d->closed_on)->format('Y-m-d') => true])
            ->all();
    }

    /**
     * @param  list<int>  $lessonIds
     * @return array<int, int|null>
     */
    private function currentLinks(array $lessonIds): array
    {
        $links = array_fill_keys($lessonIds, null);
        if ($lessonIds !== []) {
            foreach (DB::table('subscription_lessons')->whereIn('lesson_id', $lessonIds)->get(['lesson_id', 'subscription_instance_id']) as $row) {
                $links[(int) $row->lesson_id] = (int) $row->subscription_instance_id;
            }
        }

        return $links;
    }

    /**
     * @param  list<int>  $instanceIds
     * @return array<int, array<string, mixed>>
     */
    private function instanceSnapshot(array $instanceIds): array
    {
        return SubscriptionInstance::query()
            ->whereIn('id', $instanceIds)
            ->get(['id', 'lessons_used', 'manual_lessons_used', 'lessons_used_reset_at', 'status'])
            ->mapWithKeys(fn (SubscriptionInstance $i) => [(int) $i->id => [
                'lessons_used' => (int) $i->lessons_used,
                'manual_lessons_used' => (int) ($i->manual_lessons_used ?? 0),
                'lessons_used_reset_at' => $i->getRawOriginal('lessons_used_reset_at'),
                'status' => $i->status,
            ]])
            ->all();
    }

    private function clubInstanceIdsQuery(Club $club)
    {
        return SubscriptionInstance::query()
            ->whereIn('subscription_id', Subscription::forClub($club->id)->select('id'))
            ->select('id');
    }

    private function studentName(Student $student): string
    {
        return $student->name ?: 'Élève #'.$student->id;
    }

    private function logLink(Club $club, ?User $actor, int $lessonId, ?int $from, ?int $to, string $reason, int $rebalanceId): void
    {
        try {
            if ($from !== null) {
                $this->actionLogService->logForClub($club->id, LessonActionLog::ACTION_SUBSCRIPTION_UNLINKED, $actor, $actor?->role, $lessonId, null, $from, ['reason' => $reason, 'rebalance_id' => $rebalanceId]);
            }
            if ($to !== null) {
                $this->actionLogService->logForClub($club->id, LessonActionLog::ACTION_SUBSCRIPTION_LINKED, $actor, $actor?->role, $lessonId, null, $to, ['reason' => $reason, 'rebalance_id' => $rebalanceId]);
            }
        } catch (\Throwable $e) {
            Log::warning('Remise en cohérence : journalisation impossible', ['lesson_id' => $lessonId, 'error' => $e->getMessage()]);
        }
    }
}
