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
        Schema::create('certifications', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // "BEES", "BPJEPS", "BNSSA"
            $table->string('issuing_authority'); // "Ministère des Sports", "FFÉ", "FFN"
            $table->enum('category', ['official', 'federation', 'continuing_education', 'specialized']); // Type de certification
            $table->foreignId('activity_type_id')->nullable()->constrained()->onDelete('set null'); // Type d'activité concerné
            $table->integer('validity_years')->nullable(); // Durée de validité en années (null = permanente)
            $table->json('requirements')->nullable(); // Prérequis pour obtenir cette certification
            $table->text('description')->nullable(); // Description de la certification
            $table->string('icon')->nullable(); // Icône de la certification
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0); // Ordre d'affichage
            $table->timestamps();

            $table->index(['category', 'is_active']);
            $table->index(['activity_type_id', 'is_active']);
            $table->index(['issuing_authority', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('certifications');
    }
};