<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fin de série voulue (série tronquée depuis le planning, suppression « toutes les suivantes ») :
 * la tâche de prolongation ne doit jamais repousser cette date de fin.
 *
 * Reprise de l'existant : les séries déjà tronquées (note « Série tronquée ») sont verrouillées.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_recurring_slots', function (Blueprint $table) {
            $table->timestamp('end_locked_at')->nullable()->after('end_date');
        });

        DB::table('subscription_recurring_slots')
            ->whereNull('end_locked_at')
            ->where('notes', 'like', '%Série tronquée%')
            ->update(['end_locked_at' => DB::raw('updated_at')]);
    }

    public function down(): void
    {
        Schema::table('subscription_recurring_slots', function (Blueprint $table) {
            $table->dropColumn('end_locked_at');
        });
    }
};
