<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Liste des foreign keys à ajouter
     * Format: ['table' => [['column' => 'col', 'references' => 'ref_table.id', 'onDelete' => 'cascade']]]
     */
    private $foreignKeys = [
        'bookings' => [
            ['column' => 'lesson_id', 'references' => 'lessons.id', 'onDelete' => 'cascade'],
            ['column' => 'student_id', 'references' => 'students.id', 'onDelete' => 'cascade'],
        ],
        'club_activity_types' => [
            ['column' => 'activity_type_id', 'references' => 'activity_types.id', 'onDelete' => 'cascade'],
            ['column' => 'club_id', 'references' => 'clubs.id', 'onDelete' => 'cascade'],
        ],
        'club_disciplines' => [
            ['column' => 'club_id', 'references' => 'clubs.id', 'onDelete' => 'cascade'],
            ['column' => 'discipline_id', 'references' => 'disciplines.id', 'onDelete' => 'cascade'],
        ],
        'club_managers' => [
            ['column' => 'club_id', 'references' => 'clubs.id', 'onDelete' => 'cascade'],
            ['column' => 'user_id', 'references' => 'users.id', 'onDelete' => 'cascade'],
        ],
        'club_open_slot_course_types' => [
            ['column' => 'club_open_slot_id', 'references' => 'club_open_slots.id', 'onDelete' => 'cascade'],
            ['column' => 'course_type_id', 'references' => 'course_types.id', 'onDelete' => 'cascade'],
        ],
        'club_settings' => [
            ['column' => 'club_id', 'references' => 'clubs.id', 'onDelete' => 'cascade'],
        ],
        'club_students' => [
            ['column' => 'club_id', 'references' => 'clubs.id', 'onDelete' => 'cascade'],
            ['column' => 'student_id', 'references' => 'students.id', 'onDelete' => 'cascade'],
        ],
        'club_teachers' => [
            ['column' => 'club_id', 'references' => 'clubs.id', 'onDelete' => 'cascade'],
            ['column' => 'teacher_id', 'references' => 'teachers.id', 'onDelete' => 'cascade'],
        ],
        'club_user' => [
            ['column' => 'club_id', 'references' => 'clubs.id', 'onDelete' => 'cascade'],
            ['column' => 'user_id', 'references' => 'users.id', 'onDelete' => 'cascade'],
        ],
        'google_calendar_tokens' => [
            ['column' => 'user_id', 'references' => 'users.id', 'onDelete' => 'cascade'],
        ],
        'lesson_student' => [
            ['column' => 'lesson_id', 'references' => 'lessons.id', 'onDelete' => 'cascade'],
            ['column' => 'student_id', 'references' => 'students.id', 'onDelete' => 'cascade'],
        ],
        'student_disciplines' => [
            ['column' => 'discipline_id', 'references' => 'disciplines.id', 'onDelete' => 'cascade'],
            ['column' => 'student_id', 'references' => 'students.id', 'onDelete' => 'cascade'],
        ],
        'student_preferences' => [
            ['column' => 'course_type_id', 'references' => 'course_types.id', 'onDelete' => 'set null'],
            ['column' => 'discipline_id', 'references' => 'disciplines.id', 'onDelete' => 'set null'],
            ['column' => 'student_id', 'references' => 'students.id', 'onDelete' => 'cascade'],
        ],
        'subscription_course_types' => [
            ['column' => 'course_type_id', 'references' => 'course_types.id', 'onDelete' => 'cascade'],
            ['column' => 'subscription_id', 'references' => 'subscriptions.id', 'onDelete' => 'cascade'],
        ],
        'subscription_instance_students' => [
            ['column' => 'student_id', 'references' => 'students.id', 'onDelete' => 'cascade'],
            ['column' => 'subscription_instance_id', 'references' => 'subscription_instances.id', 'onDelete' => 'cascade'],
        ],
        'subscription_lessons' => [
            ['column' => 'lesson_id', 'references' => 'lessons.id', 'onDelete' => 'cascade'],
        ],
        'subscription_template_course_types' => [
            ['column' => 'course_type_id', 'references' => 'course_types.id', 'onDelete' => 'cascade'],
            ['column' => 'subscription_template_id', 'references' => 'subscription_templates.id', 'onDelete' => 'cascade'],
        ],
        'subscription_templates' => [
            ['column' => 'club_id', 'references' => 'clubs.id', 'onDelete' => 'cascade'],
        ],
        'teacher_certifications' => [
            ['column' => 'certification_id', 'references' => 'certifications.id', 'onDelete' => 'cascade'],
            ['column' => 'teacher_id', 'references' => 'teachers.id', 'onDelete' => 'cascade'],
        ],
        'teacher_disciplines' => [
            ['column' => 'discipline_id', 'references' => 'disciplines.id', 'onDelete' => 'cascade'],
            ['column' => 'teacher_id', 'references' => 'teachers.id', 'onDelete' => 'cascade'],
        ],
        'teacher_skills' => [
            ['column' => 'skill_id', 'references' => 'skills.id', 'onDelete' => 'cascade'],
            ['column' => 'teacher_id', 'references' => 'teachers.id', 'onDelete' => 'cascade'],
        ],
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $dbName = DB::getDatabaseName();

        foreach ($this->foreignKeys as $table => $keys) {
            // Vérifier que la table existe
            if (!Schema::hasTable($table)) {
                continue;
            }

            foreach ($keys as $key) {
                $column = $key['column'];
                [$refTable, $refColumn] = explode('.', $key['references']);
                $onDelete = $key['onDelete'] ?? 'restrict';

                // Vérifier si la foreign key existe déjà
                $existingFk = DB::select("
                    SELECT CONSTRAINT_NAME
                    FROM information_schema.KEY_COLUMN_USAGE
                    WHERE TABLE_SCHEMA = ?
                    AND TABLE_NAME = ?
                    AND COLUMN_NAME = ?
                    AND REFERENCED_TABLE_NAME = ?
                    AND REFERENCED_COLUMN_NAME = ?
                ", [$dbName, $table, $column, $refTable, $refColumn]);

                if (!empty($existingFk)) {
                    continue; // La foreign key existe déjà
                }

                // Vérifier que la table référencée existe
                if (!Schema::hasTable($refTable)) {
                    continue;
                }

                // Nettoyer les données orphelines avant d'ajouter la contrainte
                $this->cleanupOrphanedData($table, $column, $refTable, $refColumn, $onDelete);

                // Ajouter la foreign key
                $constraintName = $this->generateConstraintName($table, $column);
                
                try {
                    DB::statement("
                        ALTER TABLE `{$table}`
                        ADD CONSTRAINT `{$constraintName}`
                        FOREIGN KEY (`{$column}`)
                        REFERENCES `{$refTable}`(`{$refColumn}`)
                        ON DELETE {$onDelete}
                        ON UPDATE RESTRICT
                    ");
                } catch (\Exception $e) {
                    // Logger l'erreur mais continuer avec les autres foreign keys
                    \Log::warning("Impossible d'ajouter la foreign key {$constraintName}: " . $e->getMessage());
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $dbName = DB::getDatabaseName();

        foreach ($this->foreignKeys as $table => $keys) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            foreach ($keys as $key) {
                $column = $key['column'];
                $constraintName = $this->generateConstraintName($table, $column);

                // Vérifier si la foreign key existe
                $existingFk = DB::select("
                    SELECT CONSTRAINT_NAME
                    FROM information_schema.KEY_COLUMN_USAGE
                    WHERE TABLE_SCHEMA = ?
                    AND TABLE_NAME = ?
                    AND CONSTRAINT_NAME = ?
                ", [$dbName, $table, $constraintName]);

                if (!empty($existingFk)) {
                    try {
                        DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$constraintName}`");
                    } catch (\Exception $e) {
                        \Log::warning("Impossible de supprimer la foreign key {$constraintName}: " . $e->getMessage());
                    }
                }
            }
        }
    }

    /**
     * Nettoie les données orphelines avant d'ajouter la contrainte
     */
    private function cleanupOrphanedData(string $table, string $column, string $refTable, string $refColumn, string $onDelete): void
    {
        // Trouver les enregistrements orphelins
        $orphans = DB::select("
            SELECT t.{$column}
            FROM `{$table}` t
            LEFT JOIN `{$refTable}` r ON t.{$column} = r.{$refColumn}
            WHERE t.{$column} IS NOT NULL
            AND r.{$refColumn} IS NULL
        ");

        if (empty($orphans)) {
            return;
        }

        // Si onDelete est 'set null', mettre à NULL les valeurs orphelines
        if ($onDelete === 'set null') {
            $orphanIds = array_column($orphans, $column);
            DB::table($table)
                ->whereIn($column, $orphanIds)
                ->update([$column => null]);
        } else {
            // Sinon, supprimer les enregistrements orphelins (seulement si c'est sûr)
            // Pour la production, on préfère logger et laisser l'administrateur décider
            \Log::warning("Données orphelines détectées dans {$table}.{$column} référençant {$refTable}.{$refColumn}", [
                'count' => count($orphans),
                'table' => $table,
                'column' => $column,
            ]);
        }
    }

    /**
     * Génère un nom de contrainte unique
     */
    private function generateConstraintName(string $table, string $column): string
    {
        return "fk_{$table}_{$column}";
    }
};
