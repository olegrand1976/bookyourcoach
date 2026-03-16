<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClubOpenSlot;
use App\Models\CourseType;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ClubOpenSlotController extends Controller
{
    /**
     * Récupérer un créneau avec ses types de cours
     */
    public function show(string $id): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Vérifier les permissions
            if ($user->role !== 'club' && $user->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé'
                ], 403);
            }

            $slot = ClubOpenSlot::with(['courseTypes', 'discipline'])->find($id);
            
            if (!$slot) {
                return response()->json([
                    'success' => false,
                    'message' => 'Créneau non trouvé'
                ], 404);
            }

            // Si c'est un club, vérifier qu'il possède ce créneau
            if ($user->role === 'club') {
                $club = $user->getFirstClub();
                if (!$club || $slot->club_id !== $club->id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Accès non autorisé à ce créneau'
                    ], 403);
                }
            }

            return response()->json([
                'success' => true,
                'data' => $slot
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération du créneau:', [
                'error' => $e->getMessage(),
                'slot_id' => $id
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du créneau'
            ], 500);
        }
    }

    /**
     * Mettre à jour les types de cours d'un créneau
     */
    public function updateCourseTypes(Request $request, string $id): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Vérifier les permissions
            if ($user->role !== 'club' && $user->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé'
                ], 403);
            }

            $slot = ClubOpenSlot::find($id);
            
            if (!$slot) {
                return response()->json([
                    'success' => false,
                    'message' => 'Créneau non trouvé'
                ], 404);
            }

            // Si c'est un club, vérifier qu'il possède ce créneau
            if ($user->role === 'club') {
                $club = $user->getFirstClub();
                if (!$club || $slot->club_id !== $club->id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Accès non autorisé à ce créneau'
                    ], 403);
                }
            }

            // Valider les données
            $validated = $request->validate([
                'course_type_ids' => 'required|array',
                'course_type_ids.*' => 'exists:course_types,id'
            ]);

            // 🔒 VALIDATION STRICTE : Vérifier que les types de cours correspondent à la discipline du créneau
            if ($slot->discipline_id) {
                $courseTypes = CourseType::whereIn('id', $validated['course_type_ids'])->get();
                
                $slotDisciplineName = \App\Models\Discipline::find($slot->discipline_id)?->name ?? "ID {$slot->discipline_id}";
                
                foreach ($courseTypes as $courseType) {
                    // ✅ CORRECTION : Le type de cours DOIT avoir la même discipline_id que le créneau
                    // On n'accepte PAS les types génériques (discipline_id = NULL) pour éviter les incohérences
                    if ($courseType->discipline_id != $slot->discipline_id) {
                        $courseTypeDisciplineName = $courseType->discipline?->name ?? ($courseType->discipline_id ? "ID {$courseType->discipline_id}" : "Générique");
                        
                        return response()->json([
                            'success' => false,
                            'message' => "Le type de cours '{$courseType->name}' (discipline: {$courseTypeDisciplineName}) ne correspond pas à la discipline du créneau ({$slotDisciplineName}). Pour garantir la cohérence, seuls les types de cours de la discipline '{$slotDisciplineName}' peuvent être associés à ce créneau.",
                            'errors' => [
                                'course_type_ids' => [
                                    "Incohérence détectée : Type '{$courseType->name}' (discipline: {$courseTypeDisciplineName}) incompatible avec le créneau (discipline: {$slotDisciplineName})"
                                ]
                            ]
                        ], 422);
                    }
                }
                
                Log::info('ClubOpenSlotController::updateCourseTypes - Validation OK', [
                    'slot_id' => $slot->id,
                    'slot_discipline' => $slotDisciplineName,
                    'course_types_validated' => $courseTypes->map(fn($ct) => [
                        'id' => $ct->id,
                        'name' => $ct->name,
                        'discipline_id' => $ct->discipline_id
                    ])->toArray()
                ]);
            }

            // Synchroniser les types de cours
            $slot->courseTypes()->sync($validated['course_type_ids']);

            return response()->json([
                'success' => true,
                'message' => 'Types de cours mis à jour avec succès',
                'data' => $slot->load(['courseTypes', 'discipline'])
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la mise à jour des types de cours:', [
                'error' => $e->getMessage(),
                'slot_id' => $id
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour des types de cours'
            ], 500);
        }
    }

    /**
     * Récupérer tous les créneaux d'un club avec leurs types de cours
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Vérifier les permissions
            if ($user->role !== 'club' && $user->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé'
                ], 403);
            }

            $query = ClubOpenSlot::with(['courseTypes', 'discipline']);

            // Si c'est un club, filtrer par club_id
            if ($user->role === 'club') {
                $club = $user->getFirstClub();
                if (!$club) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Club non trouvé'
                    ], 404);
                }
                $query->where('club_id', $club->id);
            }

            // Filtre par défaut : afficher uniquement les créneaux actifs
            // (sauf si is_active=false est explicitement demandé)
            if ($request->has('is_active')) {
                $query->where('is_active', $request->is_active);
            } else {
                // Par défaut, afficher uniquement les créneaux actifs
                $query->where('is_active', true);
            }

            // Filtres optionnels
            if ($request->has('day_of_week')) {
                $query->where('day_of_week', $request->day_of_week);
            }

            $slots = $query->orderBy('day_of_week')->orderBy('start_time')->get();
            
            \Log::info('ClubOpenSlotController::index - Créneaux récupérés', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'club_id' => $user->role === 'club' ? ($user->getFirstClub()?->id ?? null) : null,
                'slots_count' => $slots->count(),
                'is_active_filter' => $request->has('is_active') ? $request->is_active : true
            ]);

            // Récupérer les IDs des disciplines du club pour filtrer les types de cours
            $clubDisciplineIds = [];
            if ($user->role === 'club') {
                $club = $user->getFirstClub();
                
                // 🔧 CORRECTION : Parser correctement les disciplines du club
                $rawDisciplines = $club->disciplines;
                
                // Si c'est une string JSON, la parser
                if (is_string($rawDisciplines)) {
                    try {
                        $clubDisciplineIds = json_decode($rawDisciplines, true) ?? [];
                    } catch (\Exception $e) {
                        Log::warning('ClubOpenSlotController::index - Erreur parsing disciplines JSON', [
                            'club_id' => $club->id,
                            'raw_value' => $rawDisciplines,
                            'error' => $e->getMessage()
                        ]);
                        $clubDisciplineIds = [];
                    }
                } elseif (is_array($rawDisciplines)) {
                    $clubDisciplineIds = $rawDisciplines;
                } else {
                    $clubDisciplineIds = [];
                }
                
                // S'assurer que les IDs sont des entiers
                $clubDisciplineIds = array_map('intval', array_filter($clubDisciplineIds));
                
                Log::info('ClubOpenSlotController::index - Filtrage par disciplines du club', [
                    'club_id' => $club->id,
                    'club_name' => $club->name,
                    'club_disciplines_raw' => $rawDisciplines,
                    'club_disciplines_type' => gettype($rawDisciplines),
                    'club_disciplines_parsed' => $clubDisciplineIds,
                    'is_array' => is_array($clubDisciplineIds),
                    'count' => count($clubDisciplineIds)
                ]);
            }

            // Enrichir les données avec time_step et min_duration calculés
            $enrichedSlots = $slots->map(function($slot) use ($clubDisciplineIds, $user) {
                $courseTypes = $slot->courseTypes;
                
                // ✅ CORRECTION : Filtrer les types de cours par disciplines du club
                if ($user->role === 'club') {
                    // ⚠️ Si le club n'a pas de disciplines configurées, logger un warning
                    if (empty($clubDisciplineIds)) {
                        Log::warning("ClubOpenSlotController::index - Club sans disciplines configurées", [
                            'slot_id' => $slot->id,
                            'message' => 'Le club n\'a pas de disciplines configurées. Seuls les types génériques seront affichés.'
                        ]);
                        
                        // Ne garder que les types génériques (sans discipline)
                        $courseTypes = $courseTypes->filter(function($courseType) {
                            return !$courseType->discipline_id;
                        })->values();
                    } else {
                    $originalCount = $courseTypes->count();
                    
                    $courseTypes = $courseTypes->filter(function($courseType) use ($clubDisciplineIds, $slot) {
                        // Conversion en entier pour comparaison sûre
                        $courseTypeDisciplineId = $courseType->discipline_id ? intval($courseType->discipline_id) : null;
                        $slotDisciplineId = $slot->discipline_id ? intval($slot->discipline_id) : null;
                        
                        // 🎯 LOGIQUE DE FILTRAGE :
                        // 1. Si le type de cours n'a pas de discipline (générique) → GARDER
                        // 2. Si le type de cours a une discipline qui est dans celles du club → GARDER
                        // 3. Sinon → REJETER
                        
                        $isGeneric = !$courseTypeDisciplineId;
                        $isInClubDisciplines = $courseTypeDisciplineId && in_array($courseTypeDisciplineId, $clubDisciplineIds, true);
                        $keep = $isGeneric || $isInClubDisciplines;
                        
                        Log::debug("Slot {$slot->id} - Type {$courseType->id} ({$courseType->name})", [
                            'course_type_discipline' => $courseTypeDisciplineId,
                            'slot_discipline' => $slotDisciplineId,
                            'is_generic' => $isGeneric,
                            'is_in_club' => $isInClubDisciplines,
                            'keep' => $keep
                        ]);
                        
                        return $keep;
                    })->values();
                    
                    Log::info("ClubOpenSlotController::index - Types filtrés pour slot {$slot->id}", [
                        'slot_id' => $slot->id,
                        'slot_discipline_id' => $slot->discipline_id,
                        'club_disciplines' => $clubDisciplineIds,
                        'total_before' => $originalCount,
                        'total_after' => $courseTypes->count(),
                        'filtered_types' => $courseTypes->map(fn($ct) => [
                            'id' => $ct->id,
                            'name' => $ct->name,
                            'discipline_id' => $ct->discipline_id
                        ])->toArray()
                    ]);
                    }
                }
                
                // Calculer le pas de temps (PGCD des durées)
                $durations = array_values($courseTypes->pluck('duration_minutes')->filter()->toArray());
                $timeStep = $this->calculateTimeStep($durations);
                
                // Trouver la durée minimale
                $minDuration = !empty($durations) ? min($durations) : 60;
                
                // Trouver la durée maximale
                $maxDuration = !empty($durations) ? max($durations) : 60;
                
                $slotData = $slot->toArray();
                // ✅ Garantir que max_slots (nombre de cours simultanés sur la plage) est toujours présent
                $slotData['max_slots'] = $slot->max_slots ?? 1;

                // ✅ IMPORTANT : Remplacer les courseTypes par la version filtrée
                $slotData['course_types'] = $courseTypes->toArray();

                $slotData['time_step'] = $timeStep;
                $slotData['min_duration'] = $minDuration;
                $slotData['max_duration'] = $maxDuration;
                
                return $slotData;
            });

            return response()->json([
                'success' => true,
                'data' => $enrichedSlots
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des créneaux:', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des créneaux'
            ], 500);
        }
    }

    /**
     * Créer un nouveau créneau
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Vérifier les permissions
            if ($user->role !== 'club' && $user->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé'
                ], 403);
            }

            $club = $user->getFirstClub();
            if (!$club) {
                return response()->json([
                    'success' => false,
                    'message' => 'Club non trouvé'
                ], 404);
            }

            // Validation
            $validated = $request->validate([
                'day_of_week' => 'required|integer|between:0,6',
                'start_time' => 'required|date_format:H:i',
                'end_time' => 'required|date_format:H:i|after:start_time',
                'discipline_id' => 'nullable|exists:disciplines,id',
                'max_capacity' => 'required|integer|min:1|max:50',
                'max_slots' => 'nullable|integer|min:1|max:100',
                'duration' => 'required|integer|min:15',
                'price' => 'required|numeric|min:0',
                'is_active' => 'nullable|boolean'
            ]);

            // Ajouter le club_id et valeurs par défaut
            $validated['club_id'] = $club->id;
            // S'assurer que is_active est un booléen (true par défaut si non fourni)
            $validated['is_active'] = isset($validated['is_active']) ? filter_var($validated['is_active'], FILTER_VALIDATE_BOOLEAN) : true;
            $validated['max_slots'] = $validated['max_slots'] ?? 1;

            // 🔒 VÉRIFICATION DES CHEVAUCHEMENTS : Vérifier qu'aucun créneau existant ne chevauche avec le nouveau créneau
            // Un créneau chevauche si : (nouveau_start < existant_end) && (nouveau_end > existant_start)
            // Les colonnes start_time et end_time sont de type TIME, donc on peut comparer directement
            $existingSlot = ClubOpenSlot::where('club_id', $club->id)
                ->where('day_of_week', $validated['day_of_week'])
                ->where(function ($query) use ($validated) {
                    // Vérifier le chevauchement : nouveau créneau commence avant que l'existant se termine
                    // ET nouveau créneau se termine après que l'existant commence
                    $query->where('start_time', '<', $validated['end_time'])
                          ->where('end_time', '>', $validated['start_time']);
                })
                ->first();

            if ($existingSlot) {
                Log::warning('ClubOpenSlotController::store - Chevauchement détecté', [
                    'club_id' => $club->id,
                    'day_of_week' => $validated['day_of_week'],
                    'new_start_time' => $validated['start_time'],
                    'new_end_time' => $validated['end_time'],
                    'existing_slot_id' => $existingSlot->id,
                    'existing_start_time' => $existingSlot->start_time,
                    'existing_end_time' => $existingSlot->end_time
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Un créneau existe déjà pour ce jour et cette plage horaire. Les créneaux ne peuvent pas se chevaucher.',
                    'conflict' => [
                        'existing_slot_id' => $existingSlot->id,
                        'existing_start_time' => $existingSlot->start_time,
                        'existing_end_time' => $existingSlot->end_time
                    ]
                ], 422);
            }

            Log::info('ClubOpenSlotController::store - Création créneau', [
                'club_id' => $club->id,
                'validated' => $validated,
                'is_active' => $validated['is_active']
            ]);

            $slot = ClubOpenSlot::create($validated);

            // ✨ AUTO-ASSOCIATION : Associer automatiquement les types de cours correspondant à la discipline
            if ($slot->discipline_id) {
                // Récupérer tous les types de cours actifs pour cette discipline
                $courseTypeIds = CourseType::where('discipline_id', $slot->discipline_id)
                    ->where('is_active', true)
                    ->pluck('id')
                    ->toArray();
                
                // ⚠️ WORKAROUND : Si aucun type trouvé via discipline_id, chercher par nom exact
                if (empty($courseTypeIds)) {
                    $discipline = \App\Models\Discipline::find($slot->discipline_id);
                    if ($discipline) {
                        $courseTypeByName = CourseType::where('name', $discipline->name)
                            ->where('is_active', true)
                            ->first();
                        
                        if ($courseTypeByName) {
                            $courseTypeIds = [$courseTypeByName->id];
                            
                            Log::info('ClubOpenSlotController::store - Type de cours trouvé par nom', [
                                'slot_id' => $slot->id,
                                'discipline_id' => $slot->discipline_id,
                                'discipline_name' => $discipline->name,
                                'course_type_id' => $courseTypeByName->id
                            ]);
                        }
                    }
                }
                
                if (!empty($courseTypeIds)) {
                    $slot->courseTypes()->sync($courseTypeIds);
                    
                    Log::info('ClubOpenSlotController::store - Types de cours auto-associés', [
                        'slot_id' => $slot->id,
                        'discipline_id' => $slot->discipline_id,
                        'course_type_ids' => $courseTypeIds,
                        'count' => count($courseTypeIds)
                    ]);
                } else {
                    Log::warning('ClubOpenSlotController::store - Aucun type de cours trouvé pour la discipline', [
                        'slot_id' => $slot->id,
                        'discipline_id' => $slot->discipline_id
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'data' => $slot->load(['courseTypes', 'discipline']),
                'message' => 'Créneau créé avec succès'
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la création du créneau:', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du créneau'
            ], 500);
        }
    }

    /**
     * Mettre à jour un créneau
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Vérifier les permissions
            if ($user->role !== 'club' && $user->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé'
                ], 403);
            }

            $slot = ClubOpenSlot::find($id);
            
            if (!$slot) {
                return response()->json([
                    'success' => false,
                    'message' => 'Créneau non trouvé'
                ], 404);
            }

            // Si c'est un club, vérifier qu'il possède ce créneau
            if ($user->role === 'club') {
                $club = $user->getFirstClub();
                if (!$club || $slot->club_id !== $club->id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Accès non autorisé à ce créneau'
                    ], 403);
                }
            }

            // Validation
            $validated = $request->validate([
                'day_of_week' => 'sometimes|integer|between:0,6',
                'start_time' => 'sometimes|date_format:H:i',
                'end_time' => 'sometimes|date_format:H:i|after:start_time',
                'discipline_id' => 'nullable|exists:disciplines,id',
                'max_capacity' => 'sometimes|integer|min:1|max:50',
                'max_slots' => 'sometimes|integer|min:1|max:100',
                'duration' => 'sometimes|integer|min:15',
                'price' => 'sometimes|numeric|min:0',
                'is_active' => 'sometimes|boolean'
            ]);

            // S'assurer que is_active est bien traité
            // Vérifier si is_active est présent dans la request (même si false)
            // has() retourne false si la valeur est false, donc on utilise array_key_exists
            $requestData = $request->all();
            if (array_key_exists('is_active', $requestData)) {
                $validated['is_active'] = filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN);
            } else {
                // Si is_active n'est pas dans la request, ne pas le modifier
                unset($validated['is_active']);
            }

            // 🔒 VÉRIFICATION DES CHEVAUCHEMENTS : Si le jour ou les horaires sont modifiés, vérifier les chevauchements
            $dayOfWeekChanged = isset($validated['day_of_week']) && $validated['day_of_week'] != $slot->day_of_week;
            $startTimeChanged = isset($validated['start_time']) && $validated['start_time'] != $slot->start_time;
            $endTimeChanged = isset($validated['end_time']) && $validated['end_time'] != $slot->end_time;

            if ($dayOfWeekChanged || $startTimeChanged || $endTimeChanged) {
                // Utiliser les nouvelles valeurs ou les valeurs actuelles
                $checkDayOfWeek = $validated['day_of_week'] ?? $slot->day_of_week;
                $checkStartTime = $validated['start_time'] ?? $slot->start_time;
                $checkEndTime = $validated['end_time'] ?? $slot->end_time;

                $existingSlot = ClubOpenSlot::where('club_id', $slot->club_id)
                    ->where('day_of_week', $checkDayOfWeek)
                    ->where('id', '!=', $slot->id) // Exclure le créneau actuel
                    ->where(function ($query) use ($checkStartTime, $checkEndTime) {
                        // Vérifier le chevauchement : nouveau créneau commence avant que l'existant se termine
                        // ET nouveau créneau se termine après que l'existant commence
                        $query->where('start_time', '<', $checkEndTime)
                              ->where('end_time', '>', $checkStartTime);
                    })
                    ->first();

                if ($existingSlot) {
                    Log::warning('ClubOpenSlotController::update - Chevauchement détecté lors de la mise à jour', [
                        'slot_id' => $slot->id,
                        'club_id' => $slot->club_id,
                        'day_of_week' => $checkDayOfWeek,
                        'new_start_time' => $checkStartTime,
                        'new_end_time' => $checkEndTime,
                        'existing_slot_id' => $existingSlot->id,
                        'existing_start_time' => $existingSlot->start_time,
                        'existing_end_time' => $existingSlot->end_time
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => 'Un autre créneau existe déjà pour ce jour et cette plage horaire. Les créneaux ne peuvent pas se chevaucher.',
                        'conflict' => [
                            'existing_slot_id' => $existingSlot->id,
                            'existing_start_time' => $existingSlot->start_time,
                            'existing_end_time' => $existingSlot->end_time
                        ]
                    ], 422);
                }
            }

            Log::info('ClubOpenSlotController::update - Mise à jour créneau', [
                'slot_id' => $id,
                'validated' => $validated,
                'is_active_in_request' => array_key_exists('is_active', $requestData),
                'is_active_value' => $request->input('is_active', 'non fourni'),
                'is_active_final' => $validated['is_active'] ?? 'non modifié',
                'request_all' => $requestData
            ]);

            $slot->update($validated);
            
            // Recharger pour avoir les données à jour
            $slot->refresh();

            return response()->json([
                'success' => true,
                'data' => $slot->load(['courseTypes', 'discipline']),
                'message' => 'Créneau mis à jour avec succès'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la mise à jour du créneau:', [
                'error' => $e->getMessage(),
                'slot_id' => $id
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du créneau'
            ], 500);
        }
    }

    /**
     * Supprimer un créneau
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Vérifier les permissions
            if ($user->role !== 'club' && $user->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé'
                ], 403);
            }

            $slot = ClubOpenSlot::find($id);
            
            if (!$slot) {
                return response()->json([
                    'success' => false,
                    'message' => 'Créneau non trouvé'
                ], 404);
            }

            // Si c'est un club, vérifier qu'il possède ce créneau
            if ($user->role === 'club') {
                $club = $user->getFirstClub();
                if (!$club || $slot->club_id !== $club->id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Accès non autorisé à ce créneau'
                    ], 403);
                }
            }

            $slot->delete();

            return response()->json([
                'success' => true,
                'message' => 'Créneau supprimé avec succès'
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la suppression du créneau:', [
                'error' => $e->getMessage(),
                'slot_id' => $id
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression du créneau'
            ], 500);
        }
    }

    /**
     * Calculer le PGCD de deux nombres
     */
    private function gcd(int $a, int $b): int
    {
        return $b === 0 ? $a : $this->gcd($b, $a % $b);
    }

    /**
     * Calculer le PGCD d'un tableau de nombres
     */
    private function gcdArray(array $numbers): int
    {
        if (empty($numbers)) return 30;
        if (count($numbers) === 1) return $numbers[0];

        $result = $numbers[0];
        for ($i = 1; $i < count($numbers); $i++) {
            $result = $this->gcd($result, $numbers[$i]);
        }

        return $result;
    }

    /**
     * Calculer le pas de temps basé sur les durées des types de cours
     */
    private function calculateTimeStep(array $durations): int
    {
        if (empty($durations)) {
            return 30; // Valeur par défaut
        }

        $step = $this->gcdArray($durations);

        // Assurer que le pas est raisonnable (entre 5 et 60 minutes)
        return max(5, min($step, 60));
    }
}
