<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RevokeUserTokensCommand extends Command
{
    protected $signature = 'auth:revoke-tokens {user : Identifiant numérique ou adresse e-mail}';

    protected $description = 'Révoque tous les jetons d’accès d’un utilisateur (déconnexion immédiate)';

    public function handle(): int
    {
        $needle = (string) $this->argument('user');

        $user = ctype_digit($needle)
            ? User::find((int) $needle)
            : User::where('email', $needle)->first();

        if (! $user) {
            $this->error("Utilisateur introuvable : {$needle}");

            return self::FAILURE;
        }

        $revoked = $user->tokens()->delete();

        $this->info("{$revoked} jeton(s) révoqué(s) pour {$user->email} (#{$user->id}).");
        Log::info('Jetons révoqués manuellement', ['user_id' => $user->id, 'revoked_tokens' => $revoked]);

        return self::SUCCESS;
    }
}
