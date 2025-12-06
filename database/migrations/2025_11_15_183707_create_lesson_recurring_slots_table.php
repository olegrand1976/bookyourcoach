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
        if (Schema::hasTable('lesson_recurring_slots')) {
            return; // Table already exists, skip migration
        }
        
        Schema::create('lesson_recurring_slots', function (Blueprint $table) {
            $table->id();
            
            // Relations
            $table->foreignId('lesson_id')->constrained('lessons')->onDelete('cascade');
            $table->foreignId('recurring_slot_id')->constrained('recurring_slots')->onDelete('cascade');
            $table->foreignId('subscription_instance_id')->constrained('subscription_instances')->onDelete('cascade');
            
            // Métadonnées
            $table->timestamp('generated_at')->nullable()->comment('Quand le cours a été généré');
            $table->enum('generated_by', ['auto', 'manual'])->default('auto')->comment('Comment le cours a été généré');
            
            $table->timestamps();
            
            // Contraintes
            // Un cours ne peut être lié qu'une seule fois à un créneau récurrent
            $table->unique(['lesson_id', 'recurring_slot_id'], 'unique_lesson_recurring_slot');
            
            // Index pour améliorer les performances
            $table->index('recurring_slot_id', 'lesson_recurring_slots_slot_idx');
            $table->index('subscription_instance_id', 'lesson_recurring_slots_subscription_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lesson_recurring_slots');
    }
};
