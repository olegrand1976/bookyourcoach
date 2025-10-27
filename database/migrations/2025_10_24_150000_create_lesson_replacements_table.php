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
        if (!Schema::hasTable('lesson_replacements')) {
            Schema::create('lesson_replacements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('lesson_id')->constrained('lessons')->onDelete('cascade');
                $table->foreignId('original_teacher_id')->constrained('teachers')->onDelete('cascade');
                $table->foreignId('replacement_teacher_id')->constrained('teachers')->onDelete('cascade');
                $table->enum('status', ['pending', 'accepted', 'rejected', 'cancelled'])->default('pending');
                $table->string('reason');
                $table->text('notes')->nullable();
                $table->timestamp('requested_at')->nullable();
                $table->timestamp('responded_at')->nullable();
                $table->timestamps();

                // Index pour améliorer les performances
                $table->index('lesson_id');
                $table->index('original_teacher_id');
                $table->index('replacement_teacher_id');
                $table->index('status');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lesson_replacements');
    }
};

