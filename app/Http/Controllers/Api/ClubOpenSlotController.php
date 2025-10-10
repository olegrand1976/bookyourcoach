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

            // Vérifier que les types de cours correspondent à la discipline du créneau (si définie)
            if ($slot->discipline_id) {
                $courseTypes = CourseType::whereIn('id', $validated['course_type_ids'])->get();
                
                foreach ($courseTypes as $courseType) {
                    // Le type de cours doit soit être générique (pas de discipline), soit correspondre à la discipline du créneau
                    if ($courseType->discipline_id && $courseType->discipline_id != $slot->discipline_id) {
                        return response()->json([
                            'success' => false,
                            'message' => "Le type de cours '{$courseType->name}' ne correspond pas à la discipline du créneau"
                        ], 422);
                    }
                }
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

            // Filtres optionnels
            if ($request->has('day_of_week')) {
                $query->where('day_of_week', $request->day_of_week);
            }

            if ($request->has('is_active')) {
                $query->where('is_active', $request->is_active);
            }

            $slots = $query->orderBy('day_of_week')->orderBy('start_time')->get();

            return response()->json([
                'success' => true,
                'data' => $slots
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
                'duration' => 'required|integer|min:15',
                'price' => 'required|numeric|min:0',
                'is_active' => 'boolean'
            ]);

            // Ajouter le club_id
            $validated['club_id'] = $club->id;
            $validated['is_active'] = $validated['is_active'] ?? true;

            $slot = ClubOpenSlot::create($validated);

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
                'duration' => 'sometimes|integer|min:15',
                'price' => 'sometimes|numeric|min:0',
                'is_active' => 'sometimes|boolean'
            ]);

            $slot->update($validated);

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
}
