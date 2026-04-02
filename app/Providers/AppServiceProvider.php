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
     * MailHog en dev : évite d’envoyer via Mailjet / SMTP prod présents dans .env.
     *
     * - MAIL_USE_MAILHOG=true (recommandé dans docker-compose) : forcé même si APP_ENV=production.
     * - Sinon : activé seulement si APP_ENV vaut local ou development.
     * - MAIL_USE_MAILHOG=false : désactivé.
     *
     * Utilise getenv/$_SERVER en priorité : avec config:cache, env() ne voit plus les variables
     * injectées par Docker (MAIL_MAILHOG_HOST=mailhog, etc.).
     */
    private function configureLocalMailhog(): void
    {
        $flag = $this->runningEnvString('MAIL_USE_MAILHOG');

        if ($flag !== null && $flag !== '') {
            if (! filter_var($flag, FILTER_VALIDATE_BOOLEAN)) {
                return;
            }
            $useMailhog = true;
        } else {
            $useMailhog = $this->app->environment(['local', 'development']);
        }

        if (! $useMailhog) {
            return;
        }

        $host = $this->runningEnvString('MAIL_MAILHOG_HOST') ?: '127.0.0.1';
        $port = (int) ($this->runningEnvString('MAIL_MAILHOG_PORT') ?: '1025');

        config([
            'mail.default' => 'mailhog',
            'mail.mailers.mailhog.host' => $host,
            'mail.mailers.mailhog.port' => $port,
        ]);
    }

    /**
     * Valeur d’environnement au runtime (Docker, php-fpm, CLI) — pas seulement .env non caché.
     */
    private function runningEnvString(string $key): ?string
    {
        $v = getenv($key);
        if ($v !== false && $v !== '') {
            return $v;
        }
        if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') {
            return (string) $_SERVER[$key];
        }
        if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
            return (string) $_ENV[$key];
        }
        $fromEnv = env($key);
        if ($fromEnv !== null && $fromEnv !== '') {
            return $fromEnv;
        }

        return null;
    }
}
