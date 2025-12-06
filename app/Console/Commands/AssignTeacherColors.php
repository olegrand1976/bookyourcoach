<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Teacher;
use Illuminate\Support\Facades\Schema;

class AssignTeacherColors extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'teachers:assign-colors {--force : Réassigner les couleurs même si déjà définies}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assigner des couleurs pastel aux enseignants qui n\'en ont pas encore';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🎨 Attribution des couleurs aux enseignants...');
        
        // Vérifier si la colonne color existe
        if (!Schema::hasColumn('teachers', 'color')) {
            $this->error('❌ La colonne "color" n\'existe pas encore dans la table "teachers".');
            $this->info('💡 Exécutez d\'abord la migration : php artisan migrate');
            return 1;
        }
        
        $query = Teacher::query();
        
        if (!$this->option('force')) {
            $query->whereNull('color');
        }
        
        $teachers = $query->get();
        
        if ($teachers->isEmpty()) {
            $this->info('✅ Tous les enseignants ont déjà une couleur assignée.');
            if (!$this->option('force')) {
                $this->info('💡 Utilisez --force pour réassigner toutes les couleurs.');
            }
            return 0;
        }
        
        $bar = $this->output->createProgressBar($teachers->count());
        $bar->start();
        
        $assigned = 0;
        foreach ($teachers as $teacher) {
            $teacher->assignColorFromPalette();
            $assigned++;
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine();
        $this->info("✅ {$assigned} couleur(s) assignée(s) avec succès !");
        
        return 0;
    }
}
