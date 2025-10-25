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
        Schema::create('teacher_disciplines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained()->onDelete('cascade');
            $table->foreignId('discipline_id')->constrained()->onDelete('cascade');
            $table->string('level')->default('intermediate'); // Niveau d'expertise dans cette discipline
            $table->text('certifications')->nullable(); // Certifications spécifiques à cette discipline
            $table->boolean('is_primary')->default(false); // Discipline principale
            $table->timestamps();

            $table->unique(['teacher_id', 'discipline_id']);
            $table->index(['teacher_id', 'is_primary']);
            $table->index(['discipline_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_disciplines');
    }
};