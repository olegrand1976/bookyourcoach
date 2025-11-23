<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;

trait CreatesApplication
{
    /**
     * Creates the application.
     */
    public function createApplication()
    {
        // Point d'entrée standard Laravel
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        // Forcer SQLite AVANT que RefreshDatabase ne s'exécute
        // Cela garantit que les tests utilisent SQLite même si .env définit MySQL
        $app['config']->set('database.default', 'sqlite');
        
        // Utiliser une base SQLite persistante pour permettre à RefreshDatabase
        // d'utiliser les transactions correctement
        $databasePath = database_path('testing.sqlite');
        
        // Vérifier que la configuration SQLite existe, sinon la créer
        $sqliteConfig = $app['config']->get('database.connections.sqlite', []);
        if (empty($sqliteConfig)) {
            $app['config']->set('database.connections.sqlite', [
                'driver' => 'sqlite',
                'database' => $databasePath,
                'prefix' => '',
                'foreign_key_constraints' => true,
            ]);
        } else {
            // Forcer la base de données de test même si configurée différemment
            $app['config']->set('database.connections.sqlite.database', $databasePath);
        }

        return $app;
    }
}