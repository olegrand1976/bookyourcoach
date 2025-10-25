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
        Schema::create('activity_types', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // "Équitation", "Natation"
            $table->string('slug')->unique(); // "equestrian", "swimming"
            $table->text('description')->nullable();
            $table->string('icon')->nullable(); // "🐎", "🏊‍♂️"
            $table->string('color')->default('#6B7280'); // Couleur hexadécimale
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['slug', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_types');
    }
};