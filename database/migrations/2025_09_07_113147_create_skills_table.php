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
        Schema::create('skills', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // "Dressage", "Natation sportive", "Pédagogie"
            $table->enum('category', ['technical', 'pedagogical', 'management', 'communication', 'technology']); // Catégorie de compétence
            $table->foreignId('activity_type_id')->nullable()->constrained()->onDelete('set null'); // Type d'activité (peut être null pour compétences transversales)
            $table->text('description')->nullable(); // Description de la compétence
            $table->string('icon')->nullable(); // Icône de la compétence
            $table->json('levels')->nullable(); // Niveaux possibles ["beginner", "intermediate", "advanced", "expert", "master"]
            $table->json('requirements')->nullable(); // Prérequis pour acquérir cette compétence
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0); // Ordre d'affichage
            $table->timestamps();

            $table->index(['category', 'is_active']);
            $table->index(['activity_type_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('skills');
    }
};