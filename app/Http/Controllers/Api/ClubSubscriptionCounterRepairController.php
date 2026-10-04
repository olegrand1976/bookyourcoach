<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApplySubscriptionCounterRepairRequest;
use App\Services\SubscriptionCounterRepairService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Vérification des compteurs d'abonnements : simulation, puis correction validée par le club.
 */
class ClubSubscriptionCounterRepairController extends Controller
{
    public function __construct(
        private readonly SubscriptionCounterRepairService $repairService,
    ) {}

    public function preview(): JsonResponse
    {
        $user = Auth::user();
        if ($user->role !== 'club') {
            return response()->json(['success' => false, 'message' => 'Accès réservé aux clubs'], 403);
        }

        $club = $user->getFirstClub();
        if (! $club) {
            return response()->json(['success' => false, 'message' => 'Aucun club associé'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->repairService->preview($club),
            'message' => 'Simulation des corrections (aucune modification enregistrée).',
        ]);
    }

    public function apply(ApplySubscriptionCounterRepairRequest $request): JsonResponse
    {
        $user = $request->user();
        $club = $user->getFirstClub();
        if (! $club) {
            return response()->json(['success' => false, 'message' => 'Aucun club associé'], 404);
        }

        $validated = $request->validated();

        try {
            $results = $this->repairService->apply(
                $club,
                $validated['instance_ids'],
                $validated['detach_future_excess_for'] ?? [],
                $user,
            );
        } catch (\Throwable $e) {
            Log::error('Correction des compteurs d\'abonnements impossible', [
                'club_id' => $club->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'La correction des compteurs a échoué. Aucune modification n\'a été enregistrée.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'data' => ['results' => $results],
            'message' => count($results).' abonnement(s) corrigé(s).',
        ]);
    }
}
