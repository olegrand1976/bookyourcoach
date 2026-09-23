<?php

namespace App\Services;

use App\Models\TrustedDevice;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/**
 * Double authentification TOTP : enrôlement, vérification des codes, codes de
 * récupération, challenges de connexion et appareils de confiance.
 *
 * Toute la logique est ici pour que les contrôleurs n'aient qu'à orchestrer et
 * qu'un seul endroit porte les garanties de sécurité :
 *  - un même code TOTP n'est accepté qu'une fois (anti-rejeu) ;
 *  - un challenge ne sert qu'à un compte, expire vite et meurt après trop d'erreurs ;
 *  - aucun secret ni jeton n'est stocké en clair (chiffrement ou empreinte).
 */
class TwoFactorService
{
    public const MODE_CHALLENGE = 'challenge';

    public const MODE_SETUP = 'setup';

    public const MAX_CHALLENGE_FAILURES = 5;

    public const RECOVERY_CODES_COUNT = 8;

    // Une période TOTP de part et d'autre : tolère 30 s de décalage d'horloge
    // du téléphone sans élargir inutilement la fenêtre d'attaque.
    private const WINDOW = 1;

    // Durée laissée pour scanner le QR code et saisir le premier code.
    private const PENDING_SECRET_MINUTES = 15;

    public function __construct(
        private readonly Google2FA $google2fa,
        private readonly ClientIpResolver $clientIp,
    ) {}

    // ---------------------------------------------------------------------
    // Enrôlement
    // ---------------------------------------------------------------------

    /**
     * Prépare un nouveau secret sans toucher au secret actif : tant que le premier
     * code n'est pas confirmé, un changement de téléphone peut être abandonné.
     *
     * @return array{secret: string, otpauth_url: string, qr_svg: string}
     */
    public function startEnrolment(User $user): array
    {
        $secret = $this->google2fa->generateSecretKey(32);

        Cache::put(
            $this->pendingSecretKey($user),
            Crypt::encryptString($secret),
            now()->addMinutes(self::PENDING_SECRET_MINUTES)
        );

        $url = $this->google2fa->getQRCodeUrl($this->issuer(), $user->email, $secret);

        return [
            'secret' => $secret,
            'otpauth_url' => $url,
            'qr_svg' => (string) QrCode::format('svg')->size(200)->margin(1)->generate($url),
        ];
    }

