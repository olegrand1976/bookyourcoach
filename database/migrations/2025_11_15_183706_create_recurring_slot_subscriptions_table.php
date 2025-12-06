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
        if (Schema::hasTable('recurring_slot_subscriptions')) {
            return; // Table already exists, skip migration
        }
        
        Schema::create('recurring_slot_subscriptions', function (Blueprint $table) {
            $table->id();
            
            // Relations
            $table->foreignId('recurring_slot_id')->constrained('recurring_slots')->onDelete('cascade');
            $table->foreignId('subscription_instance_id')->constrained('subscription_instances')->onDelete('cascade');
            
            // Période de validité pour cet abonnement
            $table->date('start_date')->comment('Date de début de validité pour cet abonnement');
            $table->date('end_date')->comment('Date de fin de validité pour cet abonnement');
            
            // Statut
            $table->enum('status', ['active', 'expired', 'cancelled'])->default('active');
            
            $table->timestamps();
            
            // Contraintes
            // Un créneau ne peut avoir qu'un seul abonnement actif à la fois
            $table->unique(['recurring_slot_id', 'subscription_instance_id'], 'unique_recurring_slot_subscription');
            
            // Index pour améliorer les performances
            $table->index(['recurring_slot_id', 'status'], 'recurring_slot_subscriptions_slot_status_idx');
            $table->index(['subscription_instance_id', 'status'], 'recurring_slot_subscriptions_sub_status_idx');
            $table->index(['start_date', 'end_date'], 'recurring_slot_subscriptions_dates_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recurring_slot_subscriptions');
    }
};
