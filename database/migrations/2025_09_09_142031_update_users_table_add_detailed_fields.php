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
        Schema::table('users', function (Blueprint $table) {
            // Ajouter les nouveaux champs pour les noms séparés
            $table->string('first_name')->nullable()->after('name');
            $table->string('last_name')->nullable()->after('first_name');
            
            // Ajouter les champs d'adresse décomposée
            $table->string('street')->nullable()->after('phone');
            $table->string('street_number')->nullable()->after('street');
            $table->string('postal_code')->nullable()->after('street_number');
            $table->string('city')->nullable()->after('postal_code');
            $table->string('country')->default('Belgium')->after('city');
            
            // Ajouter la date de naissance
            $table->date('birth_date')->nullable()->after('country');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'first_name',
                'last_name',
                'street',
                'street_number',
                'postal_code',
                'city',
                'country',
                'birth_date'
            ]);
        });
    }
};
