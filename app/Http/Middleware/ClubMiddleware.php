<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ClubMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        
        \Log::info('ClubMiddleware - Vérification accès', [
            'path' => $request->path(),
            'method' => $request->method(),
            'has_user' => (bool) $user,
            'user_id' => optional($user)->id,
            'user_email' => optional($user)->email,
            'user_role' => optional($user)->role,
            'has_bearer' => (bool) $request->bearerToken(),
            'bearer_preview' => $request->bearerToken() ? substr($request->bearerToken(), 0, 10) . '...' : null,
        ]);
        
        if ($user && $user->role === 'club') {
            \Log::info('ClubMiddleware - Accès autorisé', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'path' => $request->path(),
            ]);
            return $next($request);
        }

        \Log::warning('ClubMiddleware - Accès refusé (403)', [
            'user_id' => optional($user)->id,
            'user_email' => optional($user)->email,
            'role' => optional($user)->role,
            'expected_role' => 'club',
            'has_bearer' => (bool) $request->bearerToken(),
            'path' => $request->path(),
            'method' => $request->method(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Accès non autorisé. Rôle club requis.',
            'required_role' => 'club',
            'user_role' => optional($user)->role
        ], 403);
    }
}
