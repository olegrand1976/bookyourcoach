<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * 🔧 CORRECTION : Corriger l'incohérence entre les disciplines des créneaux et des types de cours
     * 
     * PROBLÈME IDENTIFIÉ :
     * - Club 11 (ACTI'VIBE) a les disciplines [2, 11]
     * - Les créneaux ont discipline_id = 11 (Natation individuel)
     * - Mais ces créneaux sont liés au course_type_id = 17 qui a discipline_id = 2 (Natation)
     * - INCOHÉRENCE : Le créneau demande "Natation individuel" mais le type de cours est "Natation standard"
     * 
     * CORRECTION :
     * - Pour chaque association créneau ↔ type de cours :
     *   1. Récupérer la discipline_id du créneau
     *   2. Vérifier si le type de cours a la même discipline_id
     *   3. Si NON, chercher un type de cours compatible et remplacer
     *   4. Si aucun type compatible, supprimer l'association (et logger)
     */
    public function up(): void
    {
        Log::info('🔧 [MIGRATION] Début correction des incohérences discipline créneau ↔ type de cours');
        
        // Récupérer toutes les associations créneau ↔ type de cours
        $associations = DB::table('club_open_slot_course_types as cosct')
            ->join('club_open_slots as cos', 'cosct.club_open_slot_id', '=', 'cos.id')
            ->join('course_types as ct', 'cosct.course_type_id', '=', 'ct.id')
            ->select(
                'cosct.id as association_id',
                'cosct.club_open_slot_id',
                'cosct.course_type_id',
                'cos.discipline_id as slot_discipline_id',
                'ct.discipline_id as course_type_discipline_id',
                'ct.name as course_type_name',
                'cos.club_id'
            )
            ->get();
        
        $corrected = 0;
        $deleted = 0;
        $unchanged = 0;
        
        foreach ($associations as $assoc) {
            // Si les disciplines correspondent, rien à faire
            if ($assoc->slot_discipline_id == $assoc->course_type_discipline_id) {
                $unchanged++;
                continue;
            }
            
            Log::warning('⚠️ Incohérence détectée', [
                'association_id' => $assoc->association_id,
                'club_id' => $assoc->club_id,
                'slot_id' => $assoc->club_open_slot_id,
                'slot_discipline' => $assoc->slot_discipline_id,
                'course_type_id' => $assoc->course_type_id,
                'course_type_name' => $assoc->course_type_name,
                'course_type_discipline' => $assoc->course_type_discipline_id
            ]);
            
            // Chercher un type de cours compatible (même discipline que le créneau)
            $compatibleCourseType = DB::table('course_types')
                ->where('discipline_id', $assoc->slot_discipline_id)
                ->where('is_active', true)
                ->first();
            
            if ($compatibleCourseType) {
                // Remplacer par le type compatible
                DB::table('club_open_slot_course_types')
                    ->where('id', $assoc->association_id)
                    ->update([
                        'course_type_id' => $compatibleCourseType->id,
                        'updated_at' => now()
                    ]);
                
                Log::info('✅ Association corrigée', [
                    'association_id' => $assoc->association_id,
                    'old_course_type' => $assoc->course_type_id,
                    'new_course_type' => $compatibleCourseType->id,
                    'new_course_type_name' => $compatibleCourseType->name
                ]);
                
                $corrected++;
            } else {
                // Aucun type compatible trouvé, supprimer l'association
                DB::table('club_open_slot_course_types')
                    ->where('id', $assoc->association_id)
                    ->delete();
                
                Log::warning('❌ Association supprimée (aucun type compatible)', [
                    'association_id' => $assoc->association_id,
                    'slot_discipline' => $assoc->slot_discipline_id
                ]);
                
                $deleted++;
            }
        }
        
        Log::info('✅ [MIGRATION] Correction terminée', [
            'total' => count($associations),
            'unchanged' => $unchanged,
            'corrected' => $corrected,
            'deleted' => $deleted
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Log::info('⚠️ [MIGRATION] Rollback de la correction des incohérences (aucune action)');
        // Pas de rollback possible car on ne peut pas restaurer les anciennes associations incorrectes
    }
};

