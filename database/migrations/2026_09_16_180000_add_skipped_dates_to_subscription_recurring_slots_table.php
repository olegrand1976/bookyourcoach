<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('subscription_recurring_slots')) {
            return;
        }

        Schema::table('subscription_recurring_slots', function (Blueprint $table) {
            if (! Schema::hasColumn('subscription_recurring_slots', 'skipped_dates')) {
                $table->json('skipped_dates')->nullable()->after('notes');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('subscription_recurring_slots')) {
            return;
        }

        Schema::table('subscription_recurring_slots', function (Blueprint $table) {
            if (Schema::hasColumn('subscription_recurring_slots', 'skipped_dates')) {
                $table->dropColumn('skipped_dates');
            }
        });
    }
};
