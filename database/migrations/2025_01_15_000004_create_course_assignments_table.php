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
        Schema::create('course_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('course_slot_id');
            $table->unsignedBigInteger('teacher_id');
            $table->unsignedBigInteger('contract_id');
            $table->date('assignment_date'); // Date spécifique de l'affectation
            $table->enum('status', ['assigned', 'confirmed', 'completed', 'cancelled', 'no_show']);
            $table->decimal('hourly_rate', 8, 2); // Taux horaire pour cette affectation
            $table->integer('actual_duration')->nullable(); // Durée réelle en minutes
            $table->text('notes')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->foreign('course_slot_id')->references('id')->on('course_slots')->onDelete('cascade');
            $table->foreign('teacher_id')->references('id')->on('teachers')->onDelete('cascade');
            $table->foreign('contract_id')->references('id')->on('teacher_contracts')->onDelete('cascade');
            
            $table->unique(['course_slot_id', 'assignment_date']);
            $table->index(['teacher_id', 'assignment_date']);
            $table->index(['status', 'assignment_date']);
            $table->index('assignment_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_assignments');
    }
};
