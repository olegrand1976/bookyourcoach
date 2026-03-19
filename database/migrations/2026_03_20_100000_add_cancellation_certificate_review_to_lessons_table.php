<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            if (!Schema::hasColumn('lessons', 'cancellation_certificate_status')) {
                $table->string('cancellation_certificate_status', 20)->nullable()->after('cancellation_count_in_subscription');
            }
            if (!Schema::hasColumn('lessons', 'cancellation_certificate_reviewed_at')) {
                $table->timestamp('cancellation_certificate_reviewed_at')->nullable()->after('cancellation_certificate_status');
            }
            if (!Schema::hasColumn('lessons', 'cancellation_certificate_reviewed_by')) {
                $table->unsignedBigInteger('cancellation_certificate_reviewed_by')->nullable()->after('cancellation_certificate_reviewed_at');
            }
            if (!Schema::hasColumn('lessons', 'cancellation_certificate_rejection_reason')) {
                $table->string('cancellation_certificate_rejection_reason', 500)->nullable()->after('cancellation_certificate_reviewed_by');
            }
            if (!Schema::hasColumn('lessons', 'cancellation_certificate_resubmitted_at')) {
                $table->timestamp('cancellation_certificate_resubmitted_at')->nullable()->after('cancellation_certificate_rejection_reason');
            }
        });
    }

    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $cols = [
                'cancellation_certificate_resubmitted_at',
                'cancellation_certificate_rejection_reason',
                'cancellation_certificate_reviewed_by',
                'cancellation_certificate_reviewed_at',
                'cancellation_certificate_status',
            ];
            foreach ($cols as $col) {
                if (Schema::hasColumn('lessons', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
