<?php

namespace App\Services;

use GeoIp2\Database\Reader;
use Illuminate\Support\Facades\Log;

/**
 * Localisation d'une adresse IP, résolue hors ligne avec les bases GeoLite2.
 *
 * Choix assumé : aucune adresse de vos utilisateurs n'est envoyée à un service
 * tiers. La contrepartie est qu'il faut déposer les fichiers .mmdb et les tenir à
 * jour ; en leur absence, la localisation reste simplement vide — elle n'est jamais
 * bloquante pour une connexion.
 *
 * Précision à garder en tête : sur une ligne grand public, une géolocalisation par
 * IP donne au mieux la ville de rattachement du fournisseur d'accès. Elle situe une
 * connexion, elle ne localise pas une personne.
 */
class GeoLocationResolver
{
    private ?Reader $cityReader = null;

    private ?Reader $asnReader = null;

    private bool $cityChecked = false;

    private bool $asnChecked = false;

    /**
     * @return array{country_code: ?string, country: ?string, region: ?string, city: ?string, time_zone: ?string, organisation: ?string, accuracy_radius_km: ?int}
     */
    public function locate(?string $ip): array
    {
        $vide = [
            'country_code' => null,
            'country' => null,
            'region' => null,
            'city' => null,
            'time_zone' => null,
            'organisation' => null,
            'accuracy_radius_km' => null,
        ];

        if (! $ip || filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return $vide;
        }

        // Adresse privée ou réservée : rien à localiser (réseau interne, tests).
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return $vide;
        }

        $resultat = $vide;

        try {
            $city = $this->cityReader()?->city($ip);
            if ($city) {
                $resultat['country_code'] = $city->country->isoCode;
                $resultat['country'] = $city->country->name;
                $resultat['region'] = $city->mostSpecificSubdivision->name;
                $resultat['city'] = $city->city->name;
                $resultat['time_zone'] = $city->location->timeZone;
                $resultat['accuracy_radius_km'] = $city->location->accuracyRadius;
            }
        } catch (\Throwable $e) {
            // Adresse absente de la base, fichier corrompu : on n'échoue jamais ici.
            $this->noter('city', $e);
        }

        try {
            $asn = $this->asnReader()?->asn($ip);
            if ($asn) {
                $resultat['organisation'] = $asn->autonomousSystemOrganization;
            }
        } catch (\Throwable $e) {
            $this->noter('asn', $e);
        }

        return $resultat;
    }

    public function isAvailable(): bool
    {
        return $this->cityReader() !== null;
    }

    private function cityReader(): ?Reader
    {
        if (! $this->cityChecked) {
            $this->cityChecked = true;
            $this->cityReader = $this->ouvrir((string) config('bookyourcoach.auth.geoip_city_database'));
        }

        return $this->cityReader;
    }

    private function asnReader(): ?Reader
    {
        if (! $this->asnChecked) {
            $this->asnChecked = true;
            $this->asnReader = $this->ouvrir((string) config('bookyourcoach.auth.geoip_asn_database'));
        }

        return $this->asnReader;
    }

    private function ouvrir(string $chemin): ?Reader
    {
        if ($chemin === '' || ! is_readable($chemin)) {
            return null;
        }

        try {
            // Noms en français d'abord (« Bruxelles, Belgique »), anglais à défaut.
            return new Reader($chemin, ['fr', 'en']);
        } catch (\Throwable $e) {
            Log::warning('GeoLite2 : base illisible', ['path' => $chemin, 'exception' => $e->getMessage()]);

            return null;
        }
    }

    private function noter(string $base, \Throwable $e): void
    {
        // Une adresse inconnue de la base est le cas courant, pas une anomalie :
        // on ne journalise que les erreurs inattendues.
        if ($e instanceof \GeoIp2\Exception\AddressNotFoundException) {
            return;
        }

        Log::debug("GeoLite2 ({$base}) : résolution impossible", ['exception' => $e->getMessage()]);
    }
}
