<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Migration des anciennes disciplines (colonne legacy) vers discipline_settings
     * 
     * Contexte :
     * - Anciennement, les clubs stockaient leurs disciplines dans une colonne "disciplines" sous forme de tableau de strings
     * - Maintenant, ils utilisent "discipline_settings" avec des IDs de disciplines et leurs paramètres
     * 
     * Cette migration convertit automatiquement tous les clubs qui utilisent encore l'ancienne structure
     */
    public function up(): void
    {
        Log::info('🔧 Début de la migration des disciplines legacy vers discipline_settings');
        
        // Mapping des anciennes disciplines (strings) vers les nouveaux IDs
        $disciplineMapping = [
            // Équitation
            'dressage' => 1,
            'saut d\'obstacles' => 2,
            'saut d\'obstacle' => 2, // Variante
            'concours complet' => 3,
            'équitation western' => 4,
            'western' => 4, // Variante
            'endurance' => 5,
            'voltige' => 6,
            'équitation de loisir' => 7,
            'loisir' => 7, // Variante
            
            // Natation
            'natation' => 11, // Par défaut : cours individuel enfant
            'natation individuel' => 11,
            'natation individuelle' => 11,
            'cours individuel enfant' => 11,
            'natation enfant' => 11,
            'cours individuel adulte' => 12,
            'natation adulte' => 12,
            'cours aquagym' => 13,
            'aquagym' => 13,
            'cours collectif enfant' => 14,
            'natation collectif enfant' => 14,
            'cours collectif adulte' => 15,
            'natation collectif adulte' => 15,
            
            // Fitness
            'musculation' => 21,
            'crossfit' => 22,
            'cardio-training' => 23,
            'cardio' => 23, // Variante
            'yoga' => 24,
            'pilates' => 25,
            'zumba' => 26,
            
            // Sports collectifs
            'football' => 31,
            'basketball' => 32,
            'basket' => 32, // Variante
            'volleyball' => 33,
            'volley' => 33, // Variante
            'handball' => 34,
            'hand' => 34, // Variante
            'rugby' => 35,
            
            // Arts martiaux
            'karaté' => 41,
            'karate' => 41, // Variante
            'judo' => 42,
            'taekwondo' => 43,
            'boxe' => 44,
            'aïkido' => 45,
            'aikido' => 45, // Variante
            
            // Danse
            'danse classique' => 51,
            'classique' => 51, // Variante
            'danse moderne' => 52,
            'moderne' => 52, // Variante
            'hip-hop' => 53,
            'hip hop' => 53, // Variante
            'salsa' => 54,
            'tango' => 55,
            
            // Raquettes
            'tennis de table' => 61,
            'ping-pong' => 61, // Variante
            'tennis sur court' => 62,
            'tennis' => 62, // Variante
            'badminton' => 63,
            'bad' => 63, // Variante
            
            // Gymnastique
            'gymnastique artistique' => 71,
            'gym artistique' => 71, // Variante
            'gymnastique rythmique' => 72,
            'gym rythmique' => 72, // Variante
            'trampoline' => 73,
        ];
        
        // Récupérer tous les clubs
        $clubs = DB::table('clubs')->get();
        
        $convertedCount = 0;
        $skippedCount = 0;
        $errorCount = 0;
        
        foreach ($clubs as $club) {
            try {
                // Si le club a déjà des discipline_settings, on skip
                if ($club->discipline_settings && $club->discipline_settings !== 'null' && $club->discipline_settings !== '{}') {
                    Log::info("⏭️ Club {$club->id} ({$club->name}) : discipline_settings déjà configuré");
                    $skippedCount++;
                    continue;
                }
                
                // Si le club n'a pas de disciplines legacy, on skip
                if (!$club->disciplines || $club->disciplines === 'null' || $club->disciplines === '[]') {
                    Log::info("⏭️ Club {$club->id} ({$club->name}) : aucune discipline legacy trouvée");
                    $skippedCount++;
                    continue;
                }
                
                // Décoder les disciplines legacy
                $legacyDisciplines = json_decode($club->disciplines, true);
                if (!is_array($legacyDisciplines) || empty($legacyDisciplines)) {
                    Log::warning("⚠️ Club {$club->id} ({$club->name}) : disciplines legacy invalides");
                    $skippedCount++;
                    continue;
                }
                
                // Construire les discipline_settings
                $disciplineSettings = [];
                $convertedDisciplines = [];
                
                foreach ($legacyDisciplines as $legacyName) {
                    // Normaliser le nom (minuscules, trim)
                    $normalizedName = mb_strtolower(trim($legacyName));
                    
                    if (isset($disciplineMapping[$normalizedName])) {
                        $disciplineId = $disciplineMapping[$normalizedName];
                        
                        // Vérifier que la discipline existe toujours
                        $disciplineExists = DB::table('disciplines')->where('id', $disciplineId)->exists();
                        
                        if ($disciplineExists) {
                            $disciplineSettings[$disciplineId] = [
                                'price' => 50.00, // Prix par défaut
                                'duration_minutes' => 60,
                                'is_active' => true
                            ];
                            $convertedDisciplines[] = $legacyName . ' → ID ' . $disciplineId;
                        } else {
                            Log::warning("⚠️ Club {$club->id} : Discipline ID {$disciplineId} ({$legacyName}) n'existe plus");
                        }
                    } else {
                        Log::warning("⚠️ Club {$club->id} : Discipline non mappée : '{$legacyName}' (normalisé: '{$normalizedName}')");
                    }
                }
                
                // Si on a des disciplines converties, les sauvegarder
                if (!empty($disciplineSettings)) {
                    DB::table('clubs')
                        ->where('id', $club->id)
                        ->update([
                            'discipline_settings' => json_encode($disciplineSettings),
                            'updated_at' => now()
                        ]);
                    
                    Log::info("✅ Club {$club->id} ({$club->name}) : {count($disciplineSettings)} discipline(s) migrée(s)", [
                        'conversions' => $convertedDisciplines
                    ]);
                    
                    $convertedCount++;
                } else {
                    Log::warning("⚠️ Club {$club->id} ({$club->name}) : Aucune discipline valide trouvée");
                    $skippedCount++;
                }
                
            } catch (\Exception $e) {
                Log::error("❌ Erreur lors de la migration du club {$club->id} : " . $e->getMessage());
                $errorCount++;
            }
        }
        
        Log::info('✅ Migration terminée', [
            'total_clubs' => $clubs->count(),
            'converted' => $convertedCount,
            'skipped' => $skippedCount,
            'errors' => $errorCount
        ]);
    }

    /**
     * Reverse the migrations.
     * 
     * Note : Cette migration ne peut pas être inversée car elle convertit des données legacy.
     * Les discipline_settings sont conservés tel quel.
     */
    public function down(): void
    {
        Log::warning('⚠️ La migration des disciplines legacy ne peut pas être inversée');
        // On ne fait rien, car il n'y a pas de retour en arrière possible
        // Les discipline_settings restent tel quel
    }
};
