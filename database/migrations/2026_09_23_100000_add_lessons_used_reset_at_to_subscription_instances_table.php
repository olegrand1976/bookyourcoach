<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Date de la dernière correction manuelle du nombre de cours utilisés.
 * Quand elle est renseignée, manual_lessons_used vaut le total corrigé à cette date
 * et seuls les cours postérieurs s'y ajoutent.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_instances', function (Blueprint $table) {
            if (!Schema::hasColumn('subscription_instances', 'lessons_used_reset_at')) {
                $table->timestamp('lessons_used_reset_at')->nullable()->after('manual_lessons_used');
            }
        });
    }

    public function down(): void
    {
        Schema::table('subscription_instances', function (Blueprint $table) {
            if (Schema::hasColumn('subscription_instances', 'lessons_used_reset_at')) {
                $table->dropColumn('lessons_used_reset_at');
            }
        });
    }
};
