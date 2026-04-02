<?php

namespace App\Services;

use App\Jobs\NotifyClubClosureRecipientsJob;
use App\Models\Club;
use App\Models\ClubClosureDay;
use App\Models\Lesson;
use App\Models\SubscriptionInstance;
use Carbon\Carbon;
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
        $clubId = $lesson->club_id;
        if (!$clubId || !$lesson->start_time) {
            return false;
        }

        $ymd = Carbon::parse($lesson->start_time)
            ->timezone(config('app.timezone'))
            ->format('Y-m-d');

        return ClubClosureDay::clubIsClosedOn((int) $clubId, $ymd);
    }
}
