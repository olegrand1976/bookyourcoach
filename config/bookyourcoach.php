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

];
