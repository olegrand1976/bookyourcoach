<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Neo4jSyncService;
use App\Services\Neo4jService;

class Neo4jSyncCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'neo4j:sync 
                            {--entity= : Synchroniser une entité spécifique (users, clubs, teachers, contracts)}
                            {--stats : Afficher les statistiques de synchronisation}
                            {--test : Tester la connexion Neo4j}
                            {--force : Forcer la synchronisation complète}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchroniser les données MySQL vers Neo4j pour l\'analyse graphique';

    protected Neo4jSyncService $syncService;
    protected Neo4jService $neo4jService;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Services résolus ici et non au constructeur : artisan instancie toutes les commandes au
        // démarrage, et Neo4j désactivé (NEO4J_ENABLED=false, prod) remplace Neo4jService par un
        // mock : l'injection échouait alors pour TOUTES les commandes artisan.
        $neo4jService = app(Neo4jService::class);
        if (! $neo4jService instanceof Neo4jService) {
            $this->error('Neo4j est désactivé ou indisponible.');

            return self::FAILURE;
        }
        $this->neo4jService = $neo4jService;
        $this->syncService = app(Neo4jSyncService::class);

        $this->info('🔄 Synchronisation MySQL → Neo4j');
        $this->newLine();

        // Test de connexion
        if ($this->option('test')) {
            return $this->testConnection();
        }

        // Statistiques
        if ($this->option('stats')) {
            return $this->showStats();
        }

        // Synchronisation d'une entité spécifique
        if ($entity = $this->option('entity')) {
            return $this->syncEntity($entity);
        }

        // Synchronisation complète
        return $this->syncAll();
    }

    protected function testConnection(): int
    {
        $this->info('🔍 Test de connexion Neo4j...');
        
        if ($this->neo4jService->testConnection()) {
            $this->info('✅ Connexion Neo4j réussie');
            
            $stats = $this->neo4jService->getStats();
            $this->table(
                ['Entité', 'Nombre'],
                collect($stats)->map(fn($count, $entity) => [$entity, $count])->toArray()
            );
            
            return 0;
        } else {
            $this->error('❌ Échec de la connexion Neo4j');
            return 1;
        }
    }

    protected function showStats(): int
    {
        $this->info('📊 Statistiques de synchronisation');
        $this->newLine();

        $stats = $this->syncService->getSyncStats();
        
        $this->table(
            ['Entité', 'MySQL', 'Neo4j', 'Synchronisé', 'Pourcentage'],
            collect($stats['sync_status'])->map(function($status, $entity) {
                return [
                    ucfirst($entity),
                    $status['mysql_count'],
                    $status['neo4j_count'],
                    $status['synced'] ? '✅' : '❌',
                    $status['percentage'] . '%'
                ];
            })->toArray()
        );

        return 0;
    }

    protected function syncEntity(string $entity): int
    {
        $this->info("🔄 Synchronisation de l'entité: {$entity}");
        $this->newLine();

        $startTime = microtime(true);

        try {
            switch ($entity) {
                case 'users':
                    $count = $this->syncService->syncUsers();
                    break;
                case 'clubs':
                    $count = $this->syncService->syncClubs();
                    break;
                case 'teachers':
                    $count = $this->syncService->syncTeachers();
                    break;
                case 'contracts':
                    $count = $this->syncService->syncContracts();
                    break;
                default:
                    $this->error("Entité inconnue: {$entity}");
                    return 1;
            }

            $duration = round(microtime(true) - $startTime, 2);
            $this->info("✅ Synchronisation terminée: {$count} {$entity} en {$duration}s");

        } catch (\Exception $e) {
            $this->error("❌ Erreur lors de la synchronisation: {$e->getMessage()}");
            return 1;
        }

        return 0;
    }

    protected function syncAll(): int
    {
        $this->info('🔄 Synchronisation complète MySQL → Neo4j');
        $this->newLine();

        if (!$this->option('force')) {
            if (!$this->confirm('Voulez-vous continuer avec la synchronisation complète ?')) {
                $this->info('Synchronisation annulée');
                return 0;
            }
        }

        $startTime = microtime(true);

        try {
            $this->info('📋 Création des index...');
            $this->neo4jService->createIndexes();

            $this->newLine();
            $this->info('🔄 Synchronisation des données...');
            
            $results = $this->syncService->syncAll();

            $duration = round(microtime(true) - $startTime, 2);

            $this->newLine();
            $this->info('✅ Synchronisation terminée !');
            $this->table(
                ['Entité', 'Nombre synchronisé'],
                collect($results)->map(fn($count, $entity) => [ucfirst($entity), $count])->toArray()
            );

            $this->info("⏱️  Durée totale: {$duration}s");

        } catch (\Exception $e) {
            $this->error("❌ Erreur lors de la synchronisation: {$e->getMessage()}");
            return 1;
        }

        return 0;
    }
}
