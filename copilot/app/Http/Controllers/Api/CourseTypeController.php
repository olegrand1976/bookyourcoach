<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CourseType;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

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
    public function index(): JsonResponse
    {
        try {
            $courseTypes = CourseType::all();

            return response()->json([
                'success' => true,
                'data' => $courseTypes
            ]);
        } catch (\Exception $e) {
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
