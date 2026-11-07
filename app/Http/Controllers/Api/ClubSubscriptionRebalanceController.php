<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Club;
use App\Models\SubscriptionRebalance;
use App\Services\SubscriptionRebalanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Remise en cohérence des rattachements cours ↔ abonnements : contrôle du club,
 * simulation et application par élève, annulation par élève.
 */
class ClubSubscriptionRebalanceController extends Controller
{
    public function __construct(
        private readonly SubscriptionRebalanceService $service,
    ) {}

    /** Élèves (familles) dont les rattachements sont incohérents. */
    public function audit(): JsonResponse
    {
        $club = $this->club();
        if ($club instanceof JsonResponse) {
            return $club;
        }

        $rows = $this->service->audit($club);

        return response()->json([
            'success' => true,
            'data' => $rows,
            'message' => count($rows).' élève(s) ou famille(s) à remettre en cohérence.',
        ]);
    }

    /** Simulation pour un élève + historique de ses corrections. */
    public function show(int $studentId): JsonResponse
    {
        $club = $this->club();
        if ($club instanceof JsonResponse) {
            return $club;
        }
        if (! $this->studentBelongsToClub($club, $studentId)) {
            return response()->json(['success' => false, 'message' => 'Élève introuvable dans ce club'], 404);
        }

        $plan = $this->service->plan($club, $studentId);
        unset($plan['targets']);

        return response()->json([
            'success' => true,
            'data' => [
                'plan' => $plan,
                'history' => $this->service->historyForStudent($club, $studentId)->map(fn (SubscriptionRebalance $r) => [
                    'id' => $r->id,
                    'created_at' => $r->created_at?->toIso8601String(),
                    'applied_by' => $r->appliedBy?->name,
                    'summary' => $r->summary,
                    'reverted_at' => $r->reverted_at?->toIso8601String(),
                    'reverted_by' => $r->revertedBy?->name,
                ])->values(),
            ],
            'message' => 'Simulation (aucune modification enregistrée).',
        ]);
    }

    public function apply(int $studentId): JsonResponse
    {
        $club = $this->club();
        if ($club instanceof JsonResponse) {
            return $club;
        }
        if (! $this->studentBelongsToClub($club, $studentId)) {
            return response()->json(['success' => false, 'message' => 'Élève introuvable dans ce club'], 404);
        }

        try {
            $rebalance = $this->service->apply($club, $studentId, Auth::user());
        } catch (\Throwable $e) {
            Log::error('Remise en cohérence des abonnements impossible', [
                'club_id' => $club->id,
                'student_id' => $studentId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'La remise en cohérence a échoué. Aucune modification n\'a été enregistrée.',
            ], 500);
        }

        if (! $rebalance) {
            return response()->json([
                'success' => true,
                'data' => null,
                'message' => 'Rien à corriger : les abonnements de cet élève sont déjà cohérents.',
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => ['id' => $rebalance->id, 'summary' => $rebalance->summary],
            'message' => 'Abonnements remis en cohérence. Cette correction peut être annulée.',
        ]);
    }

    public function revert(int $rebalanceId): JsonResponse
    {
        $club = $this->club();
        if ($club instanceof JsonResponse) {
            return $club;
        }

        $rebalance = SubscriptionRebalance::query()
            ->where('club_id', $club->id)
            ->find($rebalanceId);
        if (! $rebalance) {
            return response()->json(['success' => false, 'message' => 'Correction introuvable'], 404);
        }

        try {
            $this->service->revert($rebalance, Auth::user());
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            Log::error('Annulation de remise en cohérence impossible', [
                'club_id' => $club->id,
                'rebalance_id' => $rebalanceId,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['success' => false, 'message' => 'L\'annulation a échoué. Aucune modification n\'a été enregistrée.'], 500);
        }

        return response()->json([
            'success' => true,
            'data' => ['id' => $rebalance->id],
            'message' => 'Correction annulée : les abonnements sont revenus à leur état précédent.',
        ]);
    }

    private function club(): Club|JsonResponse
    {
        $user = Auth::user();
        if (! $user || $user->role !== 'club') {
            return response()->json(['success' => false, 'message' => 'Accès réservé aux clubs'], 403);
        }
        $club = $user->getFirstClub();
        if (! $club) {
            return response()->json(['success' => false, 'message' => 'Aucun club associé'], 404);
        }

        return $club;
    }

    private function studentBelongsToClub(Club $club, int $studentId): bool
    {
        return DB::table('club_students')
            ->where('club_id', $club->id)
            ->where('student_id', $studentId)
            ->exists();
    }
}
