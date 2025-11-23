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
        if (!Schema::hasTable('course_assignments')) {
            Schema::create('course_assignments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('course_slot_id')->constrained('course_slots')->onDelete('cascade');
                $table->foreignId('teacher_id')->constrained('teachers')->onDelete('cascade');
                $table->foreignId('contract_id')->nullable()->constrained('teacher_contracts')->onDelete('set null');
                $table->date('assignment_date');
                $table->enum('status', ['assigned', 'confirmed', 'completed', 'cancelled', 'no_show'])->default('assigned');
                $table->decimal('hourly_rate', 8, 2);
                $table->integer('actual_duration')->nullable(); // Durée réelle en minutes
                $table->text('notes')->nullable();
                $table->timestamp('confirmed_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();

                $table->index(['course_slot_id', 'assignment_date']);
                $table->index(['teacher_id', 'assignment_date']);
                $table->index(['contract_id', 'status']);
                $table->index(['assignment_date', 'status']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_assignments');
    }
};

