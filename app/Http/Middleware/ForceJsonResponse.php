<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceJsonResponse
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Forcer le header Accept pour les requêtes API
        if ($request->is('api/*')) {
            $request->headers->set('Accept', 'application/json');
            $request->headers->set('Content-Type', 'application/json; charset=utf-8');
        }
        
        $response = $next($request);
        
        // S'assurer que la réponse est en JSON UTF-8 pour les routes API
        if ($request->is('api/*')) {
            $response->headers->set('Content-Type', 'application/json; charset=utf-8');
        }
        
        return $response;
    }
}
