<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Ajoute des index optimisés pour améliorer les performances des requêtes du dashboard enseignant
     */
    public function up(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            // Index composite principal pour les requêtes du dashboard :
            // WHERE teacher_id = X AND status IN(...) AND start_time BETWEEN
            // Ordre : teacher_id (égalité) -> status (IN/égalité) -> start_time (BETWEEN/ORDER BY)
            if (!$this->indexExists('lessons', 'lessons_teacher_status_start_idx')) {
                $table->index(['teacher_id', 'status', 'start_time'], 'lessons_teacher_status_start_idx');
            }

            // Index pour les requêtes avec seulement teacher_id et start_time (déjà existant mais vérifions)
            // Cet index est déjà présent mais on le garde car très utilisé
            
            // Index sur start_time seul pour les filtres de période globaux
            // Utile quand on filtre uniquement par date sans teacher_id
            if (!$this->indexExists('lessons', 'lessons_start_time_idx')) {
                $table->index('start_time', 'lessons_start_time_idx');
            }

            // Index composite pour les statistiques (status + start_time)
            // Utile pour les requêtes de stats globales ou par statut
            if (!$this->indexExists('lessons', 'lessons_status_start_time_idx')) {
                $table->index(['status', 'start_time'], 'lessons_status_start_time_idx');
            }

            // Index pour les requêtes avec club_id et start_time (déjà existant mais on s'assure qu'il est optimal)
            // Cet index existe déjà dans la migration add_club_id_to_lessons_table
            
            // Index composite pour les requêtes avec student_id, status et start_time
            // Utile pour les dashboards étudiants
            if (!$this->indexExists('lessons', 'lessons_student_status_start_idx')) {
                $table->index(['student_id', 'status', 'start_time'], 'lessons_student_status_start_idx');
            }

            // Index pour payment_status si utilisé dans les requêtes
            // Utile pour les statistiques de paiement
            if (!$this->indexExists('lessons', 'lessons_payment_status_idx')) {
                $table->index('payment_status', 'lessons_payment_status_idx');
            }

            // Index composite pour les requêtes de revenus : teacher_id + status + start_time + price
            // Mais MySQL limite la taille des index, donc on évite d'ajouter price
            // Un index (teacher_id, status, start_time) est suffisant car price peut être agrégé
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            // Supprimer les index dans l'ordre inverse
            if ($this->indexExists('lessons', 'lessons_payment_status_idx')) {
                $table->dropIndex('lessons_payment_status_idx');
            }
            
            if ($this->indexExists('lessons', 'lessons_student_status_start_idx')) {
                $table->dropIndex('lessons_student_status_start_idx');
            }
            
            if ($this->indexExists('lessons', 'lessons_status_start_time_idx')) {
                $table->dropIndex('lessons_status_start_time_idx');
            }
            
            if ($this->indexExists('lessons', 'lessons_start_time_idx')) {
                $table->dropIndex('lessons_start_time_idx');
            }
            
            if ($this->indexExists('lessons', 'lessons_teacher_status_start_idx')) {
                $table->dropIndex('lessons_teacher_status_start_idx');
            }
        });
    }

    /**
     * Vérifie si un index existe déjà sur une table
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $driver = DB::getDriverName();
        
        if ($driver === 'sqlite') {
            // SQLite : vérifier dans sqlite_master
            $result = DB::select("
                SELECT name FROM sqlite_master 
                WHERE type='index' AND name = ?
            ", [$indexName]);
            return !empty($result);
        } else {
            // MySQL/MariaDB : vérifier dans INFORMATION_SCHEMA
            $database = DB::getDatabaseName();
            $result = DB::select("
                SELECT COUNT(*) as count
                FROM INFORMATION_SCHEMA.STATISTICS
                WHERE TABLE_SCHEMA = ? 
                AND TABLE_NAME = ? 
                AND INDEX_NAME = ?
            ", [$database, $table, $indexName]);
            
            return isset($result[0]) && $result[0]->count > 0;
        }
    }
};
