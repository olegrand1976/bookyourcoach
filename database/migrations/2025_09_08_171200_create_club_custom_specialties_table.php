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
        Schema::create('club_custom_specialties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('club_id')->constrained()->onDelete('cascade');
            $table->foreignId('activity_type_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('duration_minutes')->default(60);
            $table->decimal('base_price', 8, 2)->default(0);
            $table->json('skill_levels')->nullable();
            $table->integer('min_participants')->default(1);
            $table->integer('max_participants')->default(8);
            $table->json('equipment_required')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['club_id', 'activity_type_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('club_custom_specialties');
    }
};
