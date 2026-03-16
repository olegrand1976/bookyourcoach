<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_instances', function (Blueprint $table) {
            if (!Schema::hasColumn('subscription_instances', 'manual_lessons_used')) {
                $table->integer('manual_lessons_used')->nullable()->after('lessons_used');
            }
        });
    }

    public function down(): void
    {
        Schema::table('subscription_instances', function (Blueprint $table) {
            if (Schema::hasColumn('subscription_instances', 'manual_lessons_used')) {
                $table->dropColumn('manual_lessons_used');
            }
        });
    }
};
