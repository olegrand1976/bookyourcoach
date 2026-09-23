<?php

namespace App\Services;

use Illuminate\Http\Request;

/**
 * Origine d'une requête : adresse réelle, appareil et localisation, dans la forme
 * des colonnes communes aux journaux de traçage (connexions, demandes de congés).
 */
class RequestOriginResolver
{
    public function __construct(
        private readonly ClientIpResolver $clientIp,
        private readonly GeoLocationResolver $geo,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function describe(Request $request): array
    {
        $ip = $this->clientIp->resolve($request);
        $agent = (string) $request->userAgent();

        return array_merge([
            'ip_address' => $ip,
            'forwarded_for' => $this->clientIp->rawChain($request),
            'user_agent' => $agent !== '' ? mb_substr($agent, 0, 1000) : null,
        ], $this->summariseUserAgent($agent), $this->fitToColumns($this->geo->locate($ip)));
    }

    /**
     * Ajuste les valeurs GeoLite2 à la largeur des colonnes.
     *
     * Un nom d'opérateur ou de ville plus long que la colonne ferait échouer
     * l'insertion en MySQL strict — et comme l'écriture est volontairement
     * silencieuse, la requête ne serait pas tracée du tout. Mieux vaut une
     * valeur tronquée qu'une ligne perdue.
     *
     * @param  array<string, mixed>  $location
     * @return array<string, mixed>
     */
    public function fitToColumns(array $location): array
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
