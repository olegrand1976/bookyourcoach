<?php

namespace App\Providers;

use App\Models\Lesson;
use App\Observers\LessonObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(\App\Services\Neo4jService::class, function ($app) {
            try {
                return new \App\Services\Neo4jService();
            } catch (\Exception $e) {
                // Si Neo4j n'est pas disponible, retourner un service mock
                return new class {
                    public function __call($method, $args) {
                        return ['error' => 'Neo4j service not available'];
                    }
                };
            }
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Support\Facades\Log::info('AppServiceProvider booted successfully.');

        $this->configureLocalMailhog();
        
        // Enregistrer l'observer pour mettre à jour automatiquement lessons_used
        Lesson::observe(LessonObserver::class);
        
        // Enregistrer l'observer pour gérer automatiquement les récurrences
        \App\Models\SubscriptionInstance::observe(\App\Observers\SubscriptionInstanceObserver::class);
    }

    /**
     * En local, envoyer les mails vers MailHog (pas Mailjet / SMTP prod copié dans .env).
     * Désactiver : MAIL_USE_MAILHOG=false. Hôte Docker : MAIL_MAILHOG_HOST=mailhog (compose).
     */
    private function configureLocalMailhog(): void
    {
        if (! $this->app->environment('local')) {
            return;
        }

        if (! filter_var(env('MAIL_USE_MAILHOG', 'true'), FILTER_VALIDATE_BOOLEAN)) {
            return;
        }

        config(['mail.default' => 'mailhog']);
    }
}
