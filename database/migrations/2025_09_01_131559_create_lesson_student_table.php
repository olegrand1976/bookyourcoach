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
        Schema::create('lesson_student', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained()->onDelete('cascade');
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['confirmed', 'pending', 'cancelled'])->default('pending');
            $table->decimal('price', 8, 2)->nullable(); // Prix spécifique pour cet élève
            $table->text('notes')->nullable(); // Notes spécifiques pour cet élève
            $table->timestamps();

            // Empêcher les doublons
            $table->unique(['lesson_id', 'student_id']);
            
            // Index pour les performances
            $table->index(['lesson_id', 'status']);
            $table->index(['student_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lesson_student');
    }
};
