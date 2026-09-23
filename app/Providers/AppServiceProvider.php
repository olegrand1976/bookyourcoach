<?php

namespace App\Providers;

use App\Models\Lesson;
use App\Services\ClientIpResolver;
use App\Observers\LessonObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Lecteurs GeoLite2 : un seul ouvert par requête plutôt qu'un par tentative.
        $this->app->singleton(\App\Services\GeoLocationResolver::class);
        $this->app->singleton(\App\Services\ClientIpResolver::class);

        $this->app->singleton(\App\Services\Neo4jService::class, function ($app) {
            if (!filter_var(env('NEO4J_ENABLED', true), FILTER_VALIDATE_BOOLEAN)) {
                return new class {
                    public function __call($method, $args) {
                        return ['error' => 'Neo4j disabled (NEO4J_ENABLED=false)'];
                    }
                };
            }
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

        $this->configureRateLimiters();
        $this->configurePasswordPolicy();
        $this->configureAccessTokenExpiry();
    }

    /**
     * Limiteurs des points d'entrée sensibles au brute-force.
     */
    private function configureRateLimiters(): void
    {
        RateLimiter::for('family-link', function (Request $request) {
            $userId = optional($request->user())->id ?: 'guest';
            return [
                Limit::perMinutes(10, 5)->by('family-link:user:' . $userId),
                Limit::perMinutes(10, 15)->by('family-link:ip:' . $request->ip()),
            ];
        });

        // Connexion : clé composite e-mail + IP. L'IP seule laisse passer le bourrage
        // d'identifiants depuis un réseau distribué ; l'e-mail seul permettrait de
        // verrouiller le compte d'autrui en le harcelant. Les deux limites coexistent.
        RateLimiter::for('login', function (Request $request) {
            $attempts = (int) config('bookyourcoach.auth.login_max_attempts', 5);
            $decay = (int) config('bookyourcoach.auth.login_decay_minutes', 10);
            $email = strtolower(trim((string) $request->input('email')));
            // $request->ip() renvoie le relais interne de Cloud Run, identique pour
            // tout le monde : le plafond « par IP » serait en réalité un plafond
            // global, capable de bloquer toute la plateforme.
            $ip = app(ClientIpResolver::class)->resolve($request) ?? $request->ip();

            return [
                Limit::perMinutes($decay, $attempts)->by('login:' . $email . '|' . $ip),
                Limit::perMinutes($decay, $attempts * 4)->by('login:ip:' . $ip),
            ];
        });

        // Création de compte et parcours de réinitialisation : plus rares, donc plus stricts.
        RateLimiter::for('auth-sensitive', function (Request $request) {
            $email = strtolower(trim((string) $request->input('email')));
            $ip = app(ClientIpResolver::class)->resolve($request) ?? $request->ip();

            return [
                Limit::perMinutes(10, 3)->by('auth-sensitive:' . $email . '|' . $ip),
                Limit::perMinutes(10, 10)->by('auth-sensitive:ip:' . $ip),
            ];
        });
    }

    /**
     * Politique de mot de passe. Par défaut Laravel n'impose que 8 caractères :
     * « password123 » y était conforme.
     */
    private function configurePasswordPolicy(): void
    {
        Password::defaults(function () {
            $rule = Password::min((int) config('bookyourcoach.auth.password_min_length', 12))
                ->letters()
                ->numbers();

            // L'appel HaveIBeenPwned (k-anonymat : le mot de passe n'est jamais transmis)
            // ne doit pas rendre la suite de tests dépendante du réseau.
            if (config('bookyourcoach.auth.password_uncompromised', true) && ! app()->environment('testing')) {
                $rule->uncompromised();
            }

            return $rule;
        });
    }

    /**
     * Expiration glissante des jetons : le réglage « expiration » de Sanctum est absolu,
     * calculé depuis la création. On refuse ici tout jeton dormant depuis plus de N jours,
     * last_used_at étant mis à jour à chaque requête — un utilisateur actif reste connecté.
     */
    private function configureAccessTokenExpiry(): void
    {
        Sanctum::authenticateAccessTokensUsing(function ($accessToken, bool $isValid) {
            if (! $isValid) {
                return false;
            }

            $idleDays = (int) config('bookyourcoach.auth.token_idle_days', 30);
            if ($idleDays <= 0) {
                return true;
            }

            $lastUsed = $accessToken->last_used_at ?? $accessToken->created_at;
            if ($lastUsed === null) {
                return true;
            }

            return Carbon::parse($lastUsed)->gt(now()->subDays($idleDays));
        });
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
