<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Récupérer automatiquement les plafonds de défraiement chaque 1er janvier à 6h du matin
Schedule::command('volunteer:fetch-expense-limits')
    ->yearlyOn(1, 1, '06:00')
    ->timezone('Europe/Brussels')
    ->onSuccess(function () {
        \Log::info('Plafonds de défraiement mis à jour automatiquement pour ' . now()->year);
    })
    ->onFailure(function () {
        \Log::error('Échec de la mise à jour automatique des plafonds de défraiement pour ' . now()->year);
    });

// Générer automatiquement les lessons à partir des créneaux récurrents
// Exécuté quotidiennement à 2h du matin pour générer les lessons des 3 prochains mois
Schedule::command('recurring-slots:generate-lessons')
    ->dailyAt('02:00')
    ->timezone('Europe/Brussels')
    ->onSuccess(function () {
        \Log::info('Génération automatique des lessons depuis les créneaux récurrents terminée');
    })
    ->onFailure(function () {
        \Log::error('Échec de la génération automatique des lessons depuis les créneaux récurrents');
    });

// Expirer automatiquement les liaisons abonnement-créneau récurrent
// Exécuté quotidiennement à 3h du matin
Schedule::command('recurring-slots:expire-subscriptions')
    ->dailyAt('03:00')
    ->timezone('Europe/Brussels')
    ->onSuccess(function () {
        \Log::info('Expiration automatique des liaisons abonnement-créneau récurrent terminée');
    })
    ->onFailure(function () {
        \Log::error('Échec de l\'expiration automatique des liaisons abonnement-créneau récurrent');
    });

// Consommer automatiquement les cours dont la date/heure est passée
// Exécuté toutes les heures pour consommer les cours qui viennent de passer
Schedule::command('subscriptions:consume-past-lessons')
    ->hourly()
    ->timezone('Europe/Brussels')
    ->onSuccess(function () {
        \Log::info('Consommation automatique des cours passés terminée');
    })
    ->onFailure(function () {
        \Log::error('Échec de la consommation automatique des cours passés');
    });
