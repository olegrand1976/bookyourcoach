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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('club_id')->constrained()->onDelete('cascade');
            $table->foreignId('cash_register_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Utilisateur qui a effectué la transaction
            $table->enum('type', ['sale', 'refund', 'expense', 'deposit']); // Type de transaction
            $table->decimal('amount', 10, 2); // Montant total
            $table->enum('payment_method', ['cash', 'card', 'transfer', 'check', 'multiple']); // Méthode de paiement
            $table->text('description')->nullable(); // Description de la transaction
            $table->string('reference')->nullable(); // Référence (facture, etc.)
            $table->json('metadata')->nullable(); // Données supplémentaires
            $table->datetime('processed_at'); // Date/heure de traitement
            $table->timestamps();

            $table->index(['club_id', 'processed_at']);
            $table->index(['cash_register_id', 'processed_at']);
            $table->index(['type', 'processed_at']);
            $table->index(['payment_method', 'processed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};