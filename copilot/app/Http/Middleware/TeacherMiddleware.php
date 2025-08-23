<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class TeacherMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Non authentifié'], 401);
        }

        $user = Auth::user();

        // Vérifier si l'utilisateur peut agir en tant qu'enseignant
        // (admin ou possède un profil enseignant)
        if (!$user->canActAsTeacher()) {
            return response()->json([
                'message' => 'Accès refusé - Droits enseignant requis'
            ], 403);
        }

        return $next($request);
    }
}
