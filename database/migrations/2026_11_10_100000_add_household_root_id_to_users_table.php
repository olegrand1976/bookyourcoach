<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fusion de comptes élèves : chaque compte garde son email et son mot de passe,
 * mais les comptes d'un même groupe partagent un seul foyer (les élèves de tous
 * les comptes du groupe sont visibles depuis chaque connexion).
 *
 * household_root_id désigne le compte de référence du groupe ; nul pour un compte
 * non fusionné ou pour le compte de référence lui-même.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'household_root_id')) {
                $table->foreignId('household_root_id')->nullable()->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('household_root_id');
        });
    }
};
