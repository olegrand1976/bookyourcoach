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
        Schema::table('app_settings', function (Blueprint $table) {
            // Ajouter les nouveaux champs pour le système clé-valeur
            $table->string('key')->nullable()->index();
            $table->text('value')->nullable();
            $table->string('type')->default('string');
            $table->string('group')->nullable()->index();

            // Créer un index unique sur key + group
            $table->unique(['key', 'group']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('app_settings', function (Blueprint $table) {
            $table->dropUnique(['key', 'group']);
            $table->dropColumn(['key', 'value', 'type', 'group']);
        });
    }
};
