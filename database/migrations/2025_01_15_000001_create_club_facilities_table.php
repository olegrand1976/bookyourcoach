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
        Schema::create('club_facilities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('club_id');
            $table->string('name'); // Ex: "Manège principal", "Carrière extérieure", "Paddock 1"
            $table->string('type'); // Ex: "manège", "carrière", "paddock", "obstacles"
            $table->text('description')->nullable();
            $table->integer('capacity')->default(1); // Nombre de cours simultanés possibles
            $table->json('equipment')->nullable(); // Équipements disponibles
            $table->boolean('is_indoor')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('club_id')->references('id')->on('clubs')->onDelete('cascade');
            $table->index(['club_id', 'is_active']);
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('club_facilities');
    }
};
