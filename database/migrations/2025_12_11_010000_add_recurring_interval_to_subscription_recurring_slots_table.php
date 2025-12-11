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
        Schema::table('subscription_recurring_slots', function (Blueprint $table) {
            // Ajouter le champ recurring_interval pour gérer les récurrences complexes
            // Par défaut 1 = chaque semaine, 2 = toutes les 2 semaines, etc.
            $table->integer('recurring_interval')
                ->default(1)
                ->after('end_time')
                ->comment('Intervalle de récurrence en semaines (1 = chaque semaine, 2 = toutes les 2 semaines, etc.)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscription_recurring_slots', function (Blueprint $table) {
            $table->dropColumn('recurring_interval');
        });
    }
};
