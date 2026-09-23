<?php

namespace App\Services;

use App\Jobs\NotifyClubClosureRecipientsJob;
use App\Models\Club;
use App\Models\ClubClosureDay;
use App\Models\Lesson;
use App\Models\LessonActionLog;
use App\Models\SubscriptionInstance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ClubClosureDayService
{
    /** Cache du contrôle de schéma (voir hasAuditColumns()). */
    private static ?bool $hasAuditColumns = null;

    /**
     * Impact d'une fermeture sur une date : ce que le client doit connaître avant d'agir.
     * Comptage strictement aligné sur {@see closeDay()} — mêmes statuts, même règle de date.
     *
     * @return array{date: string, already_closed: bool, lessons_count: int, subscription_links_count: int, students_count: int, teachers_count: int, recipients_count: int}
     */
    public function impactFor(Club $club, string $dateYmd): array
    {
        $lessons = $this->lessonsToDetachQuery((int) $club->id, $dateYmd)
            ->with(['students:id', 'student:id', 'teacher:id'])
            ->get();

        $lessonIds = $lessons->pluck('id')->all();

        $subscriptionLinks = $lessonIds === [] ? 0 : DB::table('subscription_lessons')
            ->whereIn('lesson_id', $lessonIds)
            ->count();

        $studentIds = [];
        $teacherIds = [];
        foreach ($lessons as $lesson) {
            if ($lesson->student_id) {
                $studentIds[$lesson->student_id] = true;
            }
            foreach ($lesson->students as $student) {
                $studentIds[$student->id] = true;
            }
            if ($lesson->teacher_id) {
                $teacherIds[$lesson->teacher_id] = true;
            }
        }

        return [
            'date' => $dateYmd,
            'already_closed' => ClubClosureDay::clubIsClosedOn((int) $club->id, $dateYmd),
            'lessons_count' => $lessons->count(),
            'subscription_links_count' => $subscriptionLinks,
            'students_count' => count($studentIds),
            'teachers_count' => count($teacherIds),
            'recipients_count' => count($studentIds) + count($teacherIds),
        ];
    }

    /**
     * Mark a calendar day as closed: persist row, detach subscription links for lessons that day, recalculate usage.
     * Idempotent: if the day was already closed, does nothing (no duplicate notifications or detach passes).
     */
    public function closeDay(Club $club, string $dateYmd, ?User $actor = null): array
    {
        $notify = false;
        $detachedLinks = [];

        DB::transaction(function () use ($club, $dateYmd, $actor, &$notify, &$detachedLinks) {
            $closure = ClubClosureDay::firstOrCreate(
                [
                    'club_id' => $club->id,
                    'closed_on' => $dateYmd,
                ],
                $this->auditAttributes(['closed_by_user_id' => $actor?->id])
            );

            if (!$closure->wasRecentlyCreated) {
                Log::info('Club closure: day already closed, skip', [
                    'club_id' => $club->id,
                    'closed_on' => $dateYmd,
                ]);

                return;
            }

            $notify = true;

            $lessons = $this->lessonsToDetachQuery((int) $club->id, $dateYmd)->get();
            $instanceIds = [];

            foreach ($lessons as $lesson) {
                $instances = SubscriptionInstance::query()
                    ->whereHas('lessons', function ($q) use ($lesson) {
                        $q->where('lesson_id', $lesson->id);
                    })
                    ->get();

                foreach ($instances as $instance) {
                    $instance->lessons()->detach($lesson->id);
                    $instanceIds[$instance->id] = $instance->id;
                    $detachedLinks[] = [
                        'lesson_id' => (int) $lesson->id,
                        'subscription_instance_id' => (int) $instance->id,
                    ];

                    $this->logSubscriptionLink(
                        (int) $club->id,
                        LessonActionLog::ACTION_SUBSCRIPTION_UNLINKED,
                        $actor,
                        (int) $lesson->id,
                        (int) $instance->id,
                        ['reason' => 'club_closure', 'closure_date' => $dateYmd],
                    );

                    Log::info('Club closure: lesson detached from subscription instance', [
                        'club_id' => $club->id,
                        'closed_on' => $dateYmd,
                        'lesson_id' => $lesson->id,
                        'subscription_instance_id' => $instance->id,
                    ]);
                }
            }

            $this->recalculateInstances($instanceIds);

            // Le relevé permet à la réouverture de restaurer exactement ce qui a été défait.
            if ($detachedLinks !== [] && $this->hasAuditColumns()) {
                $closure->detached_links = $detachedLinks;
                $closure->save();
            }
        });

        if ($notify) {
            NotifyClubClosureRecipientsJob::dispatch($club->id, $dateYmd, 'closed');
        }

        return [
            'notified' => $notify,
            'detached_links' => count($detachedLinks),
        ];
    }

    /**
     * Rouvre une journée et restaure les liens d'abonnement que la fermeture avait défaits.
     *
     * @return array{reopened: bool, restored_links: int, skipped_links: int}
     */
    public function openDay(Club $club, string $dateYmd, ?User $actor = null): array
    {
        $result = ['reopened' => false, 'restored_links' => 0, 'skipped_links' => 0];

        DB::transaction(function () use ($club, $dateYmd, $actor, &$result) {
            $closure = ClubClosureDay::query()
                ->where('club_id', $club->id)
                ->whereDate('closed_on', $dateYmd)
                ->first();

            if (! $closure) {
                return;
            }

            $result['reopened'] = true;
            $links = $this->hasAuditColumns() ? ($closure->detached_links ?? []) : [];
            $instanceIds = [];

            foreach ($links as $link) {
                $lessonId = (int) ($link['lesson_id'] ?? 0);
                $instanceId = (int) ($link['subscription_instance_id'] ?? 0);

                if (! $this->canRestoreLink($club, $lessonId, $instanceId)) {
                    $result['skipped_links']++;

                    continue;
                }

                $instance = SubscriptionInstance::find($instanceId);
                $instance->lessons()->syncWithoutDetaching([$lessonId]);
                $instanceIds[$instanceId] = $instanceId;
                $result['restored_links']++;

                $this->logSubscriptionLink(
                    (int) $club->id,
                    LessonActionLog::ACTION_SUBSCRIPTION_LINKED,
                    $actor,
                    $lessonId,
                    $instanceId,
                    ['reason' => 'closure_reopened', 'closure_date' => $dateYmd],
                );
            }

            $this->recalculateInstances($instanceIds);

            $closure->delete();
        });

        if ($result['reopened']) {
            NotifyClubClosureRecipientsJob::dispatch($club->id, $dateYmd, 'reopened');
        }

        return $result;
    }

    /**
     * Un lien n'est restauré que si le cours et le carnet existent encore et relèvent
     * bien du club : entre la fermeture et la réouverture, les données ont pu bouger.
     */
    private function canRestoreLink(Club $club, int $lessonId, int $instanceId): bool
    {
        if ($lessonId <= 0 || $instanceId <= 0) {
            return false;
        }

        $lesson = Lesson::find($lessonId);
        if (! $lesson || (int) $lesson->club_id !== (int) $club->id) {
            Log::warning('Club closure: lien non restauré (cours absent ou hors club)', [
                'club_id' => $club->id,
                'lesson_id' => $lessonId,
            ]);

            return false;
        }

        $instance = SubscriptionInstance::with('subscription')->find($instanceId);
        if (! $instance || (int) ($instance->subscription?->club_id) !== (int) $club->id) {
            Log::warning('Club closure: lien non restauré (carnet absent ou hors club)', [
                'club_id' => $club->id,
                'subscription_instance_id' => $instanceId,
            ]);

            return false;
        }

        return true;
    }

    /**
     * Cours impactés par une fermeture : même filtre pour l'aperçu et pour l'action.
     */
    private function lessonsToDetachQuery(int $clubId, string $dateYmd): Builder
    {
        $query = Lesson::query()
            ->where('club_id', $clubId)
            ->whereIn('status', ['pending', 'confirmed', 'completed']);
        LessonCalendarDate::whereOnCalendarDate($query, 'start_time', $dateYmd);

        return $query;
    }

    /**
     * Un seul recalcul par carnet : recalculateLessonsUsed() écrit et journalise à chaque appel.
     *
     * @param  array<int, int>  $instanceIds
     */
    private function recalculateInstances(array $instanceIds): void
    {
        foreach ($instanceIds as $instanceId) {
            $instance = SubscriptionInstance::find($instanceId);
            if (! $instance) {
                continue;
            }

            $instance->recalculateLessonsUsed();
            $instance->checkAndUpdateStatus();
        }
    }

    private function logSubscriptionLink(
        int $clubId,
        string $action,
        ?User $actor,
        int $lessonId,
        int $instanceId,
        array $meta,
    ): void {
        try {
            app(LessonActionLogService::class)->logForClub(
                $clubId,
                $action,
                $actor,
                $actor?->role,
                $lessonId,
                null,
                $instanceId,
                $meta,
            );
        } catch (\Throwable $e) {
            // Le journal d'audit ne doit jamais faire échouer l'action métier.
            Log::warning('Club closure: journalisation du lien abonnement impossible', [
                'lesson_id' => $lessonId,
                'subscription_instance_id' => $instanceId,
                'exception' => $e->getMessage(),
            ]);
        }
    }

    /**
     * L'API est déployée avant le job de migration : pendant cette fenêtre, le nouveau
     * code tourne sur l'ancien schéma. Résultat mis en cache pour ne pas interroger
     * le schéma à chaque appel.
     */
    private function hasAuditColumns(): bool
    {
        if (self::$hasAuditColumns === null) {
            self::$hasAuditColumns = Schema::hasColumn('club_closure_days', 'detached_links')
                && Schema::hasColumn('club_closure_days', 'closed_by_user_id');
        }

        return self::$hasAuditColumns;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function auditAttributes(array $attributes): array
    {
        return $this->hasAuditColumns() ? $attributes : [];
    }

    public function shouldSkipSubscriptionConsumption(Lesson $lesson): bool
    {
        return $this->isLessonOnClosureDay($lesson);
    }

    public function isLessonOnClosureDay(Lesson $lesson): bool
    {
        $clubId = $lesson->club_id;
        if (! $clubId || ! $lesson->start_time) {
            return false;
        }

        $ymd = $this->lessonStartDateYmd($lesson);

        return $ymd !== null && ClubClosureDay::clubIsClosedOn((int) $clubId, $ymd);
    }

    /**
     * Exclut les cours tombant un jour de fermeture club (même club_id, même règle date que closeDay).
     */
    public function excludeClosedDaysFromQuery(Builder $query, string $lessonsTable = 'lessons'): void
    {
        $this->applyClosureDayExistsSubquery($query, $lessonsTable, false);
    }

    /**
     * Ne garde que les cours tombant un jour de fermeture club.
     */
    public function includeOnlyClosedDaysFromQuery(Builder $query, string $lessonsTable = 'lessons'): void
    {
        $this->applyClosureDayExistsSubquery($query, $lessonsTable, true);
    }

    private function applyClosureDayExistsSubquery(Builder $query, string $lessonsTable, bool $match): void
    {
        $lessonDateSql = LessonCalendarDate::sqlDateExpression(
            "{$lessonsTable}.start_time",
            $query->getConnection()
        );

        $method = $match ? 'whereExists' : 'whereNotExists';

        $query->{$method}(function ($sub) use ($lessonsTable, $lessonDateSql) {
            $sub->select(DB::raw(1))
                ->from('club_closure_days')
                ->whereColumn('club_closure_days.club_id', "{$lessonsTable}.club_id")
                ->whereRaw("{$lessonDateSql} = DATE(club_closure_days.closed_on)");
        });
    }

    /**
     * @param  Collection<int, Lesson>  $lessons
     * @return Collection<int, Lesson>
     */
    public function flagLessonsOnClosureDays(Collection $lessons): Collection
    {
        if ($lessons->isEmpty()) {
            return $lessons;
        }

        $clubIds = $lessons->pluck('club_id')->filter()->unique()->values()->all();

        $closureDatesByClub = ClubClosureDay::query()
            ->whereIn('club_id', $clubIds)
            ->get()
            ->groupBy('club_id')
            ->map(fn (Collection $rows) => $rows
                ->mapWithKeys(fn (ClubClosureDay $row) => [
                    Carbon::parse($row->closed_on)->toDateString() => true,
                ]));

        return $lessons->each(function (Lesson $lesson) use ($closureDatesByClub) {
            $ymd = $this->lessonStartDateYmd($lesson);
            $clubDates = $closureDatesByClub->get((int) $lesson->club_id);
            $lesson->is_on_closure_day = $ymd !== null
                && $clubDates instanceof Collection
                && $clubDates->has($ymd);
        });
    }

    private function lessonStartDateYmd(Lesson $lesson): ?string
    {
        return LessonCalendarDate::toYmd($lesson->start_time);
    }
}
