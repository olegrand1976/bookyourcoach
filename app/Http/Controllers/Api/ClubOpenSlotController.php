<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClubOpenSlot;
use App\Models\Club;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ClubOpenSlotController extends Controller
{
    /**
     * Récupérer tous les créneaux ouverts d'un club
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Récupérer le club de l'utilisateur
            $club = $user->getFirstClub();
            
            if (!$club) {
                return response()->json([
                    'success' => false,
                    'message' => 'Club non trouvé'
                ], 404);
            }

            $slots = ClubOpenSlot::where('club_id', $club->id)
                ->where('is_active', true)
                ->orderBy('day_of_week')
                ->orderBy('start_time')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $slots
            ]);
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la récupération des créneaux:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des créneaux'
            ], 500);
        }
    }

    /**
     * Créer un nouveau créneau ouvert
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Récupérer le club de l'utilisateur
            $club = $user->getFirstClub();
            
            if (!$club) {
                return response()->json([
                    'success' => false,
                    'message' => 'Club non trouvé'
                ], 404);
            }

            $validated = $request->validate([
                'day_of_week' => 'required|integer|min:0|max:6',
                'start_time' => 'required|date_format:H:i',
                'end_time' => 'required|date_format:H:i|after:start_time',
                'discipline_id' => 'nullable|exists:disciplines,id',
                'max_capacity' => 'required|integer|min:1|max:50',
                'duration' => 'required|integer|min:15|max:240',
                'price' => 'required|numeric|min:0'
            ]);

            $validated['club_id'] = $club->id;
            $validated['is_active'] = true;

            $slot = ClubOpenSlot::create($validated);

            return response()->json([
                'success' => true,
                'data' => $slot,
                'message' => 'Créneau créé avec succès'
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la création du créneau:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du créneau'
            ], 500);
        }
    }

    /**
     * Mettre à jour un créneau ouvert
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Récupérer le club de l'utilisateur
            $club = $user->getFirstClub();
            
            if (!$club) {
                return response()->json([
                    'success' => false,
                    'message' => 'Club non trouvé'
                ], 404);
            }

            $slot = ClubOpenSlot::where('club_id', $club->id)
                ->where('id', $id)
                ->firstOrFail();

            $validated = $request->validate([
                'day_of_week' => 'sometimes|integer|min:0|max:6',
                'start_time' => 'sometimes|date_format:H:i',
                'end_time' => 'sometimes|date_format:H:i|after:start_time',
                'discipline_id' => 'nullable|exists:disciplines,id',
                'max_capacity' => 'sometimes|integer|min:1|max:50',
                'duration' => 'sometimes|integer|min:15|max:240',
                'price' => 'sometimes|numeric|min:0',
                'is_active' => 'sometimes|boolean'
            ]);

            $slot->update($validated);

            return response()->json([
                'success' => true,
                'data' => $slot->fresh(),
                'message' => 'Créneau mis à jour avec succès'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Créneau non trouvé'
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la mise à jour du créneau:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du créneau'
            ], 500);
        }
    }

    /**
     * Supprimer un créneau ouvert
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Récupérer le club de l'utilisateur
            $club = $user->getFirstClub();
            
            if (!$club) {
                return response()->json([
                    'success' => false,
                    'message' => 'Club non trouvé'
                ], 404);
            }

            $slot = ClubOpenSlot::where('club_id', $club->id)
                ->where('id', $id)
                ->firstOrFail();

            $slot->delete();

            return response()->json([
                'success' => true,
                'message' => 'Créneau supprimé avec succès'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Créneau non trouvé'
            ], 404);
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la suppression du créneau:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression du créneau'
            ], 500);
        }
    }
}
