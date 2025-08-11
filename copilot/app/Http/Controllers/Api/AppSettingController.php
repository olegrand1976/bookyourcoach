<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AppSettingRequest;
use App\Http\Resources\AppSettingResource;
use App\Models\AppSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="App Settings",
 *     description="Gestion des paramètres d'application et de rebranding"
 * )
 */
class AppSettingController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/app-settings",
     *     operationId="getAppSettings",
     *     tags={"App Settings"},
     *     summary="Récupérer les paramètres d'application actifs",
     *     description="Récupère les paramètres d'application actuellement actifs pour le rebranding",
     *     @OA\Response(
     *         response=200,
     *         description="Paramètres d'application récupérés avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/AppSettingResource")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Aucun paramètre actif trouvé",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Aucun paramètre d'application actif trouvé")
     *         )
     *     )
     * )
     */
    public function index(): JsonResponse
    {
        $settings = AppSetting::getActiveSettings();

        if (!$settings) {
            $settings = AppSetting::getOrCreateDefault();
        }

        return response()->json([
            'success' => true,
            'data' => new AppSettingResource($settings)
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/app-settings/{id}",
     *     operationId="getAppSetting",
     *     tags={"App Settings"},
     *     summary="Récupérer un paramètre d'application spécifique",
     *     description="Récupère les détails d'un paramètre d'application spécifique (admin uniquement)",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID du paramètre d'application",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Paramètre d'application récupéré avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/AppSettingResource")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Accès non autorisé",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Accès non autorisé")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Paramètre non trouvé",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Paramètre d'application non trouvé")
     *         )
     *     )
     * )
     */
    public function show(Request $request, AppSetting $appSetting): JsonResponse
    {
        // Vérifier les permissions admin
        if (!$request->user() || $request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => new AppSettingResource($appSetting)
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/app-settings",
     *     operationId="createAppSetting",
     *     tags={"App Settings"},
     *     summary="Créer de nouveaux paramètres d'application",
     *     description="Crée de nouveaux paramètres d'application (admin uniquement)",
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/AppSetting")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Paramètres d'application créés avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Paramètres d'application créés avec succès"),
     *             @OA\Property(property="data", ref="#/components/schemas/AppSettingResource")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Erreur de validation"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function store(AppSettingRequest $request): JsonResponse
    {
        // Désactiver les anciens paramètres si on en crée de nouveaux actifs
        if ($request->input('is_active', true)) {
            AppSetting::where('is_active', true)->update(['is_active' => false]);
        }

        $appSetting = AppSetting::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Paramètres d\'application créés avec succès',
            'data' => new AppSettingResource($appSetting)
        ], 201);
    }

    /**
     * @OA\Put(
     *     path="/api/app-settings/{id}",
     *     operationId="updateAppSetting",
     *     tags={"App Settings"},
     *     summary="Mettre à jour les paramètres d'application",
     *     description="Met à jour les paramètres d'application existants (admin uniquement)",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID du paramètre d'application",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/AppSetting")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Paramètres d'application mis à jour avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Paramètres d'application mis à jour avec succès"),
     *             @OA\Property(property="data", ref="#/components/schemas/AppSettingResource")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Paramètre non trouvé",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Paramètre d'application non trouvé")
     *         )
     *     )
     * )
     */
    public function update(AppSettingRequest $request, AppSetting $appSetting): JsonResponse
    {
        // Si on active ces paramètres, désactiver les autres
        if ($request->input('is_active', $appSetting->is_active) && !$appSetting->is_active) {
            AppSetting::where('is_active', true)->where('id', '!=', $appSetting->id)->update(['is_active' => false]);
        }

        $appSetting->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Paramètres d\'application mis à jour avec succès',
            'data' => new AppSettingResource($appSetting->fresh())
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/app-settings/{id}",
     *     operationId="deleteAppSetting",
     *     tags={"App Settings"},
     *     summary="Supprimer des paramètres d'application",
     *     description="Supprime des paramètres d'application (admin uniquement)",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID du paramètre d'application",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Paramètres d'application supprimés avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Paramètres d'application supprimés avec succès")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Paramètre non trouvé",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Paramètre d'application non trouvé")
     *         )
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Impossible de supprimer le paramètre actif",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Impossible de supprimer les paramètres d'application actifs")
     *         )
     *     )
     * )
     */
    public function destroy(Request $request, AppSetting $appSetting): JsonResponse
    {
        // Vérifier les permissions admin
        if (!$request->user() || $request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé'
            ], 403);
        }

        // Empêcher la suppression des paramètres actifs
        if ($appSetting->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de supprimer les paramètres d\'application actifs'
            ], 409);
        }

        $appSetting->delete();

        return response()->json([
            'success' => true,
            'message' => 'Paramètres d\'application supprimés avec succès'
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/app-settings/{id}/activate",
     *     operationId="activateAppSetting",
     *     tags={"App Settings"},
     *     summary="Activer des paramètres d'application",
     *     description="Active des paramètres d'application spécifiques et désactive les autres (admin uniquement)",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID du paramètre d'application",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Paramètres d'application activés avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Paramètres d'application activés avec succès"),
     *             @OA\Property(property="data", ref="#/components/schemas/AppSettingResource")
     *         )
     *     )
     * )
     */
    public function activate(Request $request, AppSetting $appSetting): JsonResponse
    {
        // Vérifier les permissions admin
        if (!$request->user() || $request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé'
            ], 403);
        }

        // Désactiver tous les autres paramètres
        AppSetting::where('id', '!=', $appSetting->id)->update(['is_active' => false]);

        // Activer celui-ci
        $appSetting->update(['is_active' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Paramètres d\'application activés avec succès',
            'data' => new AppSettingResource($appSetting->fresh())
        ]);
    }
}
