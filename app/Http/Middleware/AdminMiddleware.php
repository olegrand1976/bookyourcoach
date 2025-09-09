<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     * 
     * Middleware personnalisé pour éviter les problèmes SIGSEGV avec Sanctum.
     * Gère l'authentification via token Bearer et vérifie le rôle admin.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Authentification alternative pour éviter le problème Sanctum
        $token = $request->header('Authorization');
        
        if (!$token || !str_starts_with($token, 'Bearer ')) {
            return response()->json(['message' => 'Token manquant'], 401);
        }
        
        $token = substr($token, 7); // Enlever "Bearer "
        
        // Vérifier le token dans la base de données
        $personalAccessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
        
        if (!$personalAccessToken) {
            return response()->json(['message' => 'Token invalide'], 401);
        }
        
        $user = $personalAccessToken->tokenable;
        
        if (!$user || $user->role !== 'admin') {
            return response()->json(['message' => 'Accès refusé - Droits administrateur requis'], 403);
        }
        
        // Ajouter l'utilisateur à la requête pour compatibilité
        $request->setUserResolver(function () use ($user) {
            return $user;
        });
        
        return $next($request);
    }
}