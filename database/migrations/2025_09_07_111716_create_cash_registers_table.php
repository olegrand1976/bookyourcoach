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
        Schema::create('cash_registers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('club_id')->constrained()->onDelete('cascade');
            $table->string('name'); // "Caisse Principale", "Caisse Snack"
            $table->string('location')->nullable(); // "Accueil", "Snack Bar"
            $table->boolean('is_active')->default(true);
            $table->decimal('current_balance', 10, 2)->default(0.00); // Solde actuel
            $table->datetime('last_closing_at')->nullable(); // Dernière clôture
            $table->timestamps();

            $table->index(['club_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_registers');
    }
};