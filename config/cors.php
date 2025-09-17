<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        env('FRONTEND_URL', 'http://localhost:3000'),
        'http://91.134.77.98:3000', // Ajout pour la production
        'https://91.134.77.98:3000', // Ajout pour la production (HTTPS)
        'https://activibe.be', // Domaine de production
        'https://www.activibe.be', // Domaine de production avec www
        'http://activibe.be', // Domaine de production (HTTP)
        'http://www.activibe.be', // Domaine de production avec www (HTTP)
        'http://localhost:3001',
        'http://localhost:3004', // Ajout pour l'application Flutter web
        'http://127.0.0.1:3000',
        'http://127.0.0.1:3001',
        'http://127.0.0.1:3004', // Ajout pour l'application Flutter web
        'http://localhost:8080', // Ajout pour Nginx
        'http://localhost:8081', // Ajout pour le serveur de dev Laravel
        'http://localhost:8083', // Ajout pour l'application mobile Flutter
        'http://127.0.0.1:8083', // Ajout pour l'application mobile Flutter (127.0.0.1)
        'http://localhost:8084', // Ajout pour l'application mobile Flutter (port actuel)
        'http://127.0.0.1:8084', // Ajout pour l'application mobile Flutter (127.0.0.1, port actuel)
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
