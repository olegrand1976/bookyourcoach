<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CourseType;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Tag(
 *     name="Course Types",
 *     description="Gestion des types de cours"
 * )
 */
class CourseTypeController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/course-types",
     *     summary="Liste des types de cours",
     *     description="Récupère la liste complète des types de cours disponibles",
     *     operationId="getCourseTypes",
     *     tags={"Course Types"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Liste des types de cours récupérée avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/CourseType")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non autorisé"
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Si l'utilisateur est un club manager, retourner UNIQUEMENT les types de cours du club
            if ($user && $user->role === 'club') {
                $club = $user->getFirstClub();
                
                if (!$club) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Club non trouvé'
                    ], 404);
                }
                
                // ✅ CORRECTION : Utiliser le champ JSON disciplines au lieu de la relation
                // car les données sont stockées dans clubs.disciplines (JSON) et non dans club_disciplines
                $clubDisciplineIds = $club->disciplines ?? [];
                
                // Si le club n'a pas de disciplines, retourner les types génériques uniquement
                if (empty($clubDisciplineIds)) {
                    $courseTypes = CourseType::where('is_active', true)
                        ->whereNull('discipline_id')  // Uniquement génériques
                        ->orderBy('name')
                        ->get();
                    
                    return response()->json([
                        'success' => true,
                        'data' => $courseTypes,
                        'meta' => [
                            'total' => $courseTypes->count(),
                            'club_id' => $club->id,
                            'club_disciplines' => [],
                            'message' => 'Aucune discipline configurée pour ce club'
                        ]
                    ]);
                }
                
                // Nettoyer les IDs : s'assurer qu'ils sont tous des entiers valides
                $validDisciplineIds = array_filter($clubDisciplineIds, function($id) {
                    return is_numeric($id) && $id > 0;
                });
                $validDisciplineIds = array_values(array_unique($validDisciplineIds));
                
                // Vérifier d'abord que les disciplines existent vraiment
                $existingDisciplines = \App\Models\Discipline::whereIn('id', $validDisciplineIds)->pluck('id')->toArray();
                
                // Filtrer pour ne garder que les IDs de disciplines qui existent réellement
                $validExistingDisciplineIds = array_intersect($validDisciplineIds, $existingDisciplines);
                
                if (empty($validExistingDisciplineIds)) {
                    \Log::warning('Aucune discipline valide trouvée pour le club', [
                        'club_id' => $club->id,
                        'requested_ids' => $validDisciplineIds,
                        'existing_ids' => $existingDisciplines
                    ]);
                    
                    return response()->json([
                        'success' => true,
                        'data' => [],
                        'meta' => [
                            'total' => 0,
                            'club_id' => $club->id,
                            'club_disciplines' => $validDisciplineIds,
                            'existing_disciplines' => $existingDisciplines,
                            'message' => 'Les disciplines sélectionnées n\'existent pas. Veuillez mettre à jour votre profil.'
                        ]
                    ]);
                }
                
                // Vérifier si on doit filtrer par les types de cours réellement utilisés dans les créneaux
                $onlyUsedInSlots = $request->has('only_used_in_slots') && $request->boolean('only_used_in_slots');
                
                if ($onlyUsedInSlots) {
                    // 🔒 FILTRAGE STRICT : Récupérer UNIQUEMENT les types de cours réellement UTILISÉS dans les cours créés (lessons)
                    // Pas seulement assignés aux créneaux, mais réellement utilisés dans les cours
                    $courseTypeIdsUsedInLessons = \DB::table('lessons')
                        ->where('club_id', $club->id)
                        ->whereNotNull('course_type_id')
                        ->distinct()
                        ->pluck('course_type_id')
                        ->toArray();
                    
                    \Log::info('CourseTypeController - Filtrage par cours créés (lessons)', [
                        'club_id' => $club->id,
                        'course_type_ids_used_in_lessons' => $courseTypeIdsUsedInLessons,
                        'count' => count($courseTypeIdsUsedInLessons)
                    ]);
                    
                    if (empty($courseTypeIdsUsedInLessons)) {
                        // Aucun type de cours utilisé dans les cours créés
                        return response()->json([
                            'success' => true,
                            'data' => [],
                            'meta' => [
                                'total' => 0,
                                'club_id' => $club->id,
                                'club_disciplines' => $validExistingDisciplineIds,
                                'message' => 'Aucun type de cours utilisé dans les cours créés du club'
                            ]
                        ]);
                    }
                    
                    // Vérifier les détails pour debug
                    $details = \DB::table('course_types')
                        ->whereIn('id', $courseTypeIdsUsedInLessons)
                        ->select('id', 'name', 'discipline_id')
                        ->get();
                    
                    \Log::info('CourseTypeController - Types de cours utilisés dans les cours', [
                        'club_id' => $club->id,
                        'course_type_ids' => $courseTypeIdsUsedInLessons,
                        'course_types_details' => $details->map(fn($d) => [
                            'id' => $d->id,
                            'name' => $d->name,
                            'discipline_id' => $d->discipline_id
                        ])->toArray(),
                        'count' => count($courseTypeIdsUsedInLessons)
                    ]);
                    
                    // Filtrer par discipline ET par types de cours réellement utilisés dans les cours
                    $courseTypes = CourseType::whereIn('discipline_id', $validExistingDisciplineIds)
                        ->whereIn('id', $courseTypeIdsUsedInLessons)
                        ->where('is_active', true)
                        ->with('discipline:id,name,activity_type_id', 'discipline.activityType:id,name')
                        ->orderBy('discipline_id')
                        ->orderBy('name')
                        ->get();
                    
                    \Log::info('CourseTypeController - Types de cours finaux après filtrage', [
                        'club_id' => $club->id,
                        'total_types' => $courseTypes->count(),
                        'types' => $courseTypes->map(fn($ct) => [
                            'id' => $ct->id,
                            'name' => $ct->name,
                            'discipline_id' => $ct->discipline_id,
                            'discipline_name' => $ct->discipline?->name
                        ])->toArray()
                    ]);
                } else {
                    // Récupérer UNIQUEMENT les types de cours liés aux disciplines du club
                    // On exclut les types génériques si le club a des disciplines spécifiques
                    // pour éviter la confusion dans la sélection des types de cours
                    $courseTypes = CourseType::whereIn('discipline_id', $validExistingDisciplineIds)
                        ->where('is_active', true)
                        ->with('discipline:id,name,activity_type_id', 'discipline.activityType:id,name')
                        ->orderBy('discipline_id')
                        ->orderBy('name')
                        ->get();
                }
                
                // Si aucun type de cours n'existe pour ces disciplines, créer des types par défaut
                if ($courseTypes->isEmpty()) {
                    \Log::info('Aucun type de cours existant, création automatique', [
                        'club_id' => $club->id,
                        'discipline_ids' => $validExistingDisciplineIds
                    ]);
                    
                    $createdTypes = [];
                    foreach ($validExistingDisciplineIds as $disciplineId) {
                        $discipline = \App\Models\Discipline::find($disciplineId);
                        if ($discipline) {
                            // Créer un type de cours par défaut pour cette discipline
                            $courseType = CourseType::create([
                                'discipline_id' => $disciplineId,
                                'name' => $discipline->name . ' - Cours standard',
                                'description' => 'Cours standard de ' . $discipline->name,
                                'duration_minutes' => 60,
                                'price' => 25.00,
                                'is_individual' => false,
                                'max_participants' => 10,
                                'is_active' => true
                            ]);
                            $createdTypes[] = $courseType;
                        }
                    }
                    
                    // Recharger les types de cours
                    $courseTypes = CourseType::whereIn('discipline_id', $validExistingDisciplineIds)
                        ->where('is_active', true)
                        ->with('discipline:id,name,activity_type_id', 'discipline.activityType:id,name')
                        ->orderBy('discipline_id')
                        ->orderBy('name')
                        ->get();
                }
                
                \Log::info('CourseTypeController - Club courses filtered', [
                    'club_id' => $club->id,
                    'club_disciplines_raw' => $clubDisciplineIds,
                    'club_disciplines_valid' => $validDisciplineIds,
                    'club_disciplines_existing' => $validExistingDisciplineIds,
                    'total_types' => $courseTypes->count(),
                    'types_by_discipline' => $courseTypes->groupBy('discipline_id')->map(fn($group) => $group->count())->toArray(),
                    'sample_types' => $courseTypes->take(5)->map(fn($t) => [
                        'id' => $t->id,
                        'name' => $t->name,
                        'discipline_id' => $t->discipline_id,
                        'discipline_name' => $t->discipline?->name
                    ])->toArray()
                ]);
                
                // S'assurer que la relation discipline et activityType sont incluses dans la sérialisation JSON
                $courseTypesData = $courseTypes->map(function($courseType) {
                    $data = $courseType->toArray();
                    // Ajouter explicitement l'activité si disponible
                    if ($courseType->relationLoaded('discipline') && $courseType->discipline && $courseType->discipline->relationLoaded('activityType')) {
                        if ($courseType->discipline->activityType) {
                            $data['discipline']['activity'] = [
                                'id' => $courseType->discipline->activityType->id,
                                'name' => $courseType->discipline->activityType->name
                            ];
                        }
                    }
                    return $data;
                });
                
                return response()->json([
                    'success' => true,
                    'data' => $courseTypesData,
                    'meta' => [
                        'total' => $courseTypes->count(),
                        'club_id' => $club->id,
                        'club_disciplines' => $validExistingDisciplineIds,
                        'requested_disciplines' => $validDisciplineIds,
                        'invalid_disciplines' => array_diff($validDisciplineIds, $validExistingDisciplineIds)
                    ]
                ]);
            }
            
            // Pour les admins et autres rôles : retourner TOUS les types de cours
            $courseTypes = CourseType::orderBy('name')->get();

            return response()->json([
                'success' => true,
                'data' => $courseTypes
            ]);
        } catch (\Exception $e) {
            \Log::error('CourseTypeController - Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des types de cours',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/course-types",
     *     summary="Créer un nouveau type de cours",
     *     description="Crée un nouveau type de cours",
     *     operationId="createCourseType",
     *     tags={"Course Types"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="Dressage"),
     *             @OA\Property(property="description", type="string", example="Cours de dressage classique"),
     *             @OA\Property(property="duration", type="integer", example=60),
     *             @OA\Property(property="price", type="number", format="float", example=45.00)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Type de cours créé avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/CourseType"),
     *             @OA\Property(property="message", type="string", example="Type de cours créé avec succès")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation"
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        // Vérifier que l'utilisateur est admin
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé. Seuls les administrateurs peuvent créer des types de cours.'
            ], 403);
        }

        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:course_types',
                'description' => 'nullable|string',
                'duration' => 'nullable|integer|min:15|max:180',
                'price' => 'nullable|numeric|min:0'
            ]);

            $courseType = CourseType::create($validated);

            return response()->json([
                'success' => true,
                'data' => $courseType,
                'message' => 'Type de cours créé avec succès'
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du type de cours',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/course-types/{id}",
     *     summary="Détails d'un type de cours",
     *     description="Récupère les détails d'un type de cours spécifique",
     *     operationId="getCourseType",
     *     tags={"Course Types"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID du type de cours",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Détails du type de cours récupérés avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/CourseType")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Type de cours non trouvé"
     *     )
     * )
     */
    public function show(string $id): JsonResponse
    {
        try {
            $courseType = CourseType::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $courseType
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Type de cours non trouvé'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du type de cours',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/course-types/{id}",
     *     summary="Mettre à jour un type de cours",
     *     description="Met à jour les informations d'un type de cours",
     *     operationId="updateCourseType",
     *     tags={"Course Types"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID du type de cours",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Dressage Avancé"),
     *             @OA\Property(property="description", type="string", example="Cours de dressage niveau avancé"),
     *             @OA\Property(property="duration", type="integer", example=90),
     *             @OA\Property(property="price", type="number", format="float", example=65.00)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Type de cours mis à jour avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/CourseType"),
     *             @OA\Property(property="message", type="string", example="Type de cours mis à jour avec succès")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Type de cours non trouvé"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation"
     *     )
     * )
     */
    public function update(Request $request, string $id): JsonResponse
    {
        // Vérifier que l'utilisateur est admin
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé. Seuls les administrateurs peuvent modifier des types de cours.'
            ], 403);
        }

        try {
            $courseType = CourseType::findOrFail($id);

            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255|unique:course_types,name,' . $id,
                'description' => 'nullable|string',
                'duration' => 'nullable|integer|min:15|max:180',
                'price' => 'nullable|numeric|min:0'
            ]);

            $courseType->update($validated);

            return response()->json([
                'success' => true,
                'data' => $courseType->fresh(),
                'message' => 'Type de cours mis à jour avec succès'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Type de cours non trouvé'
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du type de cours',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/course-types/{id}",
     *     summary="Supprimer un type de cours",
     *     description="Supprime un type de cours",
     *     operationId="deleteCourseType",
     *     tags={"Course Types"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID du type de cours",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Type de cours supprimé avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Type de cours supprimé avec succès")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Type de cours non trouvé"
     *     )
     * )
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        // Vérifier que l'utilisateur est admin
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé. Seuls les administrateurs peuvent supprimer des types de cours.'
            ], 403);
        }

        try {
            $courseType = CourseType::findOrFail($id);
            $courseType->delete();

            return response()->json(null, 204);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Type de cours non trouvé'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression du type de cours',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
