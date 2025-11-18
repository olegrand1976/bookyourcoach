<?php

/**
 * Script pour corriger automatiquement les migrations qui modifient des tables spécifiques à l'application
 * pour qu'elles vérifient l'existence des tables avant de les modifier (compatibilité avec les tests SQLite)
 */

$migrationsDir = __DIR__ . '/database/migrations';
$tablesToCheck = ['tiers', 'tiers_v2', 'trustees', 'cash_book_tiers', 'tier_user'];

function fixMigration($filePath, $tablesToCheck) {
    $content = file_get_contents($filePath);
    $originalContent = $content;
    $modified = false;

    // Vérifier si la migration modifie une table spécifique à l'application
    foreach ($tablesToCheck as $table) {
        $pattern = "/Schema::table\(['\"]{$table}['\"]/";
        if (preg_match($pattern, $content)) {
            // Vérifier si la vérification d'existence existe déjà
            if (!preg_match("/Schema::hasTable\(['\"]{$table}['\"]/", $content)) {
                // Ajouter la vérification d'existence avant Schema::table
                $replacement = "if (Schema::hasTable('{$table}')) {\n            Schema::table('{$table}'";
                $content = preg_replace($pattern, $replacement, $content);
                
                // Fermer le bloc après le Schema::table
                $content = preg_replace(
                    "/(\}\s*\);\s*)\}\s*$/m",
                    "$1        }\n    }",
                    $content
                );
                
                // Si le down() modifie aussi la table, le corriger aussi
                if (preg_match("/public function down\(\)/", $content)) {
                    $downPattern = "/public function down\(\)[^{]*\{[^}]*Schema::table\(['\"]{$table}['\"]/s";
                    if (preg_match($downPattern, $content)) {
                        $content = preg_replace(
                            "/(public function down\(\)[^{]*\{[^}]*)(Schema::table\(['\"]{$table}['\"])/s",
                            "$1if (Schema::hasTable('{$table}')) {\n            $2",
                            $content
                        );
                        // Fermer le bloc dans down()
                        $content = preg_replace(
                            "/(\}\s*\);\s*)\}\s*\}\s*$/m",
                            "$1        }\n    }\n}",
                            $content
                        );
                    }
                }
                
                $modified = true;
            }
        }
    }

    if ($modified && $content !== $originalContent) {
        file_put_contents($filePath, $content);
        echo "Fixed: $filePath\n";
        return true;
    }
    
    return false;
}

// Parcourir toutes les migrations
$files = glob($migrationsDir . '/*.php');
$fixedCount = 0;

foreach ($files as $file) {
    if (fixMigration($file, $tablesToCheck)) {
        $fixedCount++;
    }
}

echo "\nTotal migrations fixed: $fixedCount\n";

