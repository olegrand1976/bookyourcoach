<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Discipline;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * @OA\Tag(
 *     name="Disciplines",
 *     description="Gestion des disciplines"
 * )
 */
class DisciplineController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/disciplines",
     *     summary="Liste des disciplines",
     *     description="Récupère la liste complète des disciplines disponibles",
     *     operationId="getDisciplines",
     *     tags={"Disciplines"},
     *     @OA\Response(
     *         response=200,
     *         description="Liste des disciplines récupérée avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Discipline")
     *             )
     *         )
     *     )
     * )
     */
    public function index(): JsonResponse
    {
        try {
            $disciplines = Discipline::where('is_active', true)
                ->orderBy('activity_type_id')
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $disciplines
            ]);
        } catch (\Exception $e) {
            \Log::error('DisciplineController - Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des disciplines',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/disciplines/{id}",
     *     summary="Détails d'une discipline",
     *     description="Récupère les détails d'une discipline spécifique",
     *     operationId="getDiscipline",
     *     tags={"Disciplines"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID de la discipline",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Détails de la discipline récupérés avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/Discipline")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Discipline non trouvée"
     *     )
     * )
     */
    public function show(string $id): JsonResponse
    {
        try {
            $discipline = Discipline::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $discipline
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Discipline non trouvée'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de la discipline',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get disciplines by activity type
     */
    public function byActivityType(string $activityTypeId): JsonResponse
    {
        try {
            $disciplines = Discipline::where('activity_type_id', $activityTypeId)
                ->where('is_active', true)
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $disciplines
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des disciplines',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

