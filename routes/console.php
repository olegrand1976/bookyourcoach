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
