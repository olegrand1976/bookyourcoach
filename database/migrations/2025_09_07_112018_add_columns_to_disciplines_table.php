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
        Schema::table('disciplines', function (Blueprint $table) {
            $table->foreignId('activity_type_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('slug')->nullable()->unique();
            $table->integer('min_participants')->default(1);
            $table->integer('max_participants')->default(8);
            $table->integer('duration_minutes')->default(60);
            $table->json('equipment_required')->nullable();
            $table->json('skill_levels')->nullable();
            $table->decimal('base_price', 8, 2)->nullable();

            $table->index(['activity_type_id', 'is_active']);
            $table->index(['slug', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('disciplines', function (Blueprint $table) {
            $table->dropForeign(['activity_type_id']);
            $table->dropColumn([
                'activity_type_id',
                'slug',
                'min_participants',
                'max_participants',
                'duration_minutes',
                'equipment_required',
                'skill_levels',
                'base_price'
            ]);
        });
    }
};