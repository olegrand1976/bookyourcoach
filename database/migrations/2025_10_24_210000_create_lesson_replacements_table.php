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
        Schema::create('lesson_replacements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained()->onDelete('cascade');
            $table->foreignId('original_teacher_id')->constrained('teachers')->onDelete('cascade');
            $table->foreignId('replacement_teacher_id')->constrained('teachers')->onDelete('cascade');
            $table->enum('status', ['pending', 'accepted', 'rejected', 'cancelled'])->default('pending');
            $table->text('reason')->nullable(); // Raison du remplacement
            $table->text('notes')->nullable(); // Notes supplémentaires
            $table->timestamp('requested_at')->useCurrent();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            // Index pour améliorer les performances
            $table->index(['lesson_id', 'status']);
            $table->index(['replacement_teacher_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lesson_replacements');
    }
};

