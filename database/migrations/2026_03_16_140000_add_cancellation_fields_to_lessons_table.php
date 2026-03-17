<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->string('cancellation_reason', 50)->nullable()->after('notes'); // 'medical', 'other'
            $table->string('cancellation_certificate_path')->nullable()->after('cancellation_reason');
            $table->boolean('cancellation_count_in_subscription')->default(false)->after('cancellation_certificate_path');
        });
    }

    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropColumn(['cancellation_reason', 'cancellation_certificate_path', 'cancellation_count_in_subscription']);
        });
    }
};
