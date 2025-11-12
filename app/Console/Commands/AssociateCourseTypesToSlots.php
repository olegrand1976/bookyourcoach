<?php

namespace App\Console\Commands;

use App\Models\ClubOpenSlot;
use App\Models\CourseType;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AssociateCourseTypesToSlots extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'slots:associate-course-types {--dry-run : Simulate without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Associe automatiquement les types de cours aux créneaux basés sur leur discipline';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        $mode = $isDryRun ? 'DRY RUN (simulation)' : 'MODE ACTIF';
        
        $this->info("🔧 Association des types de cours aux créneaux");
        $this->info("Mode: {$mode}");
        $this->newLine();

        $slots = ClubOpenSlot::with(['courseTypes', 'discipline'])->get();
        
        if ($slots->isEmpty()) {
            $this->warn('Aucun créneau trouvé.');
            return Command::SUCCESS;
        }

        $this->info("📊 {$slots->count()} créneau(x) trouvé(s)");
        $this->newLine();

        $processedCount = 0;
        $skippedCount = 0;
        $errorCount = 0;

        foreach ($slots as $slot) {
            $disciplineName = $slot->discipline ? $slot->discipline->name : 'Sans discipline';
            $this->line("🔍 Créneau #{$slot->id} - {$disciplineName}");
            
            // Si pas de discipline, on skip
            if (!$slot->discipline_id) {
                $this->warn("   ⏭️  Pas de discipline associée, ignoré");
                $skippedCount++;
                continue;
            }

            // Vérifier si le créneau a déjà des types de cours associés
            $currentCourseTypes = $slot->courseTypes;
            $currentCount = $currentCourseTypes->count();
            
            $this->line("   📋 Types de cours actuels: {$currentCount}");

            // Récupérer tous les types de cours actifs pour cette discipline
            $availableCourseTypes = CourseType::where('discipline_id', $slot->discipline_id)
                ->where('is_active', true)
                ->get();
            
            $availableCount = $availableCourseTypes->count();
            $this->line("   🎯 Types de cours disponibles pour {$disciplineName}: {$availableCount}");

            if ($availableCount === 0) {
                $this->warn("   ⚠️  Aucun type de cours actif trouvé pour cette discipline");
                $skippedCount++;
                continue;
            }

            // Comparer et associer
            $newCourseTypeIds = $availableCourseTypes->pluck('id')->toArray();
            $currentCourseTypeIds = $currentCourseTypes->pluck('id')->toArray();
            
            $toAdd = array_diff($newCourseTypeIds, $currentCourseTypeIds);
            
            if (empty($toAdd)) {
                $this->info("   ✅ Déjà à jour, rien à faire");
                $skippedCount++;
                continue;
            }

            $this->line("   ➕ Types à associer: " . count($toAdd));
            
            if (!$isDryRun) {
                try {
                    // Associer les types de cours (merge pour ne pas supprimer les existants)
                    $slot->courseTypes()->syncWithoutDetaching($newCourseTypeIds);
                    
                    Log::info('AssociateCourseTypesToSlots - Types associés', [
                        'slot_id' => $slot->id,
                        'discipline_id' => $slot->discipline_id,
                        'discipline_name' => $disciplineName,
                        'course_types_added' => count($toAdd),
                        'course_type_ids' => $newCourseTypeIds
                    ]);
                    
                    $this->info("   ✅ Types de cours associés avec succès");
                    $processedCount++;
                } catch (\Exception $e) {
                    $this->error("   ❌ Erreur: " . $e->getMessage());
                    Log::error('AssociateCourseTypesToSlots - Erreur', [
                        'slot_id' => $slot->id,
                        'error' => $e->getMessage()
                    ]);
                    $errorCount++;
                }
            } else {
                $this->comment("   💡 [DRY-RUN] Associerait " . count($toAdd) . " type(s) de cours");
                $processedCount++;
            }
            
            $this->newLine();
        }

        $this->newLine();
        $this->info("📊 Résumé:");
        $this->info("   ✅ Créneaux traités: {$processedCount}");
        $this->info("   ⏭️  Créneaux ignorés: {$skippedCount}");
        if ($errorCount > 0) {
            $this->error("   ❌ Erreurs: {$errorCount}");
        }
        
        if ($isDryRun) {
            $this->newLine();
            $this->comment("💡 Pour appliquer les changements, relancez sans --dry-run");
        }

        return Command::SUCCESS;
    }
}

