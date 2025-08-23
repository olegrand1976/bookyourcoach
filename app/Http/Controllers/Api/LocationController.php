<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * @OA\Tag(
 *     name="Locations",
 *     description="Gestion des lieux de cours"
 * )
 */
class LocationController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/locations",
     *     summary="Liste des lieux",
     *     description="Récupère la liste complète des lieux de cours disponibles",
     *     operationId="getLocations",
     *     tags={"Locations"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Liste des lieux récupérée avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Location")
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
            $locations = Location::all();

            return response()->json([
                'success' => true,
                'data' => $locations
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des lieux',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/locations",
     *     summary="Créer un nouveau lieu",
     *     description="Crée un nouveau lieu de cours",
     *     operationId="createLocation",
     *     tags={"Locations"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "address", "city"},
     *             @OA\Property(property="name", type="string", example="Centre Équestre de Paris"),
     *             @OA\Property(property="address", type="string", example="123 Rue de la Paix"),
     *             @OA\Property(property="city", type="string", example="Paris"),
     *             @OA\Property(property="postal_code", type="string", example="75001"),
     *             @OA\Property(property="country", type="string", example="France"),
     *             @OA\Property(property="latitude", type="number", format="float", example=48.8566),
     *             @OA\Property(property="longitude", type="number", format="float", example=2.3522),
     *             @OA\Property(property="description", type="string", example="Centre équestre moderne avec manèges couverts"),
     *             @OA\Property(property="facilities", type="array", @OA\Items(type="string"), example={"manège couvert", "carrière", "parking"})
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Lieu créé avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/Location"),
     *             @OA\Property(property="message", type="string", example="Lieu créé avec succès")
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
                'message' => 'Accès non autorisé. Seuls les administrateurs peuvent créer des lieux.'
            ], 403);
        }

        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'address' => 'required|string|max:500',
                'city' => 'required|string|max:100',
                'postal_code' => 'required|string|max:20',
                'country' => 'required|string|max:100',
                'latitude' => 'nullable|numeric|between:-90,90',
                'longitude' => 'nullable|numeric|between:-180,180',
                'description' => 'nullable|string',
                'facilities' => 'nullable|array',
                'facilities.*' => 'string|max:100'
            ]);

            $location = Location::create($validated);

            return response()->json([
                'success' => true,
                'data' => $location,
                'message' => 'Lieu créé avec succès'
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
                'message' => 'Erreur lors de la création du lieu',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/locations/{id}",
     *     summary="Détails d'un lieu",
     *     description="Récupère les détails d'un lieu spécifique",
     *     operationId="getLocation",
     *     tags={"Locations"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID du lieu",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Détails du lieu récupérés avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/Location")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Lieu non trouvé"
     *     )
     * )
     */
    public function show(string $id): JsonResponse
    {
        try {
            $location = Location::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $location
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lieu non trouvé'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du lieu',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/locations/{id}",
     *     summary="Mettre à jour un lieu",
     *     description="Met à jour les informations d'un lieu",
     *     operationId="updateLocation",
     *     tags={"Locations"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID du lieu",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Centre Équestre de Paris Rénové"),
     *             @OA\Property(property="address", type="string", example="123 Rue de la Paix"),
     *             @OA\Property(property="city", type="string", example="Paris"),
     *             @OA\Property(property="postal_code", type="string", example="75001"),
     *             @OA\Property(property="country", type="string", example="France"),
     *             @OA\Property(property="latitude", type="number", format="float", example=48.8566),
     *             @OA\Property(property="longitude", type="number", format="float", example=2.3522),
     *             @OA\Property(property="description", type="string", example="Centre équestre moderne avec nouveaux équipements"),
     *             @OA\Property(property="facilities", type="array", @OA\Items(type="string"), example={"manège couvert", "carrière", "parking", "douches"})
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lieu mis à jour avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/Location"),
     *             @OA\Property(property="message", type="string", example="Lieu mis à jour avec succès")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Lieu non trouvé"
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
                'message' => 'Accès non autorisé. Seuls les administrateurs peuvent modifier des lieux.'
            ], 403);
        }

        try {
            $location = Location::findOrFail($id);

            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'address' => 'sometimes|required|string|max:500',
                'city' => 'sometimes|required|string|max:100',
                'postal_code' => 'sometimes|required|string|max:20',
                'country' => 'sometimes|required|string|max:100',
                'latitude' => 'nullable|numeric|between:-90,90',
                'longitude' => 'nullable|numeric|between:-180,180',
                'description' => 'nullable|string',
                'facilities' => 'nullable|array',
                'facilities.*' => 'string|max:100'
            ]);

            $location->update($validated);

            return response()->json([
                'success' => true,
                'data' => $location->fresh(),
                'message' => 'Lieu mis à jour avec succès'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lieu non trouvé'
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
                'message' => 'Erreur lors de la mise à jour du lieu',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/locations/{id}",
     *     summary="Supprimer un lieu",
     *     description="Supprime un lieu",
     *     operationId="deleteLocation",
     *     tags={"Locations"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID du lieu",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lieu supprimé avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Lieu supprimé avec succès")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Lieu non trouvé"
     *     )
     * )
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        // Vérifier que l'utilisateur est admin
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé. Seuls les administrateurs peuvent supprimer des lieux.'
            ], 403);
        }

        try {
            $location = Location::findOrFail($id);
            $location->delete();

            return response()->json(null, 204);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lieu non trouvé'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression du lieu',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
