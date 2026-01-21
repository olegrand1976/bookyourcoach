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
        Schema::create('student_family_links', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('primary_student_id'); // Compte principal (celui avec email)
            $table->unsignedBigInteger('linked_student_id');   // Compte lié
            $table->string('relationship_type', 50)->nullable(); // Optionnel: 'parent', 'guardian', 'sibling', etc.
            $table->unsignedBigInteger('created_by')->nullable(); // ID de l'admin qui a créé le lien
            $table->timestamps();
            
            // Clés étrangères
            $table->foreign('primary_student_id')
                  ->references('id')
                  ->on('students')
                  ->onDelete('cascade');
                  
            $table->foreign('linked_student_id')
                  ->references('id')
                  ->on('students')
                  ->onDelete('cascade');
                  
            $table->foreign('created_by')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');
            
            // Contrainte d'unicité : un lien ne peut exister qu'une seule fois
            $table->unique(['primary_student_id', 'linked_student_id'], 'unique_student_link');
            
            // Index pour améliorer les performances
            $table->index('primary_student_id', 'idx_primary_student');
            $table->index('linked_student_id', 'idx_linked_student');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_family_links');
    }
};
