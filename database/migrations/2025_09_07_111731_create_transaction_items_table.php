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
        Schema::create('transaction_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->nullable()->constrained()->onDelete('set null'); // Peut être null pour services
            $table->string('item_name'); // Nom de l'article (au cas où le produit serait supprimé)
            $table->integer('quantity'); // Quantité
            $table->decimal('unit_price', 8, 2); // Prix unitaire
            $table->decimal('total_price', 8, 2); // Prix total
            $table->decimal('discount', 8, 2)->default(0.00); // Remise appliquée
            $table->timestamps();

            $table->index(['transaction_id', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_items');
    }
};