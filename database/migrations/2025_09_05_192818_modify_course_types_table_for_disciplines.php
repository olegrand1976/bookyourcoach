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
        Schema::table('course_types', function (Blueprint $table) {
            $table->foreignId('discipline_id')->nullable()->constrained()->onDelete('cascade');
            $table->integer('duration_minutes')->nullable()->after('duration');
            $table->boolean('is_individual')->default(false)->after('duration_minutes');
            $table->integer('max_participants')->nullable()->after('is_individual');
            $table->boolean('is_active')->default(true)->after('max_participants');
            
            $table->index(['discipline_id', 'is_active']);
            $table->index(['is_individual']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_types', function (Blueprint $table) {
            $table->dropForeign(['discipline_id']);
            $table->dropColumn(['discipline_id', 'duration_minutes', 'is_individual', 'max_participants', 'is_active']);
        });
    }
};
