<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Purge les jetons dormants. sanctum:prune-expired ne traite que l'expiration absolue :
 * l'expiration glissante du projet repose sur last_used_at, et la même durée
 * (bookyourcoach.auth.token_idle_days) sert au contrôle à l'authentification.
 */
class PruneIdleTokensCommand extends Command
{
    protected $signature = 'auth:prune-idle-tokens {--dry-run : Compter sans supprimer}';

    protected $description = 'Supprime les jetons inutilisés depuis la durée d’inactivité configurée';

    public function handle(): int
    {
        $idleDays = (int) config('bookyourcoach.auth.token_idle_days', 30);

        if ($idleDays <= 0) {
            $this->warn('Expiration glissante désactivée (token_idle_days <= 0) : rien à purger.');

            return self::SUCCESS;
        }

        $threshold = now()->subDays($idleDays);

        $query = DB::table('personal_access_tokens')
            ->whereRaw('COALESCE(last_used_at, created_at) <= ?', [$threshold]);

        $count = (clone $query)->count();

        if ($this->option('dry-run')) {
            $this->info("{$count} jeton(s) dormant(s) depuis plus de {$idleDays} jours (simulation).");

            return self::SUCCESS;
        }

        $deleted = $query->delete();

        $this->info("{$deleted} jeton(s) purgé(s) (inactifs depuis plus de {$idleDays} jours).");
        Log::info('Purge des jetons dormants', ['deleted' => $deleted, 'idle_days' => $idleDays]);

        return self::SUCCESS;
    }
}
