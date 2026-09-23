<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Historique des connexions : réussies comme refusées.
 *
 * Les adresses IP et la localisation sont des données personnelles. La table est
 * purgée automatiquement (auth:prune-login-history), chaque personne accède à son
 * propre historique, et seuls les administrateurs voient celui des autres.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('login_attempts', function (Blueprint $table) {
            $table->id();

            // Nul sur un échec visant une adresse inconnue : la tentative reste utile.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('email')->nullable();
            $table->boolean('successful')->default(false);
            $table->string('failure_reason', 50)->nullable();

            $table->string('ip_address', 45)->nullable();
            // Chaîne X-Forwarded-For brute : permet de vérifier après coup que la
            // bonne entrée a été retenue, sans redéployer.
            $table->string('forwarded_for', 500)->nullable();

            $table->text('user_agent')->nullable();
            $table->string('device_type', 20)->nullable();
            $table->string('browser', 50)->nullable();
            $table->string('platform', 50)->nullable();

            $table->string('country_code', 2)->nullable();
            $table->string('country', 100)->nullable();
            $table->string('region', 100)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('time_zone', 64)->nullable();
            $table->string('organisation', 150)->nullable();
            // Rayon annoncé par GeoLite2 : rappelle que la ville est une estimation.
            $table->unsignedSmallInteger('accuracy_radius_km')->nullable();

            $table->timestamp('created_at')->nullable();

            $table->index(['user_id', 'created_at']);
            $table->index(['email', 'created_at']);
            $table->index(['ip_address', 'created_at']);
            // Purge et relevés d'activité récente.
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_attempts');
    }
};
