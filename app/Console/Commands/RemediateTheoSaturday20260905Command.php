<?php

namespace App\Console\Commands;

use App\Models\Lesson;
use App\Models\LessonActionLog;
use App\Models\LessonMovementHistory;
use App\Models\User;
use App\Services\LessonActionLogService;
use App\Services\LessonMovementHistoryService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Remédiation one-shot : réaffecter à Theo Verbeelen (teacher_id=31)
 * les cours du samedi 2026-09-05 (Romane, Léa, Rafael) et recréer Rose.
 */
class RemediateTheoSaturday20260905Command extends Command
{
    protected $signature = 'lessons:remediate-theo-2026-09-05
                            {--dry-run : Afficher sans écrire}
                            {--force : Exécuter sans confirmation}';

    protected $description = 'Réaffecte Romane/Léa/Rafael/Rose du 2026-09-05 à Theo Verbeelen';

    public function handle(
        LessonMovementHistoryService $movementHistory,
        LessonActionLogService $actionLog,
    ): int {
        $dryRun = (bool) $this->option('dry-run');
        $teacherId = 31;
        $clubId = 11;
        $date = '2026-09-05';

        $targets = [
            ['student_id' => 149, 'name' => 'Romane Faieta', 'time' => '11:00:00', 'existing_id' => 2653],
            ['student_id' => 136, 'name' => 'Rose Riquet', 'time' => '11:20:00', 'existing_id' => null, 'template_id' => 2538],
            ['student_id' => 94, 'name' => 'Léa Meysman', 'time' => '11:40:00', 'existing_id' => 3361],
            ['student_id' => 171, 'name' => 'Rafael Demeuse', 'time' => '12:00:00', 'existing_id' => 3310],
        ];

        $this->info(($dryRun ? '[DRY-RUN] ' : '')."Remédiation Theo #{$teacherId} — {$date}");

        if (! $dryRun && ! $this->option('force') && ! $this->confirm('Appliquer la remédiation ?')) {
            $this->warn('Annulé.');

            return self::SUCCESS;
        }

        $actor = User::query()->where('role', 'club')->orderBy('id')->first();

        DB::transaction(function () use ($targets, $teacherId, $clubId, $date, $dryRun, $movementHistory, $actionLog, $actor) {
            foreach ($targets as $target) {
                $start = Carbon::parse("{$date} {$target['time']}");
                $end = $start->copy()->addMinutes(20);

                if ($target['existing_id']) {
                    $lesson = Lesson::withTrashed()->find($target['existing_id']);
                    if (! $lesson) {
                        $this->error("Lesson #{$target['existing_id']} introuvable ({$target['name']})");

                        continue;
                    }

                    $before = $movementHistory->snapshot($lesson);
                    $this->line("• {$target['name']} #{$lesson->id} : teacher {$lesson->teacher_id} → {$teacherId}");

                    if ($dryRun) {
                        continue;
                    }

                    if ($lesson->trashed()) {
                        $lesson->restore();
                    }

                    $lesson->update([
                        'teacher_id' => $teacherId,
                        'status' => $lesson->status === 'cancelled' ? 'confirmed' : $lesson->status,
                        'deleted_at' => null,
                    ]);

                    $movementHistory->recordChange(
                        $lesson->fresh(),
                        $before,
                        LessonMovementHistory::EVENT_TEACHER_REASSIGNED,
                        $actor,
                        $actor?->role ?? 'club',
                        'single',
                        null,
                        ['remediation' => 'theo-2026-09-05', 'student_name' => $target['name']],
                    );

                    $actionLog->log(
                        $lesson->fresh(),
                        LessonActionLog::ACTION_UPDATED,
                        $actor,
                        $actor?->role ?? 'club',
                        meta: [
                            'update_scope' => 'single',
                            'old_teacher_id' => $before['teacher_id'],
                            'remediation' => 'theo-2026-09-05',
                        ],
                    );

                    continue;
                }

                // Rose : créer depuis le template 12/09 si absent
                $existing = Lesson::query()
                    ->where('club_id', $clubId)
                    ->where('student_id', $target['student_id'])
                    ->whereDate('start_time', $date)
                    ->whereTime('start_time', $target['time'])
                    ->first();

                if ($existing) {
                    $this->line("• {$target['name']} existe déjà #{$existing->id} — réaffectation");
                    if ($dryRun) {
                        continue;
                    }
                    $before = $movementHistory->snapshot($existing);
                    $existing->update(['teacher_id' => $teacherId]);
                    $movementHistory->recordChange(
                        $existing->fresh(),
                        $before,
                        LessonMovementHistory::EVENT_TEACHER_REASSIGNED,
                        $actor,
                        $actor?->role ?? 'club',
                        'single',
                        null,
                        ['remediation' => 'theo-2026-09-05', 'student_name' => $target['name']],
                    );

                    continue;
                }

                $template = Lesson::with(['subscriptionInstances', 'courseType'])->find($target['template_id'] ?? 0);
                if (! $template) {
                    $this->error("Template manquant pour {$target['name']}");

                    continue;
                }

                $this->line("• {$target['name']} : création {$start->toDateTimeString()} (template #{$template->id})");

                if ($dryRun) {
                    continue;
                }

                $lesson = Lesson::create([
                    'club_id' => $clubId,
                    'teacher_id' => $teacherId,
                    'student_id' => $target['student_id'],
                    'course_type_id' => $template->course_type_id,
                    'location_id' => $template->location_id,
                    'start_time' => $start,
                    'end_time' => $end,
                    'status' => 'confirmed',
                    'price' => $template->price,
                    'notes' => 'Remédiation planning Theo 2026-09-05',
                ]);

                foreach ($template->subscriptionInstances as $instance) {
                    $lesson->subscriptionInstances()->syncWithoutDetaching([$instance->id]);
                }

                $movementHistory->record(
                    $lesson,
                    LessonMovementHistory::EVENT_CREATED,
                    $actor,
                    $actor?->role ?? 'club',
                    null,
                    $teacherId,
                    null,
                    $start,
                    null,
                    $end,
                    null,
                    'confirmed',
                    'single',
                    null,
                    ['remediation' => 'theo-2026-09-05', 'student_name' => $target['name']],
                );

                $actionLog->log(
                    $lesson,
                    LessonActionLog::ACTION_CREATED,
                    $actor,
                    $actor?->role ?? 'club',
                    meta: ['remediation' => 'theo-2026-09-05'],
                );
            }
        });

        $this->info($dryRun ? 'Dry-run terminé.' : 'Remédiation terminée.');

        return self::SUCCESS;
    }
}
