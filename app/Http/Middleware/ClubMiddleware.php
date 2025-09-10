<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ClubMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Vérifier si l'utilisateur a le rôle club ou est admin
        if ($user->role !== 'club' && $user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized. User does not have club privileges.'], 403);
        }

        // Vérifier si l'utilisateur est associé à un club
        if (!$user->clubs()->exists()) {
            return response()->json(['message' => 'User is not associated with any club.'], 403);
        }

        return $next($request);
    }
}
