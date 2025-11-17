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
        Schema::create('recurring_slots', function (Blueprint $table) {
            $table->id();
            
            // Relations
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('teacher_id')->constrained('teachers')->onDelete('cascade');
            $table->foreignId('club_id')->constrained('clubs')->onDelete('cascade');
            $table->foreignId('course_type_id')->nullable()->constrained('course_types')->onDelete('set null');
            
            // Récurrence (RRULE)
            $table->text('rrule')->comment('Règle de récurrence iCalendar RRULE (ex: FREQ=WEEKLY;BYDAY=SA)');
            $table->dateTime('reference_start_time')->comment('Date/heure de la première occurrence');
            
            // Durée
            $table->integer('duration_minutes')->comment('Durée du cours en minutes');
            
            // Statut
            $table->enum('status', ['active', 'paused', 'cancelled', 'expired'])->default('active');
            
            // Métadonnées
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            // Index pour améliorer les performances
            $table->index(['student_id', 'status'], 'recurring_slots_student_status_idx');
            $table->index(['teacher_id', 'status'], 'recurring_slots_teacher_status_idx');
            $table->index(['club_id', 'status'], 'recurring_slots_club_status_idx');
            $table->index('reference_start_time', 'recurring_slots_reference_start_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recurring_slots');
    }
};
