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
        Schema::create('facilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_type_id')->constrained()->onDelete('cascade');
            $table->string('name'); // "Manège 1", "Bassin 25m", "Carrière A"
            $table->string('type')->default('indoor'); // "indoor", "outdoor", "covered"
            $table->integer('capacity')->default(1); // Nombre max de participants
            $table->json('dimensions')->nullable(); // {"length": 20, "width": 40, "depth": 1.5}
            $table->json('equipment')->nullable(); // Équipements disponibles
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['activity_type_id', 'is_active']);
            $table->index(['type', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('facilities');
    }
};