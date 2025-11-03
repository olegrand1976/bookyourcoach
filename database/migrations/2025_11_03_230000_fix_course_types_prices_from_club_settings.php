<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * 🔧 CORRECTION : Mettre à jour les prix des types de cours depuis les discipline_settings des clubs
     * 
     * PROBLÈME :
     * - Les types de cours ont price = NULL
     * - Les clubs ont défini des prix dans leurs discipline_settings
     * - Lors de la création d'un cours, le prix est à 0 au lieu d'utiliser le prix configuré
     * 
     * SOLUTION :
     * - Pour chaque type de cours sans prix (price = NULL)
     * - Trouver les clubs qui utilisent la discipline de ce type
     * - Récupérer le prix depuis discipline_settings
     * - Mettre à jour le prix du type de cours
     */
    public function up(): void
    {
        Log::info('🔧 [MIGRATION] Début correction des prix des types de cours');
        
        // Récupérer tous les types de cours sans prix
        $courseTypesWithoutPrice = DB::table('course_types')
            ->whereNull('price')
            ->orWhere('price', 0)
            ->get();
        
        $updated = 0;
        $skipped = 0;
        
        foreach ($courseTypesWithoutPrice as $courseType) {
            if (!$courseType->discipline_id) {
                Log::info("⏭️ Type de cours générique ignoré: {$courseType->name}");
                $skipped++;
                continue;
            }
            
            // Chercher les clubs qui ont cette discipline
            $clubs = DB::table('clubs')
                ->whereRaw("JSON_CONTAINS(disciplines, ?)", [json_encode($courseType->discipline_id)])
                ->get();
            
            if ($clubs->isEmpty()) {
                Log::warning("⚠️ Aucun club trouvé pour discipline {$courseType->discipline_id} (type: {$courseType->name})");
                $skipped++;
                continue;
            }
            
            // Prendre le premier club et extraire le prix de ses discipline_settings
            $club = $clubs->first();
            $disciplineSettings = json_decode($club->discipline_settings ?? '{}', true);
            
            if (isset($disciplineSettings[$courseType->discipline_id]['price'])) {
                $price = $disciplineSettings[$courseType->discipline_id]['price'];
                
                DB::table('course_types')
                    ->where('id', $courseType->id)
                    ->update([
                        'price' => $price,
                        'updated_at' => now()
                    ]);
                
                Log::info("✅ Prix mis à jour pour '{$courseType->name}': {$price}€", [
                    'course_type_id' => $courseType->id,
                    'discipline_id' => $courseType->discipline_id,
                    'club_id' => $club->id,
                    'price' => $price
                ]);
                
                $updated++;
            } else {
                Log::warning("⚠️ Pas de prix configuré pour discipline {$courseType->discipline_id} dans club {$club->id}");
                $skipped++;
            }
        }
        
        Log::info('✅ [MIGRATION] Correction des prix terminée', [
            'total' => count($courseTypesWithoutPrice),
            'updated' => $updated,
            'skipped' => $skipped
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Log::info('⚠️ [MIGRATION] Rollback de la correction des prix (pas d\'action)');
        // Pas de rollback car on ne peut pas restaurer les anciennes valeurs NULL
    }
};

