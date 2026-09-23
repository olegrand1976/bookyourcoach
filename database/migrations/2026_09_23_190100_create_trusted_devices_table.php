<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Appareils dispensés du code 2FA pendant une durée limitée.
 *
 * Seule l'empreinte SHA-256 du jeton est stockée : le jeton en clair ne vit que
 * dans le navigateur. Navigateur et IP servent uniquement à afficher la liste,
 * pour que la personne reconnaisse — et révoque — ses appareils.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trusted_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('token_hash', 64)->unique();
            $table->string('user_agent', 500)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index(['user_id', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trusted_devices');
    }
};
