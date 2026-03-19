<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            if (!Schema::hasColumn('lessons', 'cancellation_certificate_submitted_by_student_id')) {
                $table->unsignedBigInteger('cancellation_certificate_submitted_by_student_id')->nullable()->after('cancellation_certificate_resubmitted_at');
                $table->foreign('cancellation_certificate_submitted_by_student_id')->references('id')->on('students')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            if (Schema::hasColumn('lessons', 'cancellation_certificate_submitted_by_student_id')) {
                $table->dropForeign(['cancellation_certificate_submitted_by_student_id']);
                $table->dropColumn('cancellation_certificate_submitted_by_student_id');
            }
        });
    }
};
