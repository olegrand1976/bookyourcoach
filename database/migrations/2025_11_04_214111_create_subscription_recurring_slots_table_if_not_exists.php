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
        // Vérifier si la table existe déjà pour éviter les erreurs
        if (!Schema::hasTable('subscription_recurring_slots')) {
            Schema::create('subscription_recurring_slots', function (Blueprint $table) {
            $table->id();
                
                // Relations
                $table->foreignId('subscription_instance_id')
                    ->constrained('subscription_instances')
                    ->onDelete('cascade');
                
                $table->foreignId('open_slot_id')
                    ->nullable()
                    ->constrained('club_open_slots')
                    ->onDelete('set null');
                
                $table->foreignId('teacher_id')
                    ->constrained('teachers')
                    ->onDelete('cascade');
                
                $table->foreignId('student_id')
                    ->constrained('students')
                    ->onDelete('cascade');
                
                // Informations sur le créneau
                $table->integer('day_of_week'); // 0 = Dimanche, 1 = Lundi, etc.
                $table->time('start_time');
                $table->time('end_time');
                
                // Période de validité
                $table->date('start_date');
                $table->date('end_date');
                
                // Statut et notes
                $table->enum('status', ['active', 'cancelled', 'expired', 'completed'])
                    ->default('active');
                $table->text('notes')->nullable();
                
            $table->timestamps();
                
                // Index pour améliorer les performances
                $table->index(['day_of_week', 'start_time', 'end_time', 'status'], 'recurring_slots_schedule_idx');
                $table->index(['teacher_id', 'status'], 'recurring_slots_teacher_idx');
                $table->index(['subscription_instance_id', 'status'], 'recurring_slots_subscription_idx');
                $table->index(['student_id', 'status'], 'recurring_slots_student_idx');
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_recurring_slots');
    }
};

