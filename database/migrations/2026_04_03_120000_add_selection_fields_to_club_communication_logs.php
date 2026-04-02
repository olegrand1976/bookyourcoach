<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('club_communication_logs', function (Blueprint $table) {
            $table->string('selection_mode', 20)->default('all')->after('audience');
            $table->json('selected_teacher_ids')->nullable()->after('selection_mode');
            $table->json('selected_student_ids')->nullable()->after('selected_teacher_ids');
            $table->unsignedInteger('teacher_recipient_count')->nullable()->after('failed_count');
            $table->unsignedInteger('student_recipient_count')->nullable()->after('teacher_recipient_count');
        });
    }

    public function down(): void
    {
        Schema::table('club_communication_logs', function (Blueprint $table) {
            $table->dropColumn([
                'selection_mode',
                'selected_teacher_ids',
                'selected_student_ids',
                'teacher_recipient_count',
                'student_recipient_count',
            ]);
        });
    }
};
