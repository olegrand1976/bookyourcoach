<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class ResetStudentPasswords extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'students:reset-passwords {password : Nouveau mot de passe (aucune valeur par défaut)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Réinitialise tous les mots de passe des élèves à un mot de passe uniforme';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Commande destructive : elle réécrit le mot de passe de tous les élèves
        // et les affiche. Elle n'a rien à faire en production.
        if (! app()->environment(['local', 'testing', 'development'])) {
            $this->error('Commande réservée aux environnements de développement.');

            return 1;
        }

        $password = $this->argument('password');
        
        $students = User::where('role', 'student')->get();
        
        if ($students->isEmpty()) {
            $this->warn('Aucun élève trouvé dans la base de données.');
            return 0;
        }
        
        $this->info("Réinitialisation du mot de passe de {$students->count()} élève(s)...");
        
        $bar = $this->output->createProgressBar($students->count());
        $bar->start();
        
        foreach ($students as $student) {
            $student->password = Hash::make($password);
            $student->save();
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine(2);
        $this->info("✅ Tous les mots de passe des élèves ont été réinitialisés à: {$password}");
        $this->newLine();
        $this->info("📋 Liste des élèves et leurs emails:");
        $this->newLine();
        
        foreach ($students as $student) {
            $this->line("  - {$student->name} : {$student->email}");
        }
        
        return 0;
    }
}

