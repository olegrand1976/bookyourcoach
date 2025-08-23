<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('🌱 Démarrage du seeding de la base de données...');

        // 1. Paramètres de l'application (branding)
        $this->command->info('📱 Création des paramètres d\'application...');
        $this->call(AppSettingSeeder::class);

        // 2. Types de cours
        $this->command->info('📚 Création des types de cours...');
        $this->call(CourseTypeSeeder::class);

        // 3. Lieux
        $this->command->info('🏢 Création des lieux...');
        $this->call(LocationSeeder::class);

        // 4. Utilisateurs (admin, enseignants, élèves)
        $this->command->info('👥 Création des utilisateurs...');
        $this->call(UserSeeder::class);

        // 5. Données de démonstration (leçons, disponibilités, paiements)
        $this->command->info('🎯 Création des données de démonstration...');
        $this->call(DemoDataSeeder::class);

        $this->command->info('✅ Seeding terminé avec succès !');
        $this->command->line('');
        $this->command->info('🔑 Comptes de test créés :');
        $this->command->line('   Admin: admin@bookyourcoach.com / password123');
        $this->command->line('   Enseignants: sophie.martin@bookyourcoach.com / password123');
        $this->command->line('   Élèves: alice.durand@email.com / password123');
        $this->command->line('');
        $this->command->info('🌐 Application disponible sur : http://localhost:8081');
        $this->command->info('📖 Documentation API : http://localhost:8081/docs');
        $this->command->info('🗄️ PHPMyAdmin : http://localhost:8082');
    }
}
