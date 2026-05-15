<?php

namespace App\Console\Commands;

use App\Models\Lesson;
use App\Services\LessonCancellationAudit;
use Illuminate\Console\Command;

class BackfillLessonCancellationAuditCommand extends Command
{
    protected $signature = 'lessons:backfill-cancellation-audit {--chunk=200}';

    protected $description = 'Remplit cancelled_at et cancelled_by_role pour les cours déjà annulés';

    public function handle(): int
    {
        $chunk = (int) $this->option('chunk');
        $updated = 0;

        Lesson::query()
            ->where('status', 'cancelled')
            ->whereNull('cancelled_at')
            ->orderBy('id')
            ->chunkById($chunk, function ($lessons) use (&$updated) {
                foreach ($lessons as $lesson) {
                    $lesson->cancelled_at = $lesson->updated_at;
                    $lesson->cancelled_by_role = LessonCancellationAudit::inferRoleFromNotes($lesson->notes);
                    $lesson->saveQuietly();
                    $updated++;
                }
            });

        $this->info("{$updated} cours annulés mis à jour.");

        return self::SUCCESS;
    }
}
