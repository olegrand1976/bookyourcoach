<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\TwoFactorChallengeRequest;
use App\Http\Requests\Auth\TwoFactorConfirmRequest;
use App\Http\Requests\Auth\TwoFactorSetupRequest;
use App\Models\LoginAttempt;
use App\Models\User;
use App\Services\ClientIpResolver;
use App\Services\LoginAttemptRecorder;
use App\Services\TwoFactorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Seconde étape de la connexion des comptes club et admin.
 *
 * Aucune de ces routes n'est authentifiée : le challenge_token remis par
 * /auth/login après un mot de passe correct est la seule preuve acceptée, et il
 * ne donne accès qu'à cette étape, pour ce compte, pendant quelques minutes.
 */
class TwoFactorController extends Controller
{
    public function __construct(
        private readonly TwoFactorService $twoFactor,
        private readonly LoginAttemptRecorder $recorder,
    ) {}

    /**
     * Enrôlement : génère le secret et son QR code.
     */
    public function setup(TwoFactorSetupRequest $request): JsonResponse
    {
        return $this->locked($request, fn () => $this->doSetup($request));
    }

    private function doSetup(TwoFactorSetupRequest $request): JsonResponse
    {
        $user = $this->twoFactor->challengeUser($request->input('challenge_token'), TwoFactorService::MODE_SETUP);
        // Un challenge d'enrôlement émis avant l'activation ne doit jamais remplacer
        // un secret confirmé depuis : il faut repasser par le login (mode challenge).
        if (! $user || $user->hasTwoFactorEnabled()) {
            return $this->challengeExpired();
        }

        $enrolment = $this->twoFactor->startEnrolment($user);

        return response()->json([
            'success' => true,
            'message' => 'Scannez le QR code avec votre application d\'authentification.',
            'data' => [
                'qr_svg' => $enrolment['qr_svg'],
                'secret' => $enrolment['secret'],
                'otpauth_url' => $enrolment['otpauth_url'],
            ],
        ]);
    }

    /**
     * Enrôlement : le premier code valide active la 2FA et ouvre la session.
     */
    public function confirmSetup(TwoFactorConfirmRequest $request): JsonResponse
    {
        return $this->locked($request, fn () => $this->doConfirmSetup($request));
    }

    private function doConfirmSetup(TwoFactorConfirmRequest $request): JsonResponse
    {
        $challengeToken = $request->input('challenge_token');
        $user = $this->twoFactor->challengeUser($challengeToken, TwoFactorService::MODE_SETUP);
        if (! $user || $user->hasTwoFactorEnabled()) {
            return $this->challengeExpired();
        }

        $recoveryCodes = $this->twoFactor->confirmEnrolment($user, (string) $request->input('code'));
        if ($recoveryCodes === null) {
            return $this->invalidCode($request, $user, $challengeToken);
        }

        $this->twoFactor->consumeChallenge($challengeToken);

        Log::info('Double authentification activée', [
            'user_id' => $user->id,
            'role' => $user->role,
        ]);

        return $this->completeLogin($request, $user, [
            'recovery_codes' => $recoveryCodes,
        ], 'Double authentification activée.');
    }

    /**
     * Connexion : code TOTP ou code de récupération.
     */
    public function challenge(TwoFactorChallengeRequest $request): JsonResponse
    {
        return $this->locked($request, fn () => $this->doChallenge($request));
    }

    private function doChallenge(TwoFactorChallengeRequest $request): JsonResponse
    {
        $challengeToken = $request->input('challenge_token');
        $user = $this->twoFactor->challengeUser($challengeToken, TwoFactorService::MODE_CHALLENGE);
        if (! $user) {
            return $this->challengeExpired();
        }

        $usedRecoveryCode = false;
        if ($request->filled('code')) {
            $valid = $this->twoFactor->verifyCode($user, (string) $request->input('code'));
        } else {
            $valid = $this->twoFactor->useRecoveryCode($user, (string) $request->input('recovery_code'));
            $usedRecoveryCode = $valid;
        }

        if (! $valid) {
            return $this->invalidCode($request, $user, $challengeToken);
        }

        $this->twoFactor->consumeChallenge($challengeToken);

        $extra = [];
        if ($usedRecoveryCode) {
            // Un code de secours sert quand le téléphone manque : à surveiller, et à
            // signaler à la personne pour qu'elle régénère ses codes.
            Log::warning('Connexion par code de récupération 2FA', [
                'user_id' => $user->id,
                'ip' => app(ClientIpResolver::class)->resolve($request),
            ]);
            $extra['remaining_recovery_codes'] = $this->twoFactor->remainingRecoveryCodes($user->fresh());
        }

        if ($request->boolean('remember_device')) {
            $extra['device_token'] = $this->twoFactor->issueTrustedDevice($user, $request);
        }

        return $this->completeLogin($request, $user, $extra, 'Connexion réussie.');
    }

    /**
     * Une seule vérification à la fois par challenge : la lecture du challenge, le
     * contrôle du code et sa consommation (ou l'échec compté) forment un tout. Une
     * requête parallèle attend puis trouve le challenge consommé — un challenge
     * n'ouvre donc jamais deux sessions, et chaque code faux est bien compté.
     */
    private function locked(Request $request, callable $callback): JsonResponse
    {
        // Attente trop longue : LockTimeoutException, rendue en 429 (bootstrap/app.php).
        return $this->twoFactor->withChallengeLock((string) $request->input('challenge_token'), $callback);
    }

    /**
     * Délivre le jeton : même journalisation et même historique qu'un login direct.
     */
    private function completeLogin(Request $request, User $user, array $extra, string $message): JsonResponse
    {
        $token = $user->createToken('auth_token')->plainTextToken;

        Log::info('Connexion réussie', [
            'user_id' => $user->id,
            'email' => $user->email,
            'role' => $user->role,
            'ip' => app(ClientIpResolver::class)->resolve($request),
            'user_agent' => $request->userAgent(),
            'two_factor' => true,
        ]);
        $this->recorder->recordSuccess($request, $user);

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => array_merge([
                'user' => $user->fresh(),
                'access_token' => $token,
                'token_type' => 'Bearer',
            ], $extra),
        ]);
    }

    private function invalidCode(Request $request, User $user, string $challengeToken): JsonResponse
    {
        $remaining = $this->twoFactor->recordChallengeFailure($challengeToken);
        $this->recorder->recordFailure($request, $user->email, LoginAttempt::REASON_INVALID_TWO_FACTOR, $user);

        Log::warning('Code 2FA refusé', [
            'user_id' => $user->id,
            'ip' => app(ClientIpResolver::class)->resolve($request),
            'remaining_attempts' => $remaining,
        ]);

        if ($remaining === 0) {
            return $this->challengeExpired('Trop de codes invalides : reconnectez-vous.');
        }

        return response()->json([
            'success' => false,
            'message' => 'Code invalide.',
            'errors' => ['code' => ['Code invalide.']],
            'data' => ['remaining_attempts' => $remaining],
        ], 422);
    }

    private function challengeExpired(string $message = 'Session de vérification expirée : reconnectez-vous.'): JsonResponse
    {
        return response()->json([
            'success' => false,
            'code' => 'two_factor_challenge_invalid',
            'message' => $message,
        ], 401);
    }
}
