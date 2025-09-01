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
        Schema::table('students', function (Blueprint $table) {
            $table->json('preferred_disciplines')->nullable()->after('goals');
            $table->json('preferred_levels')->nullable()->after('preferred_disciplines');
            $table->json('preferred_formats')->nullable()->after('preferred_levels');
            $table->string('location')->nullable()->after('preferred_formats');
            $table->decimal('max_price', 10, 2)->nullable()->after('location');
            $table->integer('max_distance')->nullable()->after('max_price');
            $table->boolean('notifications_enabled')->default(true)->after('max_distance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn([
                'preferred_disciplines',
                'preferred_levels',
                'preferred_formats',
                'location',
                'max_price',
                'max_distance',
                'notifications_enabled',
            ]);
        });
    }
};
