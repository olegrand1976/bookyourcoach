<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'teacher' => \App\Http\Middleware\TeacherMiddleware::class,
            'student' => \App\Http\Middleware\StudentMiddleware::class,
            'club' => \App\Http\Middleware\ClubMiddleware::class,
            'force.json' => \App\Http\Middleware\ForceJsonResponse::class,
            'active.student' => \App\Http\Middleware\SetActiveStudentContext::class,
        ]);
        
        // Appliquer le middleware CORS et ForceJsonResponse à toutes les routes API
        $middleware->group('api', [
            \Illuminate\Http\Middleware\HandleCors::class,
            \App\Http\Middleware\ForceJsonResponse::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Ajouter les en-têtes CORS aux réponses d'erreur (ex. 500) pour les routes API,
        // sinon le navigateur bloque avec "Access-Control-Allow-Origin manquant" au lieu d'afficher l'erreur.
        $exceptions->respond(function ($response, $e, $request) {
            if (! $request->is('api/*')) {
                return $response;
            }
            try {
                if (class_exists(\Fruitcake\Cors\CorsService::class)) {
                    $cors = app(\Fruitcake\Cors\CorsService::class);
                    $cors->setOptions(config('cors', []));

                    return $cors->addActualRequestHeaders($response, $request);
                }
            } catch (\Throwable) {
                // Fallback ci-dessous
            }

            $origin = $request->headers->get('Origin');
            $allowed = array_values(array_filter((array) config('cors.allowed_origins', [])));
            if ($origin && in_array($origin, $allowed, true)) {
                $response->headers->set('Access-Control-Allow-Origin', $origin);
                if (config('cors.supports_credentials')) {
                    $response->headers->set('Access-Control-Allow-Credentials', 'true');
                }
                $response->headers->set('Vary', 'Origin', false);
            }

            return $response;
        });
    })->create();
