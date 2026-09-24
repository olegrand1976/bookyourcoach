<?php

namespace App\Console\Commands;

use App\Models\Club;
use App\Models\User;
use App\Services\DualProfileService;
use Illuminate\Console\Command;

/**
 * Rend un compte double club + enseignant. Simulation par défaut.
 */
class GrantDualProfileCommand extends Command
{
    protected $signature = 'user:grant-dual-profile
                            {email : Adresse e-mail du compte}
                            {--club= : Club à gérer (obligatoire pour un compte enseignant)}
                            {--apply : Écrire réellement (sans ce drapeau : simulation)}
                            {--force : Ne pas demander de confirmation (obligatoire sans TTY)}';

    protected $description = 'Donne à un compte enseignant ou club le second profil (club + enseignant), avec 2FA obligatoire';

    public function handle(DualProfileService $service): int
    {
        $user = User::where('email', (string) $this->argument('email'))->first();
        if (! $user) {
            $this->error('Compte introuvable : '.$this->argument('email'));

            return self::FAILURE;
        }

        $club = null;
        if ($this->option('club') !== null) {
            $club = Club::find((int) $this->option('club'));
            if (! $club) {
                $this->error('Club introuvable : '.$this->option('club'));

                return self::FAILURE;
            }
        }

        try {
            $plan = $service->plan($user, $club);
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $apply = (bool) $this->option('apply');
        $this->info(($apply ? '' : '[SIMULATION] ')."{$user->name} <{$user->email}> (#{$user->id}) — {$plan['direction']}");
        foreach ($plan['changes'] as $change) {
            $this->line('  - '.$change);
        }
        $this->line('  - 2FA : '.($user->hasTwoFactorEnabled()
            ? 'déjà configurée'
            : 'à configurer à la prochaine connexion (obligatoire)'));
        $this->line('  - sessions ouvertes révoquées : reconnexion nécessaire');

        if (! $apply) {
            $this->warn('Aucune écriture : relancer avec --apply pour appliquer.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm('Appliquer ?')) {
            $this->warn('Annulé.');

            return self::SUCCESS;
        }

        $service->apply($user, $club);
        $this->info('Compte double activé.');

        return self::SUCCESS;
    }
}
