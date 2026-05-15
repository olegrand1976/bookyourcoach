<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Logs détaillés validation récurrence (POST /lessons)
    |--------------------------------------------------------------------------
    |
    | Si true : chaque conflit (cours ou subscription_recurring_slots) est
    | journalisé avec IDs, horaires UTC et contexte — utile pour diagnostiquer
    | les 422 « Conflits détectés sur N occurrence(s) » en production.
    | Désactiver (false) si le volume de logs est trop élevé.
    |
    */
    'log_recurring_validation_conflicts' => env('RECURRING_VALIDATION_LOG_CONFLICTS', false),

    /*
    |--------------------------------------------------------------------------
    | Rapport veille : planning + analyse IA pour les responsables club
    |--------------------------------------------------------------------------
    |
    | La commande club:send-daily-planning-insights envoie la veille le planning du
    | lendemain (timezone) aux e-mails stakeholder (owner/manager/admin), par défaut
    | à 8h. Désactiver le schedule sans bloquer l’appel manuel de la commande.
    |
    */
    'club_daily_planning_insight' => [
        'enabled' => env('CLUB_DAILY_PLANNING_INSIGHT_ENABLED', true),
        'schedule_time' => env('CLUB_DAILY_PLANNING_INSIGHT_TIME', '08:00'),
        'timezone' => env('CLUB_DAILY_PLANNING_INSIGHT_TIMEZONE', 'Europe/Brussels'),
        'use_ai' => env('CLUB_DAILY_PLANNING_INSIGHT_USE_AI', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Conseil créneaux récurrents (prévisualisation + enrichissement 422)
    |--------------------------------------------------------------------------
    */
    'recurring_planning_advice' => [
        'timezone' => env('RECURRING_PLANNING_ADVICE_TIMEZONE', 'Europe/Brussels'),
        'use_ai' => env('RECURRING_PLANNING_ADVICE_USE_AI', true),
        'attach_on_validation_failure' => env('RECURRING_PLANNING_ADVICE_ATTACH_ON_422', true),
        'max_candidates_to_validate' => (int) env('RECURRING_PLANNING_ADVICE_MAX_CANDIDATES', 120),
        'max_alternatives_returned' => (int) env('RECURRING_PLANNING_ADVICE_MAX_ALTERNATIVES', 12),
    ],

];