    /**
     * Active le secret en attente si le code fourni en provient.
     *
     * @return list<string>|null Les codes de récupération en clair (affichés une seule
     *                           fois), ou null si le code est faux ou l'enrôlement expiré.
     */
    public function confirmEnrolment(User $user, string $code): ?array
    {
        $encrypted = Cache::get($this->pendingSecretKey($user));
        if (! $encrypted) {
            return null;
        }

        $secret = Crypt::decryptString($encrypted);
        if (! $this->verifyCode($user, $code, $secret)) {
            return null;
        }

        $recoveryCodes = $this->generateRecoveryCodes();

        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => array_map(fn ($c) => $this->hashRecoveryCode($c), $recoveryCodes),
            'two_factor_confirmed_at' => now(),
        ])->save();

        Cache::forget($this->pendingSecretKey($user));

        return $recoveryCodes;
    }

    public function hasPendingEnrolment(User $user): bool
    {
        return Cache::has($this->pendingSecretKey($user));
    }

    // ---------------------------------------------------------------------
    // Codes
    // ---------------------------------------------------------------------

    /**
     * Vérifie un code TOTP. Sans $secret, c'est le secret actif du compte qui sert.
     * Un code déjà accepté (ou plus ancien) est refusé : intercepté, il ne rouvre rien.
     */
    public function verifyCode(User $user, string $code, ?string $secret = null): bool
    {
        $secret ??= $user->two_factor_secret;
        $code = preg_replace('/\s+/', '', $code);

        if (! $secret || ! preg_match('/^\d{6}$/', $code)) {
            return false;
        }

        $key = $this->lastTimestampKey($user);
        $lastTimestamp = (int) Cache::get($key, 0);

        $timestamp = $this->google2fa->verifyKeyNewer($secret, $code, $lastTimestamp, self::WINDOW);
        if ($timestamp === false) {
            return false;
        }

        // Au-delà de la fenêtre, le code est de toute façon périmé : inutile de garder plus.
        Cache::put($key, $timestamp, now()->addMinutes(5));

        return true;
    }

    /**
     * Consomme un code de récupération : chacun ne sert qu'une fois.
     */
    public function useRecoveryCode(User $user, string $code): bool
    {
        $hashes = $user->two_factor_recovery_codes ?? [];
        $candidate = $this->hashRecoveryCode($code);

        foreach ($hashes as $index => $hash) {
            if (hash_equals($hash, $candidate)) {
                unset($hashes[$index]);
                $user->forceFill(['two_factor_recovery_codes' => array_values($hashes)])->save();

                return true;
            }
        }

        return false;
    }

    public function remainingRecoveryCodes(User $user): int
    {
        return count($user->two_factor_recovery_codes ?? []);
    }

    /**
     * Remplace tous les codes de récupération ; les anciens cessent de fonctionner.
     *
     * @return list<string>
     */
    public function regenerateRecoveryCodes(User $user): array
    {
        $codes = $this->generateRecoveryCodes();

        $user->forceFill([
            'two_factor_recovery_codes' => array_map(fn ($c) => $this->hashRecoveryCode($c), $codes),
        ])->save();

        return $codes;
    }

    // ---------------------------------------------------------------------
    // Challenges de connexion
    // ---------------------------------------------------------------------

    /**
     * Seconde étape exigée après un mot de passe correct, ou null si le jeton peut
     * être délivré tout de suite (rôle non concerné, ou appareil de confiance).
     *
     * @return array{two_factor_required: bool, two_factor_setup_required: bool, challenge_token: string}|null
     */
    public function pendingStepFor(User $user, ?string $deviceToken = null): ?array
    {
        if (! $user->requiresTwoFactor()) {
            return null;
        }

        $enabled = $user->hasTwoFactorEnabled();

        // Un appareil de confiance ne dispense que du code : jamais de l'enrôlement.
        if ($enabled && $this->isTrustedDevice($user, $deviceToken)) {
            return null;
        }

        $mode = $enabled ? self::MODE_CHALLENGE : self::MODE_SETUP;

        return [
            'two_factor_required' => $enabled,
            'two_factor_setup_required' => ! $enabled,
            'challenge_token' => $this->createChallenge($user, $mode),
        ];
    }

    /**
     * Émis après un mot de passe correct : prouve que la première étape est franchie,
     * sans rien ouvrir d'autre que l'étape 2FA de ce compte.
     */
    public function createChallenge(User $user, string $mode): string
    {
        $token = Str::random(64);
        $ttl = max(1, (int) config('bookyourcoach.auth.two_factor_challenge_ttl_minutes', 10));

        Cache::put($this->challengeKey($token), [
            'user_id' => $user->id,
            'mode' => $mode,
            'failures' => 0,
            'expires_at' => now()->addMinutes($ttl)->getTimestamp(),
        ], now()->addMinutes($ttl));

        return $token;
    }

    /**
     * Le compte visé par un challenge encore valide, ou null.
     */
    public function challengeUser(?string $token, string $mode): ?User
    {
        $payload = $token ? Cache::get($this->challengeKey($token)) : null;
        if (! is_array($payload) || ($payload['mode'] ?? null) !== $mode) {
            return null;
        }

        return User::find($payload['user_id']);
    }

    /**
     * Compte une erreur de code ; au-delà du plafond, le challenge est détruit et il
     * faut repasser par le mot de passe.
     *
     * @return int Essais restants (0 : challenge détruit).
     */
    public function recordChallengeFailure(string $token): int
    {
        $key = $this->challengeKey($token);
        $payload = Cache::get($key);
        if (! is_array($payload)) {
            return 0;
        }

        $payload['failures']++;
        $remaining = self::MAX_CHALLENGE_FAILURES - $payload['failures'];

        if ($remaining <= 0) {
            Cache::forget($key);

            return 0;
        }

        // Garder l'échéance d'origine : une erreur ne doit pas prolonger le challenge.
        $seconds = $payload['expires_at'] - now()->getTimestamp();
        if ($seconds <= 0) {
            Cache::forget($key);

            return 0;
        }
        Cache::put($key, $payload, $seconds);

        return $remaining;
    }

    public function consumeChallenge(string $token): void
    {
        Cache::forget($this->challengeKey($token));
    }

    // ---------------------------------------------------------------------
    // Appareils de confiance
    // ---------------------------------------------------------------------

    /**
     * @return string Le jeton en clair, à remettre au navigateur : il n'est plus
     *                récupérable ensuite.
     */
    public function issueTrustedDevice(User $user, Request $request): string
    {
        $token = Str::random(64);
        $days = max(1, (int) config('bookyourcoach.auth.two_factor_trusted_device_days', 30));

        $user->trustedDevices()->create([
            'token_hash' => hash('sha256', $token),
            'user_agent' => Str::limit((string) $request->userAgent(), 490, ''),
            'ip_address' => $this->clientIp->resolve($request),
            'last_used_at' => now(),
            'expires_at' => now()->addDays($days),
        ]);

        return $token;
    }

    public function isTrustedDevice(User $user, ?string $token): bool
    {
        if (! $token) {
            return false;
        }

        $device = TrustedDevice::query()
            ->where('user_id', $user->id)
            ->where('token_hash', hash('sha256', $token))
            ->active()
            ->first();

        if (! $device) {
            return false;
        }

        $device->forceFill(['last_used_at' => now()])->save();

        return true;
    }

    // ---------------------------------------------------------------------
    // Réinitialisation
    // ---------------------------------------------------------------------

    /**
     * Remet le compte à zéro : prochaine connexion = nouvel enrôlement. Toutes les
     * sessions tombent, puisque l'ancien second facteur n'est plus une garantie.
     */
    public function reset(User $user): void
    {
        DB::transaction(function () use ($user) {
            $user->forceFill([
                'two_factor_secret' => null,
                'two_factor_recovery_codes' => null,
                'two_factor_confirmed_at' => null,
            ])->save();

            $user->trustedDevices()->delete();
            $user->tokens()->delete();
        });

        Cache::forget($this->pendingSecretKey($user));
        Cache::forget($this->lastTimestampKey($user));
    }

    // ---------------------------------------------------------------------

    /**
     * @return list<string> Format « xxxxx-xxxxx », lisible et facile à recopier.
     */
    private function generateRecoveryCodes(): array
    {
        $codes = [];
        for ($i = 0; $i < self::RECOVERY_CODES_COUNT; $i++) {
            $raw = strtolower(Str::random(10));
            $codes[] = substr($raw, 0, 5).'-'.substr($raw, 5);
        }

        return $codes;
    }

    private function hashRecoveryCode(string $code): string
    {
        // Tolère majuscules, espaces et tiret oubliés à la saisie.
        $normalized = strtolower(preg_replace('/[\s-]+/', '', $code));

        return hash('sha256', $normalized);
    }

    private function issuer(): string
    {
        return (string) config('bookyourcoach.auth.two_factor_issuer', config('app.name'));
    }

    private function challengeKey(string $token): string
    {
        return '2fa:challenge:'.hash('sha256', $token);
    }

    private function pendingSecretKey(User $user): string
    {
        return '2fa:pending-secret:'.$user->id;
    }

    private function lastTimestampKey(User $user): string
    {
        return '2fa:last-timestamp:'.$user->id;
    }
}
