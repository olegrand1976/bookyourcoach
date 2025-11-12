<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionRecurringSlot;
use App\Models\SubscriptionInstance;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RecurringSlotController extends Controller
{
    /**
     * Liste des créneaux récurrents pour un club
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if ($user->role !== 'club') {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé'
                ], 403);
            }

            $club = $user->getFirstClub();
            if (!$club) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun club associé'
                ], 404);
            }

            // Récupérer les créneaux récurrents via les subscription_instances du club
            $recurringSlots = SubscriptionRecurringSlot::whereHas('subscriptionInstance', function ($query) use ($club) {
                    $query->whereHas('subscription', function ($q) use ($club) {
                        $q->where('club_id', $club->id);
                    });
                })
                ->with([
                    'subscriptionInstance.subscription',
                    'subscriptionInstance.students.user',
                    'teacher.user',
                    'student.user',
                    'openSlot'
                ])
                ->orderBy('day_of_week')
                ->orderBy('start_time')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $recurringSlots
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des créneaux récurrents: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des créneaux récurrents',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Libérer manuellement un créneau récurrent
     * Utilisé quand on sait que l'abonnement va se terminer ou qu'on veut libérer le créneau
     */
    public function release(Request $request, int $id): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if ($user->role !== 'club') {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé'
                ], 403);
            }

            $club = $user->getFirstClub();
            if (!$club) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun club associé'
                ], 404);
            }

            $validated = $request->validate([
                'reason' => 'nullable|string|max:500'
            ]);

            $recurringSlot = SubscriptionRecurringSlot::whereHas('subscriptionInstance', function ($query) use ($club) {
                    $query->whereHas('subscription', function ($q) use ($club) {
                        $q->where('club_id', $club->id);
                    });
                })
                ->findOrFail($id);

            $recurringSlot->release($validated['reason'] ?? null);

            Log::info("🔓 Créneau récurrent libéré manuellement", [
                'recurring_slot_id' => $id,
                'subscription_instance_id' => $recurringSlot->subscription_instance_id,
                'club_id' => $club->id,
                'user_id' => $user->id,
                'reason' => $validated['reason'] ?? 'Non spécifiée'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Créneau libéré avec succès',
                'data' => $recurringSlot->fresh()
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Créneau récurrent non trouvé'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la libération du créneau récurrent: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la libération du créneau',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Réactiver un créneau récurrent annulé
     * Utilisé pour rétablir une réservation qui avait été libérée
     */
    public function reactivate(Request $request, int $id): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if ($user->role !== 'club') {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé'
                ], 403);
            }

            $club = $user->getFirstClub();
            if (!$club) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun club associé'
                ], 404);
            }

            $validated = $request->validate([
                'reason' => 'nullable|string|max:500'
            ]);

            $recurringSlot = SubscriptionRecurringSlot::whereHas('subscriptionInstance', function ($query) use ($club) {
                    $query->whereHas('subscription', function ($q) use ($club) {
                        $q->where('club_id', $club->id);
                    });
                })
                ->findOrFail($id);

            if ($recurringSlot->status !== 'cancelled') {
                return response()->json([
                    'success' => false,
                    'message' => 'Seuls les créneaux annulés peuvent être réactivés'
                ], 422);
            }

            $recurringSlot->reactivate($validated['reason'] ?? null);

            Log::info("🔄 Créneau récurrent réactivé manuellement", [
                'recurring_slot_id' => $id,
                'subscription_instance_id' => $recurringSlot->subscription_instance_id,
                'club_id' => $club->id,
                'user_id' => $user->id,
                'reason' => $validated['reason'] ?? 'Non spécifiée'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Créneau réactivé avec succès',
                'data' => $recurringSlot->fresh()
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Créneau récurrent non trouvé'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la réactivation du créneau récurrent: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la réactivation du créneau',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Afficher les détails d'un créneau récurrent
     */
    public function show(Request $request, int $id): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if ($user->role !== 'club') {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé'
                ], 403);
            }

            $club = $user->getFirstClub();
            if (!$club) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun club associé'
                ], 404);
            }

            $recurringSlot = SubscriptionRecurringSlot::whereHas('subscriptionInstance', function ($query) use ($club) {
                    $query->whereHas('subscription', function ($q) use ($club) {
                        $q->where('club_id', $club->id);
                    });
                })
                ->with([
                    'subscriptionInstance.subscription',
                    'subscriptionInstance.students.user',
                    'teacher.user',
                    'student.user',
                    'openSlot'
                ])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $recurringSlot
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Créneau récurrent non trouvé'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération du créneau récurrent: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du créneau',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

