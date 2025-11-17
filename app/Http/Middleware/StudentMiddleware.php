<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class StudentMiddleware
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
        
        // Si l'utilisateur n'est pas authentifié, retourner 401
        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated',
                'error' => 'Missing token'
            ], 401);
        }

        // Si l'utilisateur est authentifié mais n'a pas le rôle student, retourner 403
        if ($user->role !== 'student') {
            return response()->json([
                'message' => 'Unauthorized',
                'error' => 'Access denied. Student role required.'
            ], 403);
        }

        return $next($request);
    }
}
