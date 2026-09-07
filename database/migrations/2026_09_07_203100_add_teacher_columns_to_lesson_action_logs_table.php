<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lesson_action_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('teacher_id')->nullable()->after('subscription_instance_id');
            $table->unsignedBigInteger('old_teacher_id')->nullable()->after('teacher_id');
            $table->index(['club_id', 'teacher_id']);
            $table->index(['club_id', 'old_teacher_id']);
        });
    }

    public function down(): void
    {
        Schema::table('lesson_action_logs', function (Blueprint $table) {
            $table->dropIndex(['club_id', 'teacher_id']);
            $table->dropIndex(['club_id', 'old_teacher_id']);
            $table->dropColumn(['teacher_id', 'old_teacher_id']);
        });
    }
};
