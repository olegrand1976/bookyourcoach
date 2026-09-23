<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;

/**
 * Recherche les comptes dont le mot de passe fait partie des valeurs qui ont été
 * publiées dans ce dépôt (tests e2e, seeders, documentation) alors qu'il était public.
 *
 * Ce n'est pas une attaque par dictionnaire : la liste est courte et fermée, elle ne
 * contient que ce que nous avons nous-mêmes divulgué, et l'audit porte sur notre base.
 */
class AuditWeakPasswordsCommand extends Command
{
    protected $signature = 'auth:audit-weak-passwords
                            {--revoke : Révoquer les jetons des comptes concernés}
                            {--send-reset-link : Envoyer un lien de réinitialisation aux comptes concernés}
                            {--chunk=200 : Taille des lots (bcrypt est volontairement lent)}';

    protected $description = 'Signale les comptes utilisant un mot de passe divulgué par le dépôt';

    /** Valeurs présentes en clair dans l'historique public du dépôt. */
    private const LEAKED_PASSWORDS = [
        'password',
        'password123',
        'admin123',
        'coach123',
        'eleve123',
        'Password123',
    ];

    public function handle(): int
    {
        $revoke = (bool) $this->option('revoke');
        $sendResetLink = (bool) $this->option('send-reset-link');
        $chunk = max(1, (int) $this->option('chunk'));

        if (! $revoke && ! $sendResetLink) {
            $this->warn('Lecture seule : aucun compte ne sera modifié ni notifié.');
        }

        $affected = [];
        $scanned = 0;

        User::query()
            ->select(['id', 'email', 'role', 'password'])
            ->orderBy('id')
            ->chunk($chunk, function ($users) use (&$affected, &$scanned) {
                foreach ($users as $user) {
                    $scanned++;

                    foreach (self::LEAKED_PASSWORDS as $candidate) {
                        if (Hash::check($candidate, $user->password)) {
                            $affected[] = ['user' => $user, 'password' => $candidate];
                            break;
                        }
                    }
                }
            });

        $this->info("{$scanned} compte(s) examiné(s).");

        if ($affected === []) {
            $this->info('Aucun mot de passe divulgué en base.');

            return self::SUCCESS;
        }

        $rows = [];
        foreach ($affected as $entry) {
            $user = $entry['user'];
            $actions = [];

            if ($revoke) {
                $actions[] = $user->tokens()->delete().' jeton(s) révoqué(s)';
            }

            if ($sendResetLink) {
                $status = Password::sendResetLink(['email' => $user->email]);
                $actions[] = $status === Password::RESET_LINK_SENT
                    ? 'lien envoyé'
                    : 'lien non envoyé ('.$status.')';
            }

            $rows[] = [$user->id, $user->email, $user->role, $entry['password'], implode(' · ', $actions) ?: '—'];
        }

        $this->table(['ID', 'E-mail', 'Rôle', 'Mot de passe', 'Action'], $rows);
        $this->error(count($affected).' compte(s) utilisent un mot de passe divulgué.');

        Log::warning('Audit des mots de passe : comptes vulnérables', [
            'count' => count($affected),
            'user_ids' => array_map(fn ($e) => $e['user']->id, $affected),
            'revoked' => $revoke,
            'reset_link_sent' => $sendResetLink,
        ]);

        return self::FAILURE;
    }
}
