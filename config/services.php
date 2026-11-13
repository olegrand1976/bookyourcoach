<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'stripe' => [
        'model' => env('STRIPE_MODEL', App\Models\User::class),
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook' => [
            'secret' => env('STRIPE_WEBHOOK_SECRET'),
            'tolerance' => env('STRIPE_WEBHOOK_TOLERANCE', 300),
        ],
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'google' => [
        'calendar' => [
            'client_id' => env('GOOGLE_CALENDAR_CLIENT_ID'),
            'client_secret' => env('GOOGLE_CALENDAR_CLIENT_SECRET'),
            'redirect_uri' => env('GOOGLE_CALENDAR_REDIRECT_URI'),
            'application_name' => env('GOOGLE_CALENDAR_APPLICATION_NAME', 'Activibe Calendar'),
        ],
    ],

    // Veille planning : une issue GitHub par jour signalé, pour analyse et correction (dépôt public :
    // jamais de nom ni de donnée personnelle, seulement des identifiants). Inactif sans jeton.
    'github_planning_issues' => [
        'token' => env('GITHUB_PLANNING_ISSUES_TOKEN'),
        'repo' => env('GITHUB_PLANNING_ISSUES_REPO', 'olegrand1976/bookyourcoach'),
        'label' => env('GITHUB_PLANNING_ISSUES_LABEL', 'veille-planning'),
    ],

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-2.5-flash'),
        'text_models' => [
            'gemini-1.5-pro',
            'gemini-1.5-flash',
            'gemini-2.5-flash',
        ],
        'image_models' => [
            'imagen-4.0-generate-001',
            'imagen-4.0-fast-generate-001',
        ],
    ],

];
