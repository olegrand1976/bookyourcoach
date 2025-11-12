<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Club;
use App\Models\Discipline;
use App\Models\CourseType;

class SyncAllClubCourseTypes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'club:sync-course-types {--club-id= : Synchroniser un club spécifique}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronise les CourseTypes avec les discipline_settings de tous les clubs (ou d\'un club spécifique)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Synchronisation des CourseTypes avec discipline_settings');
        $this->info('═══════════════════════════════════════════════════════════');
        
        // Si un club spécifique est demandé
        if ($clubId = $this->option('club-id')) {
            $clubs = Club::where('id', $clubId)->get();
            if ($clubs->isEmpty()) {
                $this->error("❌ Club ID {$clubId} introuvable");
                return 1;
            }
        } else {
            $clubs = Club::all();
        }
        
        $this->info("📊 Clubs à traiter : {$clubs->count()}");
        $this->newLine();
        
        $successCount = 0;
        $skippedCount = 0;
        $errorCount = 0;
        
        foreach ($clubs as $club) {
            try {
                $this->info("🏢 Club {$club->id}: {$club->name}");
                
                // Récupérer discipline_settings
                $disciplineSettings = $club->discipline_settings;
                if (is_string($disciplineSettings)) {
                    $disciplineSettings = json_decode($disciplineSettings, true);
                }
                
                if (empty($disciplineSettings)) {
                    $this->warn("   ⏭️ Aucun discipline_settings configuré - ignoré");
                    $skippedCount++;
                    continue;
                }
                
                $courseTypesCreated = 0;
                $courseTypesUpdated = 0;
                
                foreach ($disciplineSettings as $disciplineId => $settings) {
                    // Vérifier que la discipline existe
                    $discipline = Discipline::find($disciplineId);
                    if (!$discipline) {
                        $this->warn("   ⚠️ Discipline {$disciplineId} introuvable - ignorée");
                        continue;
                    }
                    
                    // Extraire les paramètres
                    $duration = $settings['duration'] ?? $settings['duration_minutes'] ?? 60;
                    $price = $settings['price'] ?? 0;
                    $isIndividual = $settings['is_individual'] ?? true;
                    $maxParticipants = $isIndividual ? 1 : ($settings['max_participants'] ?? 8);
                    
                    // Chercher un CourseType existant
                    $existingCourseType = CourseType::where('club_id', $club->id)
                        ->where('discipline_id', $disciplineId)
                        ->first();
                    
                    if ($existingCourseType) {
                        // Mettre à jour
                        $existingCourseType->update([
                            'duration_minutes' => $duration,
                            'price' => $price,
                            'is_individual' => $isIndividual,
                            'max_participants' => $maxParticipants,
                        ]);
                        
                        $this->line("   ✅ {$discipline->name}: CourseType mis à jour (ID: {$existingCourseType->id}) - {$duration}min, {$price}€");
                        $courseTypesUpdated++;
                    } else {
                        // Créer
                        $newCourseType = CourseType::create([
                            'club_id' => $club->id,
                            'discipline_id' => $disciplineId,
                            'name' => $isIndividual ? 'Cours individuel' : 'Cours collectif',
                            'description' => "Type de cours configuré pour {$discipline->name}",
                            'duration_minutes' => $duration,
                            'price' => $price,
                            'is_individual' => $isIndividual,
                            'max_participants' => $maxParticipants,
                            'is_active' => true,
                        ]);
                        
                        $this->line("   🆕 {$discipline->name}: CourseType créé (ID: {$newCourseType->id}) - {$duration}min, {$price}€");
                        $courseTypesCreated++;
                    }
                }
                
                $this->info("   ✅ Terminé: {$courseTypesCreated} créé(s), {$courseTypesUpdated} mis à jour");
                $successCount++;
                
            } catch (\Exception $e) {
                $this->error("   ❌ Erreur: {$e->getMessage()}");
                $errorCount++;
            }
            
            $this->newLine();
        }
        
        $this->info('═══════════════════════════════════════════════════════════');
        $this->info('📊 RÉSUMÉ');
        $this->info("✅ Succès: {$successCount}");
        $this->info("⏭️ Ignorés: {$skippedCount}");
        if ($errorCount > 0) {
            $this->error("❌ Erreurs: {$errorCount}");
        }
        
        return 0;
    }
}
