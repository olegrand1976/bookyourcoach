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
        if (!Schema::hasTable('club_facilities')) {
            Schema::create('club_facilities', function (Blueprint $table) {
                $table->id();
                $table->foreignId('club_id')->constrained('clubs')->onDelete('cascade');
                $table->string('name'); // "Manège principal", "Carrière A", etc.
                $table->string('type')->default('manège'); // manège, carrière, paddock, obstacles, dressage, cross, voltige, attelage, autre
                $table->text('description')->nullable();
                $table->integer('capacity')->default(1); // Nombre de cours simultanés possibles
                $table->json('equipment')->nullable(); // Équipements disponibles
                $table->boolean('is_indoor')->default(true);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['club_id', 'is_active']);
                $table->index(['type', 'is_active']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('club_facilities');
    }
};

