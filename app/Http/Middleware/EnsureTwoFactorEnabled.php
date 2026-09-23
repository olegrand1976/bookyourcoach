<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Refuse l'accès aux comptes club et admin qui n'ont pas configuré leur double
 * authentification.
 *
 * Le login ne délivre déjà plus de jeton sans second facteur ; ce garde-fou couvre
 * les jetons émis avant la mise en place de la 2FA, et tout chemin qui aurait
 * échappé au login. Sans effet pour les enseignants et les élèves.
 */
class EnsureTwoFactorEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->requiresTwoFactor() && ! $user->hasTwoFactorEnabled()) {
            return response()->json([
                'success' => false,
                'code' => 'two_factor_setup_required',
                'message' => 'La double authentification est obligatoire pour ce compte : reconnectez-vous pour la configurer.',
            ], 403);
        }

        return $next($request);
    }
}
