<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DiagnoseClubProfile extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'club:diagnose-profile {club_id?}';

    /**
     * The command description.
     *
     * @var string
     */
    protected $description = 'Diagnostique les colonnes de la table clubs et vérifie les données';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $clubId = $this->argument('club_id');
        
        $this->info('🔍 Diagnostic de la table clubs');
        $this->newLine();
        
        // 1. Lister toutes les colonnes de la table clubs
        $columns = Schema::getColumnListing('clubs');
        $this->info('📋 Colonnes existantes dans la table clubs:');
        foreach ($columns as $column) {
            $this->line('  ✓ ' . $column);
        }
        $this->newLine();
        
        // 2. Vérifier les champs légaux spécifiquement
        $legalFields = [
            'company_number',
            'legal_representative_name',
            'legal_representative_role',
            'insurance_rc_company',
            'insurance_rc_policy_number',
            'insurance_additional_company',
            'insurance_additional_policy_number',
            'insurance_additional_details',
            'expense_reimbursement_type',
            'expense_reimbursement_details'
        ];
        
        $this->info('🔍 Vérification des champs légaux:');
        foreach ($legalFields as $field) {
            $exists = in_array($field, $columns);
            if ($exists) {
                $this->line('  ✅ ' . $field);
            } else {
                $this->error('  ❌ MANQUANT: ' . $field);
            }
        }
        $this->newLine();
        
        // 3. Si un club_id est fourni, afficher ses données
        if ($clubId) {
            $club = DB::table('clubs')->where('id', $clubId)->first();
            
            if ($club) {
                $this->info("📊 Données du club #{$clubId}:");
                $clubData = (array) $club;
                
                foreach ($legalFields as $field) {
                    $value = $clubData[$field] ?? 'N/A';
                    $status = !empty($value) && $value !== 'N/A' ? '✅' : '⚠️';
                    $this->line("  {$status} {$field}: " . ($value === 'N/A' ? 'N/A' : ($value ?: 'NULL')));
                }
            } else {
                $this->error("Club #{$clubId} introuvable");
            }
        } else {
            // Afficher tous les clubs avec leur statut de complétude
            $clubs = DB::table('clubs')->get();
            $this->info('📊 Liste des clubs:');
            foreach ($clubs as $club) {
                $clubData = (array) $club;
                $completedFields = 0;
                foreach ($legalFields as $field) {
                    if (isset($clubData[$field]) && !empty($clubData[$field])) {
                        $completedFields++;
                    }
                }
                $percentage = round(($completedFields / count($legalFields)) * 100);
                $this->line("  Club #{$club->id} - {$club->name}: {$completedFields}/{" . count($legalFields) . "} champs légaux ({$percentage}%)");
            }
        }
        
        return Command::SUCCESS;
    }
}

