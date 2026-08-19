<?php

namespace App\Services;

use App\Jobs\NotifyClubClosureRecipientsJob;
use App\Models\Club;
use App\Models\ClubClosureDay;
use App\Models\Lesson;
use App\Models\SubscriptionInstance;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ClubClosureDayService
{
    /**
     * Mark a calendar day as closed: persist row, detach subscription links for lessons that day, recalculate usage.
     * Idempotent: if the day was already closed, does nothing (no duplicate notifications or detach passes).
     */
    public function closeDay(Club $club, string $dateYmd): array
    {
        $notify = false;

        DB::transaction(function () use ($club, $dateYmd, &$notify) {
            $closure = ClubClosureDay::firstOrCreate(
                [
                    'club_id' => $club->id,
                    'closed_on' => $dateYmd,
                ],
                []
            );

            if (!$closure->wasRecentlyCreated) {
                Log::info('Club closure: day already closed, skip', [
                    'club_id' => $club->id,
                    'closed_on' => $dateYmd,
                ]);

                return;
            }

            $notify = true;

            $lessons = Lesson::query()
                ->where('club_id', $club->id)
                ->whereDate('start_time', $dateYmd)
                ->whereIn('status', ['pending', 'confirmed'])
                ->get();

            foreach ($lessons as $lesson) {
                $instances = SubscriptionInstance::query()
                    ->whereHas('lessons', function ($q) use ($lesson) {
                        $q->where('lesson_id', $lesson->id);
                    })
                    ->get();

                foreach ($instances as $instance) {
                    $instance->lessons()->detach($lesson->id);
                    $instance->recalculateLessonsUsed();
                    $instance->checkAndUpdateStatus();

                    Log::info('Club closure: lesson detached from subscription instance', [
                        'club_id' => $club->id,
                        'closed_on' => $dateYmd,
                        'lesson_id' => $lesson->id,
                        'subscription_instance_id' => $instance->id,
                    ]);
                }
            }
        });

        if ($notify) {
            NotifyClubClosureRecipientsJob::dispatch($club->id, $dateYmd, 'closed');
        }

        return [
            'notified' => $notify,
        ];
    }

    /**
     * Remove closure row only. Does not re-attach subscriptions to lessons.
     */
    public function openDay(Club $club, string $dateYmd): bool
    {
        $deleted = ClubClosureDay::query()
            ->where('club_id', $club->id)
            ->whereDate('closed_on', $dateYmd)
            ->delete();

        if ($deleted > 0) {
            NotifyClubClosureRecipientsJob::dispatch($club->id, $dateYmd, 'reopened');
        }

        return $deleted > 0;
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
     * Exclut les cours tombant un jour de fermeture club (même club_id, même règle date que closeDay / whereDate).
     */
    public function excludeClosedDaysFromQuery(Builder $query, string $lessonsTable = 'lessons'): void
    {
        $lessonDateSql = $this->lessonStartDateSqlExpression($lessonsTable);

        $query->whereNotExists(function ($sub) use ($lessonsTable, $lessonDateSql) {
            $sub->select(DB::raw(1))
                ->from('club_closure_days')
                ->whereColumn('club_closure_days.club_id', "{$lessonsTable}.club_id")
                ->whereRaw("{$lessonDateSql} = DATE(club_closure_days.closed_on)");
        });
    }

    /**
     * Expression SQL de la date calendaire du cours — alignée sur whereDate('start_time') et lessonStartDateYmd().
     */
    private function lessonStartDateSqlExpression(string $lessonsTable): string
    {
        return 'DATE('.$lessonsTable.'.start_time)';
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
        if (! $lesson->start_time) {
            return null;
        }

        // Aligné sur closeDay() → whereDate('start_time') et excludeClosedDaysFromQuery() → DATE(start_time).
        return Carbon::parse($lesson->start_time)->toDateString();
    }
}
