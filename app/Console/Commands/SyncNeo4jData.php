<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Neo4jService;

class SyncNeo4jData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'neo4j:sync {--force : Forcer la synchronisation même si Neo4j n\'est pas disponible}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchroniser les données MySQL vers Neo4j';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Démarrage de la synchronisation MySQL → Neo4j');
        
        $neo4jService = new Neo4jService();
        
        // Vérifier la connexion
        if (!$neo4jService->checkConnection()) {
            if (!$this->option('force')) {
                $this->error('❌ Neo4j n\'est pas disponible');
                $this->info('💡 Utilisez --force pour forcer la synchronisation');
                return 1;
            }
            $this->warn('⚠️  Neo4j non disponible, synchronisation forcée');
        } else {
            $this->info('✅ Connexion Neo4j établie');
        }

        // Synchroniser les données
        $this->info('📊 Synchronisation des données...');
        
        $results = $neo4jService->syncAllData();
        
        // Afficher les résultats
        $this->info('📈 Résultats de la synchronisation :');
        $this->line("   • Enseignants synchronisés : {$results['teachers']}");
        $this->line("   • Étudiants synchronisés : {$results['students']}");
        
        if (!empty($results['errors'])) {
            $this->warn('⚠️  Erreurs rencontrées :');
            foreach ($results['errors'] as $error) {
                $this->line("   • {$error}");
            }
        }
        
        $this->info('✅ Synchronisation terminée');
        
        return 0;
    }
}