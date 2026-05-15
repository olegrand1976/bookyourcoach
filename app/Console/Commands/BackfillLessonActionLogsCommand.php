<?php

namespace App\Console\Commands;

use App\Models\Lesson;
use App\Models\LessonActionLog;
use App\Models\User;
use App\Services\LessonActionLogService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class BackfillLessonActionLogsCommand extends Command
{
    protected $signature = 'lessons:backfill-action-logs
                            {--days=365 : Nombre de jours en arrière}
                            {--club-id= : Limiter à un club}
                            {--dry-run : Simuler sans écrire}';

    protected $description = 'Importe les annulations historiques dans lesson_action_logs';

    public function handle(LessonActionLogService $logService): int
    {
        if (! Schema::hasTable('lesson_action_logs')) {
            $this->error('Table lesson_action_logs absente. Exécutez les migrations.');

            return self::FAILURE;
        }

        $from = Carbon::now()->subDays((int) $this->option('days'))->startOfDay();
        $clubId = $this->option('club-id') ? (int) $this->option('club-id') : null;
        $dryRun = (bool) $this->option('dry-run');

        $query = Lesson::query()
            ->where('status', 'cancelled')
            ->whereNotNull('club_id')
            ->where(function ($q) use ($from) {
                if (Schema::hasColumn('lessons', 'cancelled_at')) {
                    $q->where('cancelled_at', '>=', $from)
                        ->orWhere(function ($q2) use ($from) {
                            $q2->whereNull('cancelled_at')->where('updated_at', '>=', $from);
                        });
                } else {
                    $q->where('updated_at', '>=', $from);
                }
            });

        if ($clubId) {
            $query->where('club_id', $clubId);
        }

        $created = 0;
        $skipped = 0;

        $query->with(['student.user', 'students.user', 'subscriptionInstances.subscription.template', 'cancelledByUser'])
            ->orderBy('id')
            ->chunkById(200, function ($lessons) use ($logService, $dryRun, &$created, &$skipped) {
                foreach ($lessons as $lesson) {
                    $actionAt = $lesson->cancelled_at ?? $lesson->updated_at;
                    $exists = LessonActionLog::query()
                        ->where('lesson_id', $lesson->id)
                        ->whereIn('action', [
                            LessonActionLog::ACTION_CANCELLED,
                            LessonActionLog::ACTION_CANCELLED_CASCADE,
                            LessonActionLog::ACTION_STUDENT_CANCELLED,
                        ])
                        ->exists();

                    if ($exists) {
                        $skipped++;

                        continue;
                    }

                    $performedBy = null;
                    if (Schema::hasColumn('lessons', 'cancelled_by_user_id') && $lesson->cancelled_by_user_id) {
                        $performedBy = User::find($lesson->cancelled_by_user_id);
                    }

                    $role = $lesson->cancelled_by_role ?? 'unknown';
                    $action = $role === 'student'
                        ? LessonActionLog::ACTION_STUDENT_CANCELLED
                        : LessonActionLog::ACTION_CANCELLED;

                    if ($dryRun) {
                        $created++;

                        continue;
                    }

                    $log = $logService->log(
                        $lesson,
                        $action,
                        $performedBy,
                        $role,
                        meta: ['backfilled' => true],
                    );

                    if ($log && $actionAt) {
                        $log->created_at = $actionAt;
                        $log->updated_at = $actionAt;
                        $log->saveQuietly();
                    }

                    $created++;
                }
            });

        $this->info(($dryRun ? '[dry-run] ' : '') . "Créés: {$created}, ignorés (déjà présents): {$skipped}");

        return self::SUCCESS;
    }
}
