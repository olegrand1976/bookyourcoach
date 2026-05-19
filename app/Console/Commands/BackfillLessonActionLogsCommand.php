<?php

namespace App\Console\Commands;

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

        if ($dryRun) {
            $this->warn('Mode dry-run : aucune écriture (comptage non disponible en dry-run).');

            return self::SUCCESS;
        }

        if ($clubId) {
            $created = $logService->syncMissingCancellationLogs($clubId, $from);
            $this->info("Club #{$clubId} — entrées créées: {$created}");

            return self::SUCCESS;
        }

        $clubs = \App\Models\Club::query()->pluck('id');
        $total = 0;
        foreach ($clubs as $id) {
            $total += $logService->syncMissingCancellationLogs((int) $id, $from);
        }

        $this->info("Entrées créées au total: {$total}");

        return self::SUCCESS;
    }
}
