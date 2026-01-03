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
        // Ne pas forcer JSON pour les requêtes OPTIONS (preflight CORS)
        if ($request->isMethod('OPTIONS')) {
            return $next($request);
        }
        
        // Ne pas forcer JSON pour les webhooks Stripe (ils ont leur propre format)
        if ($request->is('api/stripe/webhook')) {
            return $next($request);
        }
        
        // Forcer le header Accept pour les requêtes API
        if ($request->is('api/*')) {
            $request->headers->set('Accept', 'application/json');
            // Ne pas forcer Content-Type sur les requêtes sans body
            if ($request->hasHeader('Content-Type')) {
                $request->headers->set('Content-Type', 'application/json; charset=utf-8');
            }
        }
        
        $response = $next($request);
        
        // S'assurer que la réponse est en JSON UTF-8 pour les routes API (sauf OPTIONS et webhooks)
        if ($request->is('api/*') && !$request->isMethod('OPTIONS') && !$request->is('api/stripe/webhook')) {
            $response->headers->set('Content-Type', 'application/json; charset=utf-8');
        }
        
        return $response;
    }
}
