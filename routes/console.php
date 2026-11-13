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

// La génération des cours des séries est faite par recurring-slots:prolong (02:30), qui ne traite que les séries
// en cours. recurring-slots:generate-lessons n'est plus planifiée (doublon ; à l'origine de l'incident du 12/11/2026) :
// elle reste disponible à la main, avec un --dry-run qui simule réellement.

// Planifier l'avenir : prolonger les récurrences des élèves ayant un abonnement actif
// (horizon 26 semaines, borné à l'expiration des abonnements) et générer les cours.
// Quotidien : une série ne doit plus s'arrêter tant que l'abonnement est actif.
Schedule::command('recurring-slots:prolong')
    ->dailyAt('02:30')
    ->timezone('Europe/Brussels')
    ->onSuccess(function () {
        \Log::info('Prolongation automatique des récurrences (horizon 26 semaines) terminée');
    })
    ->onFailure(function () {
        \Log::error('Échec de la prolongation automatique des récurrences');
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

// Relances e-mail (48 h et 24 h avant le cours) pour les demandes de remplacement en attente
Schedule::command('lesson-replacements:send-pending-reminders')
    ->hourly()
    ->timezone('Europe/Brussels')
    ->onFailure(function () {
        \Log::error('Échec des relances automatiques demandes de remplacement');
    });

// Veille (matin 8h par défaut) : planning du lendemain + analyse IA (Gemini) pour optimisation et contraintes famille
if (config('bookyourcoach.club_daily_planning_insight.enabled', true)) {
    $insightTz = config('bookyourcoach.club_daily_planning_insight.timezone', 'Europe/Brussels');
    $insightTime = config('bookyourcoach.club_daily_planning_insight.schedule_time', '08:00');
    Schedule::command('club:send-daily-planning-insights')
        ->dailyAt($insightTime)
        ->timezone($insightTz)
        ->onFailure(function () {
            \Log::error('Échec de l\'envoi des rapports veille planning club');
        });
}

// Veille abonnements : e-mail au responsable du club 2 jours avant chaque jour de cours
// (config lead_days), élèves dont l'abonnement arrive en fin de vie — dès 3 séances restantes
// (rien n'est envoyé sans cours ni abonnement concerné)
if (config('bookyourcoach.subscription_expiry_watch.enabled', true)) {
    Schedule::command('subscriptions:watch-expiring')
        ->dailyAt(config('bookyourcoach.subscription_expiry_watch.schedule_time', '07:45'))
        ->timezone(config('bookyourcoach.subscription_expiry_watch.timezone', 'Europe/Brussels'))
        ->onFailure(function () {
            \Log::error('Échec de la veille des abonnements en fin de vie');
        });
}

// Veille planning : la veille de chaque jour de cours, contrôle de cohérence du planning du lendemain
// (capacité, doubles réservations, cours générés ou réapparus à tort…) ; e-mail aux responsables du club
// uniquement en cas d'incohérence.
Schedule::command('planning:check-consistency')
    ->dailyAt('18:00')
    ->timezone('Europe/Brussels')
    ->onFailure(function () {
        \Log::error('Échec de la veille planning');
    });

// Purge des jetons d'accès dormants (expiration glissante, cf. bookyourcoach.auth.token_idle_days)
Schedule::command('auth:prune-idle-tokens')
    ->dailyAt('03:30')
    ->timezone('Europe/Brussels')
    ->onFailure(function () {
        \Log::error('Échec de la purge des jetons dormants');
    });

// Purge de l'historique des connexions (données personnelles, durée assumée)
Schedule::command('auth:prune-login-history')
    ->dailyAt('03:45')
    ->timezone('Europe/Brussels')
    ->onFailure(function () {
        \Log::error('Échec de la purge de l\'historique de connexion');
    });
