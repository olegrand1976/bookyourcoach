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
        // Ajouter un index sur postal_code pour les clubs
        Schema::table('clubs', function (Blueprint $table) {
            $table->index('postal_code');
        });

        // Ajouter un index sur postal_code pour les users
        Schema::table('users', function (Blueprint $table) {
            $table->index('postal_code');
        });

        // Ajouter un index sur postal_code pour les profiles
        Schema::table('profiles', function (Blueprint $table) {
            $table->index('postal_code');
        });

        // Ajouter un index sur postal_code pour les locations
        Schema::table('locations', function (Blueprint $table) {
            $table->index('postal_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Supprimer les index
        Schema::table('clubs', function (Blueprint $table) {
            $table->dropIndex(['postal_code']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['postal_code']);
        });

        Schema::table('profiles', function (Blueprint $table) {
            $table->dropIndex(['postal_code']);
        });

        Schema::table('locations', function (Blueprint $table) {
            $table->dropIndex(['postal_code']);
        });
    }
};
