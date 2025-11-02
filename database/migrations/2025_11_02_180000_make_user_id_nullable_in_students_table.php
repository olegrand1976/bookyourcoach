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
        Schema::table('students', function (Blueprint $table) {
            // Supprimer la contrainte de clé étrangère existante
            $table->dropForeign(['user_id']);
            
            // Modifier la colonne pour permettre null
            $table->unsignedBigInteger('user_id')->nullable()->change();
            
            // Recréer la contrainte de clé étrangère avec nullable
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            // Supprimer la contrainte de clé étrangère
            $table->dropForeign(['user_id']);
            
            // Modifier la colonne pour ne plus permettre null (mais seulement si tous les enregistrements ont un user_id)
            // Attention : cette opération peut échouer si des étudiants sans user_id existent
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
            
            // Recréer la contrainte de clé étrangère sans nullable
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }
};

