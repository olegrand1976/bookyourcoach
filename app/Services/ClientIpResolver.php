<?php

namespace App\Services;

use Illuminate\Http\Request;

/**
 * Adresse réelle du visiteur derrière les relais de Google Cloud.
 *
 * Sans cela, `$request->ip()` renvoie le relais interne de Cloud Run — la même
 * adresse pour tout le monde. L'incident du 2026-09-23 n'a pu être reconstitué que
 * grâce aux journaux de Google, pas à ceux de l'application, et le plafond de
 * tentatives par IP retombait en pratique sur un compteur global.
 *
 * La chaîne X-Forwarded-For se lit de droite à gauche : chaque relais ajoute à la
 * fin. Ce qui vient du client, à gauche, peut avoir été forgé ; on ne s'y fie donc
 * jamais, mais on le conserve intégralement pour pouvoir vérifier après coup.
 */
class ClientIpResolver
{
    public function resolve(Request $request): ?string
    {
        $chain = $this->chain($request);

        if ($chain === []) {
            return $request->ip();
        }

        $hops = max(0, (int) config('bookyourcoach.auth.trusted_proxy_hops', 1));
        $index = count($chain) - 1 - $hops;

        // Chaîne plus courte que le nombre de relais attendus : la requête n'est pas
        // passée par le chemin prévu. Ce qui reste vient du client et est donc
        // forgeable — on préfère l'adresse du pair direct, moins précise mais non
        // falsifiable, plutôt qu'une valeur que n'importe qui peut écrire.
        if ($index < 0) {
            return $request->ip() ?: null;
        }

        return $this->normalize($chain[$index]) ?? ($request->ip() ?: null);
    }

    /**
     * Chaîne X-Forwarded-For telle que reçue, sans interprétation.
     *
     * @return array<int, string>
     */
    public function chain(Request $request): array
    {
        $header = (string) $request->headers->get('X-Forwarded-For', '');

        if (trim($header) === '') {
            return [];
        }

        return array_values(array_filter(
            array_map(fn ($part) => trim($part), explode(',', $header)),
            fn ($part) => $part !== '',
        ));
    }

    /** Chaîne brute conservée dans l'audit, tronquée à la taille de colonne. */
    public function rawChain(Request $request): ?string
    {
        $header = trim((string) $request->headers->get('X-Forwarded-For', ''));

        return $header === '' ? null : mb_substr($header, 0, 500);
    }

    /** Adresse nue (sans crochets ni port), ou null si ce n'est pas une IP. */
    private function normalize(string $candidate): ?string
    {
        // Une IPv6 peut arriver entre crochets avec un port, une IPv4 avec un port.
        if (preg_match('/^\[([^\]]+)\](?::\d+)?$/', $candidate, $m)) {
            $candidate = $m[1];
        } elseif (preg_match('/^(\d{1,3}(?:\.\d{1,3}){3}):\d+$/', $candidate, $m)) {
            $candidate = $m[1];
        }

        return filter_var($candidate, FILTER_VALIDATE_IP) !== false ? $candidate : null;
    }
}
