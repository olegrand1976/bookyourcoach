<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->string('app_name')->default('BookYourCoach');
            $table->string('primary_color')->default('#2563eb'); // Bleu principal
            $table->string('secondary_color')->default('#1e40af'); // Bleu secondaire
            $table->string('accent_color')->default('#3b82f6'); // Bleu accent
            $table->string('logo_url')->nullable(); // URL du logo
            $table->string('logo_path')->nullable(); // Chemin local du logo
            $table->text('app_description')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->json('social_links')->nullable(); // Liens réseaux sociaux
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_settings');
    }
};
