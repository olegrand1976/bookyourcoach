<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Ajoute les colonnes manquantes à la table users si elles n'existent pas déjà
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'niss')) {
                $table->string('niss')->nullable()->after('birth_date');
            }
            if (!Schema::hasColumn('users', 'bank_account_number')) {
                $table->string('bank_account_number')->nullable()->after('niss');
            }
            if (!Schema::hasColumn('users', 'experience_start_date')) {
                $table->date('experience_start_date')->nullable()->after('bank_account_number');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'experience_start_date')) {
                $table->dropColumn('experience_start_date');
            }
            if (Schema::hasColumn('users', 'bank_account_number')) {
                $table->dropColumn('bank_account_number');
            }
            if (Schema::hasColumn('users', 'niss')) {
                $table->dropColumn('niss');
            }
        });
    }
};

