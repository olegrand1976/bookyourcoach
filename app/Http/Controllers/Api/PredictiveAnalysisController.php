<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AI\PredictiveAnalysisService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class PredictiveAnalysisController extends Controller
{
    protected $analysisService;

    public function __construct(PredictiveAnalysisService $analysisService)
    {
        $this->analysisService = $analysisService;
    }

    /**
     * Obtenir l'analyse prédictive complète pour le club
     */
    public function getAnalysis(): JsonResponse
    {
        try {
            $user = Auth::user();
            $club = $user->getFirstClub();

            if (!$club) {
                return response()->json([
                    'success' => false,
                    'message' => 'Club non trouvé'
                ], 404);
            }

            $report = $this->analysisService->generateDashboardReport($club);

            if (!$report) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pas assez de données pour générer une analyse. Continuez à enregistrer des cours.'
                ], 200);
            }

            return response()->json([
                'success' => true,
                'data' => $report
            ]);
        } catch (\Exception $e) {
            \Log::error('Erreur analyse prédictive:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération de l\'analyse'
            ], 500);
        }
    }

    /**
     * Obtenir uniquement les alertes critiques
     */
    public function getCriticalAlerts(): JsonResponse
    {
        try {
            $user = Auth::user();
            $club = $user->getFirstClub();

            if (!$club) {
                return response()->json([
                    'success' => false,
                    'message' => 'Club non trouvé'
                ], 404);
            }

            $alerts = $this->analysisService->getCriticalAlerts($club);

            return response()->json([
                'success' => true,
                'data' => $alerts
            ]);
        } catch (\Exception $e) {
            \Log::error('Erreur récupération alertes:', [
                'message' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des alertes'
            ], 500);
        }
    }
}
