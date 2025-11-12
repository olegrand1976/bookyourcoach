<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AnalyzeModelsCoherence extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:analyze-models-coherence';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Analyse la cohérence entre les modèles et les migrations';

    private $issues = [];
    private $modelsPath;
    private $migrationsPath;

    public function __construct()
    {
        parent::__construct();
        $this->modelsPath = app_path('Models');
        $this->migrationsPath = database_path('migrations');
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $this->info('🔍 Analyse de cohérence des modèles...');
            $this->newLine();

            // 1. Vérifier les clés primaires
            $this->checkPrimaryKeys();

            // 2. Vérifier les modèles vs migrations
            $this->checkModelsVsMigrations();

            // 3. Vérifier les foreign keys
            $this->checkForeignKeys();

            // 4. Afficher le rapport
            $this->displayReport();

            return count($this->issues) > 0 ? 1 : 0;
        } catch (\Exception $e) {
            $this->error('Erreur lors de l\'analyse: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
    }

    private function checkPrimaryKeys()
    {
        $this->info('📋 Vérification des clés primaires...');
        
        $tables = DB::select("SHOW TABLES");
        $dbName = DB::getDatabaseName();
        $tableKey = "Tables_in_{$dbName}";

        $systemTables = ['migrations', 'failed_jobs', 'password_reset_tokens', 'sessions', 
                        'cache', 'cache_locks', 'jobs', 'job_batches'];

        foreach ($tables as $table) {
            $tableName = $table->$tableKey;
            
            if (in_array($tableName, $systemTables)) {
                continue;
            }

            $primaryKey = DB::select("
                SELECT COUNT(*) as count
                FROM information_schema.TABLE_CONSTRAINTS
                WHERE TABLE_SCHEMA = ?
                AND TABLE_NAME = ?
                AND CONSTRAINT_TYPE = 'PRIMARY KEY'
            ", [$dbName, $tableName]);

            if ($primaryKey[0]->count == 0) {
                $this->issues[] = [
                    'type' => 'missing_primary_key',
                    'table' => $tableName,
                    'severity' => 'critical',
                    'message' => "La table '{$tableName}' n'a pas de clé primaire"
                ];
            }
        }
    }

    private function checkModelsVsMigrations()
    {
        $this->info('📝 Vérification modèles vs migrations...');

        $modelFiles = glob($this->modelsPath . '/*.php');
        
        foreach ($modelFiles as $modelFile) {
            $className = basename($modelFile, '.php');
            $tableName = $this->getTableNameFromModel($className);
            
            if (!$tableName || !Schema::hasTable($tableName)) {
                continue;
            }

            // Charger le modèle pour obtenir ses propriétés
            $model = $this->loadModel($className);
            if (!$model) {
                continue;
            }

            $fillable = $model->getFillable();
            $tableColumns = $this->getTableColumns($tableName);

            // Vérifier que toutes les colonnes fillable existent
            foreach ($fillable as $column) {
                if (!in_array($column, $tableColumns)) {
                    $this->issues[] = [
                        'type' => 'missing_column',
                        'table' => $tableName,
                        'column' => $column,
                        'severity' => 'high',
                        'message' => "La colonne '{$column}' est dans le modèle {$className} mais n'existe pas dans la table '{$tableName}'"
                    ];
                }
            }
        }
    }

    private function checkForeignKeys()
    {
        $this->info('🔗 Vérification des foreign keys...');

        $tables = DB::select("SHOW TABLES");
        $dbName = DB::getDatabaseName();
        $tableKey = "Tables_in_{$dbName}";

        $systemTables = ['migrations', 'failed_jobs', 'password_reset_tokens', 'sessions', 
                        'cache', 'cache_locks', 'jobs', 'job_batches'];

        foreach ($tables as $table) {
            $tableName = $table->$tableKey;
            
            if (in_array($tableName, $systemTables)) {
                continue;
            }

            // Vérifier les colonnes qui ressemblent à des foreign keys (se terminant par _id)
            $columns = DB::select("SHOW COLUMNS FROM `{$tableName}`");
            
            foreach ($columns as $column) {
                $columnName = $column->Field;
                
                if (Str::endsWith($columnName, '_id') && $columnName !== 'id') {
                    // Extraire le nom de la table référencée
                    $referencedTable = Str::plural(Str::before($columnName, '_id'));
                    
                    // Vérifier si une foreign key existe
                    $foreignKeys = DB::select("
                        SELECT CONSTRAINT_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
                        FROM information_schema.KEY_COLUMN_USAGE
                        WHERE TABLE_SCHEMA = ?
                        AND TABLE_NAME = ?
                        AND COLUMN_NAME = ?
                        AND REFERENCED_TABLE_NAME IS NOT NULL
                    ", [$dbName, $tableName, $columnName]);

                    // Vérifier si la table référencée existe
                    $referencedTableExists = Schema::hasTable($referencedTable);
                    
                    // Liste des tables connues avec des noms différents
                    $knownTables = [
                        'user_id' => 'users',
                        'club_id' => 'clubs',
                        'lesson_id' => 'lessons',
                        'teacher_id' => 'teachers',
                        'student_id' => 'students',
                        'location_id' => 'locations',
                        'discipline_id' => 'disciplines',
                        'course_type_id' => 'course_types',
                        'activity_type_id' => 'activity_types',
                        'product_id' => 'products',
                        'category_id' => 'product_categories',
                        'cash_register_id' => 'cash_registers',
                        'transaction_id' => 'transactions',
                        'subscription_id' => 'subscriptions',
                        'profile_id' => 'profiles',
                        'invoice_id' => 'invoices',
                        'payment_id' => 'payments',
                        'payout_id' => 'payouts',
                        'availability_id' => 'availabilities',
                        'time_block_id' => 'time_blocks',
                        'certification_id' => 'certifications',
                        'skill_id' => 'skills',
                        'facility_id' => 'facilities',
                    ];

                    // Vérifier si c'est une colonne connue
                    if (isset($knownTables[$columnName])) {
                        $referencedTable = $knownTables[$columnName];
                        $referencedTableExists = Schema::hasTable($referencedTable);
                    }

                    if (empty($foreignKeys) && $referencedTableExists) {
                        $this->issues[] = [
                            'type' => 'missing_foreign_key',
                            'table' => $tableName,
                            'column' => $columnName,
                            'referenced_table' => $referencedTable,
                            'severity' => 'medium',
                            'message' => "La colonne '{$columnName}' dans '{$tableName}' ressemble à une foreign key mais n'a pas de contrainte définie vers '{$referencedTable}'"
                        ];
                    }
                }
            }
        }
    }

    private function getTableNameFromModel($className)
    {
        $modelFile = $this->modelsPath . '/' . $className . '.php';
        if (!file_exists($modelFile)) {
            return null;
        }

        $content = file_get_contents($modelFile);
        
        // Chercher protected $table = '...'
        if (preg_match("/protected\s+\$table\s*=\s*['\"]([^'\"]+)['\"]/", $content, $matches)) {
            return $matches[1];
        }

        // Sinon, utiliser la convention Laravel (pluriel du nom de classe)
        return Str::snake(Str::plural($className));
    }

    private function loadModel($className)
    {
        try {
            $fullClassName = "App\\Models\\{$className}";
            if (class_exists($fullClassName)) {
                return new $fullClassName();
            }
        } catch (\Exception $e) {
            // Ignorer les erreurs de chargement
        }
        return null;
    }

    private function getTableColumns($tableName)
    {
        $columns = DB::select("SHOW COLUMNS FROM `{$tableName}`");
        return array_map(function($col) {
            return $col->Field;
        }, $columns);
    }

    private function displayReport()
    {
        $this->newLine();
        $this->line(str_repeat("=", 80));
        $this->info('📊 RAPPORT D\'ANALYSE');
        $this->line(str_repeat("=", 80));
        $this->newLine();

        if (empty($this->issues)) {
            $this->info('✅ Aucune incohérence détectée !');
            return;
        }

        // Grouper par sévérité
        $grouped = [
            'critical' => [],
            'high' => [],
            'medium' => [],
            'low' => []
        ];

        foreach ($this->issues as $issue) {
            $grouped[$issue['severity']][] = $issue;
        }

        foreach (['critical', 'high', 'medium', 'low'] as $severity) {
            if (!empty($grouped[$severity])) {
                $emoji = $severity === 'critical' ? '🔴' : ($severity === 'high' ? '🟠' : ($severity === 'medium' ? '🟡' : '🟢'));
                $this->line("{$emoji} {$severity} (" . count($grouped[$severity]) . ")");
                $this->line(str_repeat("-", 80));
                
                foreach ($grouped[$severity] as $issue) {
                    $this->line("  • {$issue['message']}");
                }
                $this->newLine();
            }
        }

        $this->line(str_repeat("=", 80));
        $this->info('Total d\'incohérences: ' . count($this->issues));
        $this->line(str_repeat("=", 80));
    }

    public function getIssues()
    {
        return $this->issues;
    }
}
