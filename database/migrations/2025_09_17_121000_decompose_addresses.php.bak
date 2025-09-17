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
        // 1. Modifier la table clubs
        Schema::table('clubs', function (Blueprint $table) {
            // Ajouter les nouveaux champs
            $table->string('street')->nullable()->after('phone');
            $table->string('street_number')->nullable()->after('street');
            $table->string('street_box')->nullable()->after('street_number'); // Pour les boîtes (ex: 123/A, 123B)
            
            // Garder le champ address pour compatibilité (sera rempli automatiquement)
            // Le champ address existe déjà, on le garde
        });

        // 2. Modifier la table users
        Schema::table('users', function (Blueprint $table) {
            // Ajouter les nouveaux champs
            $table->string('street')->nullable()->after('phone');
            $table->string('street_number')->nullable()->after('street');
            $table->string('street_box')->nullable()->after('street_number');
            
            // Le champ address n'existe pas dans users, on l'ajoute pour compatibilité
            $table->text('address')->nullable()->after('street_box');
        });

        // 3. Modifier la table profiles
        Schema::table('profiles', function (Blueprint $table) {
            // Ajouter les nouveaux champs
            $table->string('street')->nullable()->after('phone');
            $table->string('street_number')->nullable()->after('street');
            $table->string('street_box')->nullable()->after('street_number');
            
            // Le champ address existe déjà, on le garde
        });

        // 4. Modifier la table locations
        Schema::table('locations', function (Blueprint $table) {
            // Ajouter les nouveaux champs
            $table->string('street')->nullable()->after('name');
            $table->string('street_number')->nullable()->after('street');
            $table->string('street_box')->nullable()->after('street_number');
            
            // Le champ address existe déjà, on le garde
        });

        // 5. Ajouter des index pour optimiser les recherches
        Schema::table('clubs', function (Blueprint $table) {
            $table->index(['street', 'street_number']);
            $table->index(['postal_code', 'city']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index(['street', 'street_number']);
            $table->index(['postal_code', 'city']);
        });

        Schema::table('profiles', function (Blueprint $table) {
            $table->index(['street', 'street_number']);
            $table->index(['postal_code', 'city']);
        });

        Schema::table('locations', function (Blueprint $table) {
            $table->index(['street', 'street_number']);
            $table->index(['postal_code', 'city']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Supprimer les index
        Schema::table('clubs', function (Blueprint $table) {
            $table->dropIndex(['street', 'street_number']);
            $table->dropIndex(['postal_code', 'city']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['street', 'street_number']);
            $table->dropIndex(['postal_code', 'city']);
        });

        Schema::table('profiles', function (Blueprint $table) {
            $table->dropIndex(['street', 'street_number']);
            $table->dropIndex(['postal_code', 'city']);
        });

        Schema::table('locations', function (Blueprint $table) {
            $table->dropIndex(['street', 'street_number']);
            $table->dropIndex(['postal_code', 'city']);
        });

        // Supprimer les colonnes ajoutées
        Schema::table('clubs', function (Blueprint $table) {
            $table->dropColumn(['street', 'street_number', 'street_box']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['street', 'street_number', 'street_box', 'address']);
        });

        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn(['street', 'street_number', 'street_box']);
        });

        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn(['street', 'street_number', 'street_box']);
        });
    }
};
