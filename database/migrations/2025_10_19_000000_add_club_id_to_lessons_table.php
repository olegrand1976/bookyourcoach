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
            // Ajouter le champ club_id après l'id
            $table->foreignId('club_id')->nullable()->after('id')->constrained()->onDelete('cascade');
            
            // Ajouter un index pour améliorer les performances des requêtes par club
            $table->index('club_id');
            
            // Index composé pour les requêtes fréquentes (club + date)
            $table->index(['club_id', 'start_time']);
        });
        
        // Migration des données existantes : affecter le club via la relation teacher
        // Cette étape est importante pour les données existantes
        DB::statement("
            UPDATE lessons l
            INNER JOIN teachers t ON l.teacher_id = t.id
            INNER JOIN club_teacher ct ON t.id = ct.teacher_id
            SET l.club_id = ct.club_id
            WHERE l.club_id IS NULL
        ");
        
        // Rendre le champ obligatoire maintenant que les données sont migrées
        Schema::table('lessons', function (Blueprint $table) {
            $table->foreignId('club_id')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            // Supprimer les index
            $table->dropIndex(['club_id', 'start_time']);
            $table->dropIndex(['club_id']);
            
            // Supprimer la contrainte de clé étrangère
            $table->dropForeign(['club_id']);
            
            // Supprimer la colonne
            $table->dropColumn('club_id');
        });
    }
};


