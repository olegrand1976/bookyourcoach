<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Motif affiché à l'utilisateur lorsque la récurrence n'a pas été créée (job asynchrone).
     */
    public function up(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            if (!Schema::hasColumn('lessons', 'recurrence_skipped_reason')) {
                $table->string('recurrence_skipped_reason', 500)->nullable()->after('deduct_from_subscription');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            if (Schema::hasColumn('lessons', 'recurrence_skipped_reason')) {
                $table->dropColumn('recurrence_skipped_reason');
            }
        });
    }
};
