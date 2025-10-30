<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class FixUtf8Encoding extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:utf8-encoding
                          {--dry-run : Afficher les changements sans les appliquer}
                          {--table=users : Table à corriger (users par défaut)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Corrige l\'encodage UTF-8 des noms dans la base de données';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $table = $this->option('table');
        
        $this->info('🔧 Correction de l\'encodage UTF-8...');
        $this->info('Mode: ' . ($dryRun ? 'DRY RUN (aucune modification)' : 'MODIFICATION ACTIVE'));
        $this->info('');
        
        if ($table === 'users') {
            $this->fixUsersTable($dryRun);
        }
        
        $this->info('');
        $this->info('✅ Terminé!');
        
        return Command::SUCCESS;
    }
    
    /**
     * Corrige l'encodage de la table users
     */
    private function fixUsersTable($dryRun = false)
    {
        $this->info('📋 Table: users');
        
        $users = User::all();
        $fixed = 0;
        
        foreach ($users as $user) {
            $nameFixed = $this->fixEncoding($user->name);
            $firstNameFixed = $this->fixEncoding($user->first_name);
            $lastNameFixed = $this->fixEncoding($user->last_name);
            
            $changed = false;
            $changes = [];
            
            if ($nameFixed !== $user->name) {
                $changes[] = "name: '{$user->name}' → '{$nameFixed}'";
                $changed = true;
            }
            
            if ($firstNameFixed !== $user->first_name) {
                $changes[] = "first_name: '{$user->first_name}' → '{$firstNameFixed}'";
                $changed = true;
            }
            
            if ($lastNameFixed !== $user->last_name) {
                $changes[] = "last_name: '{$user->last_name}' → '{$lastNameFixed}'";
                $changed = true;
            }
            
            if ($changed) {
                $this->warn("👤 User #{$user->id} ({$user->email}):");
                foreach ($changes as $change) {
                    $this->line("   • $change");
                }
                
                if (!$dryRun) {
                    $user->name = $nameFixed;
                    $user->first_name = $firstNameFixed;
                    $user->last_name = $lastNameFixed;
                    $user->save();
                    $this->info("   ✓ Sauvegardé");
                }
                
                $fixed++;
            }
        }
        
        $this->info('');
        $this->info("📊 Résultat: {$fixed} utilisateur(s) corrigé(s) sur {$users->count()}");
    }
    
    /**
     * Corrige l'encodage d'une chaîne
     */
    private function fixEncoding($string)
    {
        if (empty($string)) {
            return $string;
        }
        
        // Détecte si la chaîne contient des caractères mal encodés (double encodage UTF-8)
        // Pattern: Ã suivi d'un caractère > 127 (comme Ã©, Ã¨, Ã , etc.)
        if (preg_match('/Ã[\x80-\xFF]/', $string)) {
            // La chaîne est mal encodée, on la corrige
            // Méthode 1: Décoder comme si c'était du ISO-8859-1 puis ré-encoder en UTF-8
            $fixed = mb_convert_encoding($string, 'UTF-8', 'ISO-8859-1');
            
            // Vérifier si la correction a fonctionné
            if ($this->isValidUtf8($fixed) && !preg_match('/Ã[\x80-\xFF]/', $fixed)) {
                return $fixed;
            }
            
            // Méthode 2: Si la méthode 1 ne fonctionne pas, essayer une correction manuelle
            $replacements = [
                'Ã©' => 'é',
                'Ã¨' => 'è',
                'Ãª' => 'ê',
                'Ã«' => 'ë',
                'Ã ' => 'à',
                'Ã¢' => 'â',
                'Ã´' => 'ô',
                'Ã¹' => 'ù',
                'Ã»' => 'û',
                'Ã§' => 'ç',
                'Ã®' => 'î',
                'Ã¯' => 'ï',
                'Ã‰' => 'É',
                'Ãˆ' => 'È',
                'ÃŠ' => 'Ê',
                'Ã‹' => 'Ë',
                'Ã€' => 'À',
                'Ã‚' => 'Â',
                'Ã"' => 'Ô',
                'Ã™' => 'Ù',
                'Ã›' => 'Û',
                'Ã‡' => 'Ç',
                'ÃŽ' => 'Î',
                'Ã' => 'Ï',
            ];
            
            return str_replace(array_keys($replacements), array_values($replacements), $string);
        }
        
        return $string;
    }
    
    /**
     * Vérifie si une chaîne est en UTF-8 valide
     */
    private function isValidUtf8($string)
    {
        return mb_check_encoding($string, 'UTF-8');
    }
}

