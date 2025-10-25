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
        Schema::create('club_students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('club_id')->constrained()->onDelete('cascade');
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->string('level')->nullable(); // Niveau spécifique au club
            $table->text('goals')->nullable(); // Objectifs spécifiques au club
            $table->text('medical_info')->nullable(); // Infos médicales spécifiques au club
            $table->json('preferred_disciplines')->nullable(); // Disciplines préférées pour ce club
            $table->boolean('is_active')->default(true);
            $table->timestamp('joined_at')->useCurrent();
            $table->timestamps();

            $table->unique(['club_id', 'student_id']);
            $table->index(['club_id', 'is_active']);
            $table->index(['student_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('club_students');
    }
};