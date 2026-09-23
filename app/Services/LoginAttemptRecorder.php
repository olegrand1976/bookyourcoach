<?php

namespace App\Services;

use App\Models\LoginAttempt;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Consigne chaque tentative de connexion, réussie ou refusée.
 *
 * Écrire cet historique ne doit jamais empêcher quelqu'un de se connecter : toute
 * erreur ici est journalisée et avalée.
 */
class LoginAttemptRecorder
{
    public function __construct(
        private readonly RequestOriginResolver $origin,
    ) {}

    public function recordSuccess(Request $request, User $user): ?LoginAttempt
    {
        return $this->record($request, $user->email, true, null, $user);
    }

    public function recordFailure(Request $request, ?string $email, string $reason = LoginAttempt::REASON_INVALID_CREDENTIALS, ?User $user = null): ?LoginAttempt
    {
        // On rattache la tentative au compte visé s'il existe : c'est ce qui permet
        // à quelqu'un de voir qu'on a essayé d'entrer chez lui. Compte déjà connu
        // (échec 2FA) : le prendre tel quel, un même email pouvant porter plusieurs rôles.
        $user ??= $email ? User::where('email', $email)->first() : null;

        return $this->record($request, $email, false, $reason, $user);
    }

    private function record(Request $request, ?string $email, bool $successful, ?string $reason, ?User $user): ?LoginAttempt
    {
        try {
            return LoginAttempt::create(array_merge([
                'user_id' => $user?->id,
                'email' => $email ? mb_substr($email, 0, 255) : null,
                'successful' => $successful,
                'failure_reason' => $reason,
            ], $this->origin->describe($request)));
        } catch (\Throwable $e) {
            Log::warning('Historique de connexion : écriture impossible', [
                'email' => $email,
                'exception' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
