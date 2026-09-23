<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Journal des demandes de congés du club (fermeture ou réouverture d'une journée).
 *
 * Chaque demande est validée par mot de passe ou code 2FA ; on trace qui l'a faite,
 * d'où, et ce qui en est sorti — y compris les refus. C'est ce qui a manqué le
 * 2026-09-23, où une fermeture accidentelle a dû être reconstituée depuis les
 * journaux de Google.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('club_closure_requests', function (Blueprint $table) {
            $table->id();

            $table->foreignId('club_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->date('closed_on');
            // close | open
            $table->string('action', 10);
            // password | totp
            $table->string('confirmation_method', 20)->nullable();
            // success | invalid_credential | impact_mismatch | error
            $table->string('outcome', 30);
            $table->unsignedInteger('impacted_lessons')->nullable();

            $table->string('ip_address', 45)->nullable();
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
            $table->unsignedSmallInteger('accuracy_radius_km')->nullable();

            $table->timestamp('created_at')->nullable();

            $table->index(['club_id', 'closed_on']);
            $table->index(['club_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('club_closure_requests');
    }
};
