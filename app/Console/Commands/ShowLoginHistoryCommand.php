<?php

namespace App\Console\Commands;

use App\Models\LoginAttempt;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * Consultation en ligne de commande, pour enquêter sans passer par l'interface —
 * c'est ce qui manquait le 2026-09-23, où il a fallu lire les journaux de Google.
 */
class ShowLoginHistoryCommand extends Command
{
    protected $signature = 'auth:login-history
                            {user : Identifiant numérique ou adresse e-mail}
                            {--limit=30 : Nombre de lignes}
                            {--failed : N’afficher que les tentatives refusées}';

    protected $description = 'Affiche les dernières connexions d’un compte';

    public function handle(): int
    {
        $needle = (string) $this->argument('user');
        $user = ctype_digit($needle) ? User::find((int) $needle) : User::where('email', $needle)->first();

        if (! $user) {
            $this->error("Utilisateur introuvable : {$needle}");

            return self::FAILURE;
        }

        $attempts = LoginAttempt::query()
            ->where('user_id', $user->id)
            ->when($this->option('failed'), fn ($q) => $q->failed())
            ->orderByDesc('created_at')
            ->limit(max(1, (int) $this->option('limit')))
            ->get();

        if ($attempts->isEmpty()) {
            $this->warn("Aucune connexion enregistrée pour {$user->email}.");

            return self::SUCCESS;
        }

        $this->info("Connexions de {$user->email} (#{$user->id}) — la plus récente en tête");

        $this->table(
            ['Date', 'Résultat', 'Adresse IP', 'Localisation', 'Opérateur', 'Appareil'],
            $attempts->map(fn (LoginAttempt $a) => [
                $a->created_at?->format('Y-m-d H:i:s'),
                $a->successful ? 'réussie' : 'refusée',
                $a->ip_address ?? '—',
                $a->location_label ?? '—',
                $a->organisation ?? '—',
                trim(implode(' ', array_filter([$a->device_type, $a->browser, $a->platform]))) ?: '—',
            ])->all(),
        );

        return self::SUCCESS;
    }
}
