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
        private readonly ClientIpResolver $clientIp,
        private readonly GeoLocationResolver $geo,
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
            $ip = $this->clientIp->resolve($request);
            $agent = (string) $request->userAgent();
            $summary = $this->summariseUserAgent($agent);

            return LoginAttempt::create(array_merge([
                'user_id' => $user?->id,
                'email' => $email ? mb_substr($email, 0, 255) : null,
                'successful' => $successful,
                'failure_reason' => $reason,
                'ip_address' => $ip,
                'forwarded_for' => $this->clientIp->rawChain($request),
                'user_agent' => $agent !== '' ? mb_substr($agent, 0, 1000) : null,
                'device_type' => $summary['device_type'],
                'browser' => $summary['browser'],
                'platform' => $summary['platform'],
            ], $this->fitToColumns($this->geo->locate($ip))));
        } catch (\Throwable $e) {
            Log::warning('Historique de connexion : écriture impossible', [
                'email' => $email,
                'exception' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Ajuste les valeurs GeoLite2 à la largeur des colonnes.
     *
     * Un nom d'opérateur ou de ville plus long que la colonne ferait échouer
     * l'insertion en MySQL strict — et comme l'écriture est volontairement
     * silencieuse, la connexion ne serait pas tracée du tout. Mieux vaut une
     * valeur tronquée qu'une ligne perdue.
     *
     * @param  array<string, mixed>  $location
     * @return array<string, mixed>
     */
    private function fitToColumns(array $location): array
    {
        $largeurs = [
            'country_code' => 2,
            'country' => 100,
            'region' => 100,
            'city' => 100,
            'time_zone' => 64,
            'organisation' => 150,
        ];

        foreach ($largeurs as $champ => $largeur) {
            if (is_string($location[$champ] ?? null)) {
                $location[$champ] = mb_substr($location[$champ], 0, $largeur);
            }
        }

        // Colonne unsignedSmallInteger : au-delà, on préfère ne rien affirmer.
        if (isset($location['accuracy_radius_km'])) {
            $rayon = (int) $location['accuracy_radius_km'];
            $location['accuracy_radius_km'] = ($rayon >= 0 && $rayon <= 65535) ? $rayon : null;
        }

        return $location;
    }

    /**
     * Lecture volontairement sommaire de l'agent utilisateur : de quoi distinguer
     * un téléphone d'un poste et reconnaître son propre appareil dans la liste.
     * Ce n'est pas une empreinte, et ça ne prétend pas à l'exhaustivité.
     *
     * @return array{device_type: ?string, browser: ?string, platform: ?string}
     */
    public function summariseUserAgent(string $agent): array
    {
        if (trim($agent) === '') {
            return ['device_type' => null, 'browser' => null, 'platform' => null];
        }

        $platform = match (true) {
            str_contains($agent, 'iPhone') => 'iOS',
            str_contains($agent, 'iPad') => 'iPadOS',
            str_contains($agent, 'Android') => 'Android',
            str_contains($agent, 'Windows NT') => 'Windows',
            str_contains($agent, 'Mac OS X') => 'macOS',
            str_contains($agent, 'CrOS') => 'ChromeOS',
            str_contains($agent, 'Linux') => 'Linux',
            default => null,
        };

        // L'ordre compte : Edge et Opera se présentent aussi comme Chrome,
        // Chrome se présente comme Safari.
        $browser = match (true) {
            str_contains($agent, 'Edg/') => 'Edge',
            str_contains($agent, 'OPR/') || str_contains($agent, 'Opera') => 'Opera',
            str_contains($agent, 'Firefox/') => 'Firefox',
            str_contains($agent, 'Chrome/') => 'Chrome',
            str_contains($agent, 'Safari/') => 'Safari',
            str_contains($agent, 'Dart/') || str_contains($agent, 'Flutter') => 'Application mobile',
            default => null,
        };

        $deviceType = match (true) {
            str_contains($agent, 'iPad') || str_contains($agent, 'Tablet') => 'tablette',
            str_contains($agent, 'Mobile') || str_contains($agent, 'iPhone') || str_contains($agent, 'Android') => 'mobile',
            $platform !== null => 'ordinateur',
            default => null,
        };

        return ['device_type' => $deviceType, 'browser' => $browser, 'platform' => $platform];
    }
}
