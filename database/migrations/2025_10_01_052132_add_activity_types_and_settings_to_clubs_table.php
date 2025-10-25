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
        Schema::table('clubs', function (Blueprint $table) {
            // Ajouter les champs seulement s'ils n'existent pas
            if (!Schema::hasColumn('clubs', 'activity_types')) {
                $table->json('activity_types')->nullable()->after('disciplines');
            }
            
            if (!Schema::hasColumn('clubs', 'discipline_settings')) {
                $table->json('discipline_settings')->nullable()->after('activity_types');
            }
            
            if (!Schema::hasColumn('clubs', 'schedule_config')) {
                $table->json('schedule_config')->nullable()->after('discipline_settings');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clubs', function (Blueprint $table) {
            if (Schema::hasColumn('clubs', 'schedule_config')) {
                $table->dropColumn('schedule_config');
            }
            
            if (Schema::hasColumn('clubs', 'discipline_settings')) {
                $table->dropColumn('discipline_settings');
            }
            
            if (Schema::hasColumn('clubs', 'activity_types')) {
                $table->dropColumn('activity_types');
            }
        });
    }
};
