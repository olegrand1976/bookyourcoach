<?php

namespace App\Console\Commands;

use App\Models\LoginAttempt;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Purge de l'historique des connexions.
 *
 * Les adresses IP et les localisations sont des données personnelles : les garder
 * indéfiniment ne serait ni nécessaire ni proportionné. La durée est configurable
 * et assumée (bookyourcoach.auth.login_history_retention_days).
 */
class PruneLoginHistoryCommand extends Command
{
    protected $signature = 'auth:prune-login-history {--dry-run : Compter sans supprimer}';

    protected $description = 'Supprime les connexions plus anciennes que la durée de conservation';

    public function handle(): int
    {
        $days = (int) config('bookyourcoach.auth.login_history_retention_days', 365);

        if ($days <= 0) {
            $this->warn('Conservation illimitée (valeur <= 0) : rien à purger.');

            return self::SUCCESS;
        }

        $seuil = now()->subDays($days);
        $query = LoginAttempt::query()->where('created_at', '<', $seuil);

        if ($this->option('dry-run')) {
            $this->info("{$query->count()} connexion(s) antérieure(s) au {$seuil->toDateString()} (simulation).");

            return self::SUCCESS;
        }

        $supprimees = $query->delete();

        $this->info("{$supprimees} connexion(s) purgée(s) (conservation : {$days} jours).");
        Log::info('Purge de l’historique de connexion', ['deleted' => $supprimees, 'retention_days' => $days]);

        return self::SUCCESS;
    }
}
