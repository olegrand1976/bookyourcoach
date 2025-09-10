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
        Schema::create('student_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->foreignId('discipline_id')->constrained()->onDelete('cascade');
            $table->foreignId('course_type_id')->constrained()->onDelete('cascade');
            $table->boolean('is_preferred')->default(false);
            $table->integer('priority_level')->default(1); // 1 = highest priority, 5 = lowest
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['student_id', 'discipline_id', 'course_type_id'], 'student_prefs_unique');
            $table->index(['student_id', 'is_preferred']);
            $table->index(['discipline_id', 'is_preferred']);
            $table->index(['course_type_id', 'is_preferred']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_preferences');
    }
};