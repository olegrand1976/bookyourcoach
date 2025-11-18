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
        // Vérifier que la table users existe avant de la modifier
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                // Ajouter les nouveaux champs pour les noms séparés
                if (!Schema::hasColumn('users', 'first_name')) {
                    $table->string('first_name')->nullable()->after('name');
                }
                if (!Schema::hasColumn('users', 'last_name')) {
                    $table->string('last_name')->nullable()->after('first_name');
                }
                
                // Ajouter les champs d'adresse décomposée
                if (!Schema::hasColumn('users', 'street')) {
                    $table->string('street')->nullable()->after('phone');
                }
                if (!Schema::hasColumn('users', 'street_number')) {
                    $table->string('street_number')->nullable()->after('street');
                }
                if (!Schema::hasColumn('users', 'postal_code')) {
                    $table->string('postal_code')->nullable()->after('street_number');
                }
                if (!Schema::hasColumn('users', 'city')) {
                    $table->string('city')->nullable()->after('postal_code');
                }
                if (!Schema::hasColumn('users', 'country')) {
                    $table->string('country')->default('Belgium')->after('city');
                }
                
                // Ajouter la date de naissance
                if (!Schema::hasColumn('users', 'birth_date')) {
                    $table->date('birth_date')->nullable()->after('country');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // SQLite ne supporte pas plusieurs dropColumn dans une seule modification
        // Séparer chaque dropColumn pour compatibilité SQLite
        $driver = \Illuminate\Support\Facades\DB::getDriverName();
        
        if ($driver === 'sqlite') {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('birth_date');
            });
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('country');
            });
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('city');
            });
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('postal_code');
            });
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('street_number');
            });
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('street');
            });
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('last_name');
            });
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('first_name');
            });
        } else {
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
    }
};
