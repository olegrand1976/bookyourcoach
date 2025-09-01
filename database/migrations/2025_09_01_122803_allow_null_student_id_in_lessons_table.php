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
        Schema::table('lessons', function (Blueprint $table) {
            // Supprimer la contrainte de clé étrangère existante
            $table->dropForeign(['student_id']);
            
            // Modifier la colonne pour permettre null
            $table->foreignId('student_id')->nullable()->change();
            
            // Recréer la contrainte de clé étrangère avec nullable
            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            // Supprimer la contrainte de clé étrangère
            $table->dropForeign(['student_id']);
            
            // Modifier la colonne pour ne plus permettre null
            $table->foreignId('student_id')->nullable(false)->change();
            
            // Recréer la contrainte de clé étrangère sans nullable
            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
        });
    }
};
