<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Compte double club + enseignant : applique le rôle choisi dans l'en-tête du site
 * (X-Active-Role) pour cette requête. Le choix est propre à chaque appareil ; un
 * rôle que le compte ne détient pas est ignoré, l'en-tête ne donne aucun droit.
 *
 * Placé juste après l'authentification (voir bootstrap/app.php), avant les
 * middlewares de rôle et de 2FA.
 */
class ApplyActiveRole
{
    public const HEADER = 'X-Active-Role';

    public function handle(Request $request, Closure $next): Response
    {
        $wanted = $request->header(self::HEADER);

        if (is_string($wanted) && $wanted !== '' && ($user = $request->user())) {
            $user->actAs($wanted);
        }

        return $next($request);
    }
}
