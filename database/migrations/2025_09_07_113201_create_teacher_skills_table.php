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
        Schema::create('teacher_skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained()->onDelete('cascade');
            $table->foreignId('skill_id')->constrained()->onDelete('cascade');
            $table->enum('level', ['beginner', 'intermediate', 'advanced', 'expert', 'master']); // Niveau de maîtrise
            $table->integer('experience_years')->default(0); // Années d'expérience dans cette compétence
            $table->date('acquired_date')->nullable(); // Date d'acquisition de la compétence
            $table->date('last_practiced')->nullable(); // Dernière pratique de cette compétence
            $table->text('notes')->nullable(); // Notes personnelles sur cette compétence
            $table->json('evidence')->nullable(); // Preuves de compétence (vidéos, témoignages, etc.)
            $table->boolean('is_verified')->default(false); // Compétence vérifiée par un tiers
            $table->foreignId('verified_by')->nullable()->constrained('users')->onDelete('set null'); // Qui a vérifié cette compétence
            $table->date('verified_at')->nullable(); // Date de vérification
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['teacher_id', 'skill_id']);
            $table->index(['teacher_id', 'level']);
            $table->index(['skill_id', 'level']);
            $table->index(['is_verified', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_skills');
    }
};