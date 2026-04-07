<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('lesson_replacements')) {
            return;
        }

        Schema::table('lesson_replacements', function (Blueprint $table) {
            if (! Schema::hasColumn('lesson_replacements', 'reminder_48h_sent_at')) {
                $table->timestamp('reminder_48h_sent_at')->nullable()->after('responded_at');
            }
            if (! Schema::hasColumn('lesson_replacements', 'reminder_24h_sent_at')) {
                $table->timestamp('reminder_24h_sent_at')->nullable()->after('reminder_48h_sent_at');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('lesson_replacements')) {
            return;
        }

        Schema::table('lesson_replacements', function (Blueprint $table) {
            if (Schema::hasColumn('lesson_replacements', 'reminder_24h_sent_at')) {
                $table->dropColumn('reminder_24h_sent_at');
            }
            if (Schema::hasColumn('lesson_replacements', 'reminder_48h_sent_at')) {
                $table->dropColumn('reminder_48h_sent_at');
            }
        });
    }
};
