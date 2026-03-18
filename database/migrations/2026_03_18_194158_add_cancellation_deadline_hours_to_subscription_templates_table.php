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
        Schema::table('subscription_templates', function (Blueprint $table) {
            $table->unsignedTinyInteger('cancellation_deadline_hours')->nullable()->after('warning_at_session')
                ->comment('Délai en heures avant le cours : annulation au-delà = non déduit. Null = utiliser défaut club.');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscription_templates', function (Blueprint $table) {
            $table->dropColumn('cancellation_deadline_hours');
        });
    }
};
