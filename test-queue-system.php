<?php

/**
 * Script de test du système de queues
 * Usage: php test-queue-system.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Config;

echo "\n";
echo "╔═══════════════════════════════════════════════════════════════╗\n";
echo "║           TEST DU SYSTÈME DE QUEUES                          ║\n";
echo "╔═══════════════════════════════════════════════════════════════╗\n";
echo "\n";

// 1. Configuration actuelle
echo "📊 CONFIGURATION ACTUELLE\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
$queueConnection = Config::get('queue.default');
echo "✓ QUEUE_CONNECTION: {$queueConnection}\n";
echo "✓ DB_CONNECTION: " . Config::get('database.default') . "\n";
echo "\n";

// 2. Vérifier l'état de la configuration
if ($queueConnection === 'sync') {
    echo "⚠️  ATTENTION: Mode SYNC détecté!\n";
    echo "   Les jobs seront exécutés de manière synchrone.\n";
    echo "   L'optimisation asynchrone n'est PAS active.\n";
    echo "\n";
    echo "   Pour activer l'optimisation, exécutez:\n";
    echo "   ./enable-async-optimization.sh\n";
    echo "\n";
} else {
    echo "✅ Mode asynchrone activé ({$queueConnection})\n";
    echo "\n";
}

// 3. Vérifier que les tables existent
echo "📋 VÉRIFICATION DES TABLES\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

try {
    $tables = ['jobs', 'failed_jobs'];
    foreach ($tables as $table) {
        if (DB::getSchemaBuilder()->hasTable($table)) {
            echo "✓ Table '{$table}' existe\n";
        } else {
            echo "✗ Table '{$table}' n'existe pas\n";
            echo "  → Exécutez: php artisan migrate\n";
        }
    }
    echo "\n";
} catch (\Exception $e) {
    echo "✗ Erreur lors de la vérification des tables:\n";
    echo "  " . $e->getMessage() . "\n";
    echo "\n";
}

// 4. Compter les jobs en attente
if ($queueConnection !== 'sync') {
    echo "📊 JOBS EN ATTENTE\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    
    try {
        if (DB::getSchemaBuilder()->hasTable('jobs')) {
            $pendingJobs = DB::table('jobs')->count();
            echo "✓ Jobs en attente: {$pendingJobs}\n";
            
            if ($pendingJobs > 0) {
                echo "\n";
                echo "  Détails des jobs:\n";
                $jobs = DB::table('jobs')
                    ->select('queue', 'created_at')
                    ->limit(5)
                    ->get();
                
                foreach ($jobs as $job) {
                    $payload = json_decode($job->payload ?? '{}', true);
                    $jobClass = $payload['displayName'] ?? 'Unknown';
                    echo "  - {$jobClass} (queue: {$job->queue})\n";
                }
            }
            echo "\n";
        }
        
        if (DB::getSchemaBuilder()->hasTable('failed_jobs')) {
            $failedJobs = DB::table('failed_jobs')->count();
            if ($failedJobs > 0) {
                echo "⚠️  Jobs échoués: {$failedJobs}\n";
                echo "   Consultez avec: php artisan queue:failed\n";
                echo "\n";
            } else {
                echo "✓ Aucun job échoué\n";
                echo "\n";
            }
        }
    } catch (\Exception $e) {
        echo "✗ Erreur lors de la vérification des jobs:\n";
        echo "  " . $e->getMessage() . "\n";
        echo "\n";
    }
}

// 5. Tester la création d'un job
echo "🧪 TEST DE CRÉATION DE JOB\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

try {
    // Créer un job de test simple
    $testJobClass = new class {
        use \Illuminate\Bus\Queueable;
        use \Illuminate\Queue\SerializesModels;
        use \Illuminate\Queue\InteractsWithQueue;
        use \Illuminate\Foundation\Bus\Dispatchable;
        use \Illuminate\Contracts\Queue\ShouldQueue;
        
        public function handle() {
            \Illuminate\Support\Facades\Log::info("✅ Test job exécuté avec succès");
        }
    };
    
    echo "✓ Création d'un job de test...\n";
    dispatch($testJobClass);
    echo "✓ Job dispatché avec succès\n";
    echo "\n";
    
    if ($queueConnection === 'sync') {
        echo "  Le job a été exécuté immédiatement (mode sync)\n";
    } else {
        echo "  Le job a été mis en queue\n";
        echo "  Il sera traité par le worker\n";
    }
    echo "\n";
} catch (\Exception $e) {
    echo "✗ Erreur lors du test:\n";
    echo "  " . $e->getMessage() . "\n";
    echo "\n";
}

// 6. Vérifier si le worker est actif
echo "🔍 WORKER DE QUEUE\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

if ($queueConnection !== 'sync') {
    $workerProcesses = shell_exec('ps aux | grep -E "[q]ueue:work" | wc -l');
    $workerCount = intval(trim($workerProcesses));
    
    if ($workerCount > 0) {
        echo "✅ Worker actif ({$workerCount} processus)\n";
        echo "\n";
        
        // Afficher les détails des workers
        $workerDetails = shell_exec('ps aux | grep -E "[q]ueue:work"');
        if ($workerDetails) {
            echo "  Détails:\n";
            $lines = explode("\n", trim($workerDetails));
            foreach ($lines as $line) {
                if (!empty($line)) {
                    echo "  " . $line . "\n";
                }
            }
            echo "\n";
        }
    } else {
        echo "⚠️  Aucun worker actif détecté\n";
        echo "\n";
        echo "  Pour que l'optimisation fonctionne, lancez:\n";
        echo "  ./start-queue-worker.sh\n";
        echo "\n";
        echo "  Ou:\n";
        echo "  php artisan queue:work --verbose\n";
        echo "\n";
    }
} else {
    echo "✓ Mode sync - Aucun worker nécessaire\n";
    echo "  (mais pas d'optimisation asynchrone)\n";
    echo "\n";
}

// 7. Résumé final
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "📝 RÉSUMÉ\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$allGood = true;

if ($queueConnection === 'sync') {
    echo "❌ L'optimisation asynchrone n'est PAS active\n";
    echo "   Action: Exécutez ./enable-async-optimization.sh\n";
    $allGood = false;
} else {
    echo "✅ Configuration: OK (mode {$queueConnection})\n";
    
    try {
        if (!DB::getSchemaBuilder()->hasTable('jobs')) {
            echo "❌ Tables manquantes\n";
            echo "   Action: Exécutez php artisan migrate\n";
            $allGood = false;
        } else {
            echo "✅ Tables: OK\n";
        }
    } catch (\Exception $e) {
        echo "⚠️  Impossible de vérifier les tables\n";
        $allGood = false;
    }
    
    $workerCount = intval(trim(shell_exec('ps aux | grep -E "[q]ueue:work" | wc -l') ?? '0'));
    if ($workerCount === 0) {
        echo "❌ Worker: Inactif\n";
        echo "   Action: Lancez ./start-queue-worker.sh\n";
        $allGood = false;
    } else {
        echo "✅ Worker: Actif\n";
    }
}

echo "\n";

if ($allGood) {
    echo "🎉 TOUT EST PRÊT !\n";
    echo "   L'optimisation est active et fonctionnelle.\n";
} else {
    echo "⚠️  ACTIONS REQUISES\n";
    echo "   Suivez les instructions ci-dessus pour activer l'optimisation.\n";
}

echo "\n";
echo "Pour plus d'informations: cat DEMARRAGE_RAPIDE.txt\n";
echo "\n";





