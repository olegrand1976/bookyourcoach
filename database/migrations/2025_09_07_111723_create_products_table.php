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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('club_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->constrained('product_categories')->onDelete('cascade');
            $table->string('name'); // "Café", "Casque d'équitation"
            $table->text('description')->nullable();
            $table->decimal('price', 8, 2); // Prix de vente
            $table->decimal('cost_price', 8, 2)->nullable(); // Prix d'achat
            $table->integer('stock_quantity')->default(0); // Stock disponible
            $table->integer('min_stock')->default(5); // Stock minimum
            $table->string('sku')->nullable(); // Code produit
            $table->string('barcode')->nullable(); // Code-barres
            $table->json('images')->nullable(); // Images du produit
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['club_id', 'is_active']);
            $table->index(['category_id', 'is_active']);
            $table->index('sku');
            $table->index('barcode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};