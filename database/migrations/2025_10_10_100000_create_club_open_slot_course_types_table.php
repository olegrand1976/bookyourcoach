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
        if (!Schema::hasTable('club_open_slot_course_types')) {
            Schema::create('club_open_slot_course_types', function (Blueprint $table) {
                $table->id();
                $table->foreignId('club_open_slot_id')->constrained()->onDelete('cascade');
                $table->foreignId('course_type_id')->constrained()->onDelete('cascade');
                $table->timestamps();

                // Index pour améliorer les performances de recherche
                $table->index(['club_open_slot_id', 'course_type_id'], 'slot_course_type_idx');
                
                // Éviter les doublons
                $table->unique(['club_open_slot_id', 'course_type_id'], 'slot_course_type_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('club_open_slot_course_types');
    }
};

