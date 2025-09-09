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
        Schema::create('club_teachers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('club_id')->constrained()->onDelete('cascade');
            $table->foreignId('teacher_id')->constrained()->onDelete('cascade');
            $table->json('allowed_disciplines')->nullable(); // Disciplines autorisées pour ce club
            $table->json('restricted_disciplines')->nullable(); // Disciplines restreintes pour ce club
            $table->decimal('hourly_rate', 8, 2)->nullable(); // Tarif spécifique au club
            $table->boolean('is_active')->default(true);
            $table->timestamp('joined_at')->useCurrent();
            $table->timestamps();

            $table->unique(['club_id', 'teacher_id']);
            $table->index(['club_id', 'is_active']);
            $table->index(['teacher_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('club_teachers');
    }
};