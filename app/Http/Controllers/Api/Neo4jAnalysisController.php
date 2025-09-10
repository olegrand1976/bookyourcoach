<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Neo4jAnalysisService;
use App\Services\Neo4jSyncService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class Neo4jAnalysisController extends Controller
{
    protected Neo4jAnalysisService $analysisService;
    protected Neo4jSyncService $syncService;

    public function __construct(Neo4jAnalysisService $analysisService, Neo4jSyncService $syncService)
    {
        $this->analysisService = $analysisService;
        $this->syncService = $syncService;
    }

    /**
     * Obtenir les métriques globales
     */
    public function getGlobalMetrics(): JsonResponse
    {
        try {
            $metrics = $this->analysisService->getGlobalMetrics();
            
            return response()->json([
                'success' => true,
                'data' => $metrics,
                'message' => 'Métriques globales récupérées avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des métriques: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Analyser les relations utilisateurs-clubs
     */
    public function analyzeUserClubRelations(): JsonResponse
    {
        try {
            $data = $this->analysisService->analyzeUserClubRelations();
            
            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Analyse des relations utilisateurs-clubs récupérée avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'analyse: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Analyser les enseignants par spécialité
     */
    public function analyzeTeachersBySpecialty(): JsonResponse
    {
        try {
            $data = $this->analysisService->analyzeTeachersBySpecialty();
            
            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Analyse des enseignants par spécialité récupérée avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'analyse: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Analyser les contrats
     */
    public function analyzeContracts(): JsonResponse
    {
        try {
            $data = $this->analysisService->analyzeContracts();
            
            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Analyse des contrats récupérée avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'analyse: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Trouver les enseignants les plus connectés
     */
    public function findMostConnectedTeachers(Request $request): JsonResponse
    {
        try {
            $limit = $request->get('limit', 10);
            $data = $this->analysisService->findMostConnectedTeachers($limit);
            
            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Enseignants les plus connectés récupérés avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'analyse: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Analyser la répartition géographique
     */
    public function analyzeGeographicDistribution(): JsonResponse
    {
        try {
            $data = $this->analysisService->analyzeGeographicDistribution();
            
            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Analyse géographique récupérée avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'analyse: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Analyser les relations enseignants-clubs
     */
    public function analyzeTeacherClubRelations(): JsonResponse
    {
        try {
            $data = $this->analysisService->analyzeTeacherClubRelations();
            
            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Analyse des relations enseignants-clubs récupérée avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'analyse: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Analyser les tendances temporelles
     */
    public function analyzeContractTrends(): JsonResponse
    {
        try {
            $data = $this->analysisService->analyzeContractTrends();
            
            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Analyse des tendances temporelles récupérée avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'analyse: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Analyser les spécialités les plus demandées
     */
    public function analyzeMostDemandedSpecialties(): JsonResponse
    {
        try {
            $data = $this->analysisService->analyzeMostDemandedSpecialties();
            
            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Analyse des spécialités les plus demandées récupérée avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'analyse: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Recommander des enseignants pour un club
     */
    public function recommendTeachersForClub(Request $request): JsonResponse
    {
        try {
            $clubId = $request->get('club_id');
            $specialties = $request->get('specialties', []);
            
            if (!$clubId) {
                return response()->json([
                    'success' => false,
                    'message' => 'club_id est requis'
                ], 400);
            }
            
            $data = $this->analysisService->recommendTeachersForClub($clubId, $specialties);
            
            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Recommandations d\'enseignants récupérées avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'analyse: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Analyser la performance des clubs
     */
    public function analyzeClubPerformance(): JsonResponse
    {
        try {
            $data = $this->analysisService->analyzeClubPerformance();
            
            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Analyse de performance des clubs récupérée avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'analyse: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les statistiques de synchronisation
     */
    public function getSyncStats(): JsonResponse
    {
        try {
            $stats = $this->syncService->getSyncStats();
            
            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'Statistiques de synchronisation récupérées avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les données pour la visualisation graphique
     */
    public function getGraphVisualization(Request $request): JsonResponse
    {
        try {
            $entity = $request->get('entity');
            $entityId = $request->get('id');
            $depth = $request->get('depth', 2);
            $status = $request->get('status', '');
            $city = $request->get('city', '');
            
            if (!$entity || !$entityId) {
                return response()->json([
                    'success' => false,
                    'message' => 'entity et id sont requis'
                ], 400);
            }
            
            $filters = [
                'status' => $status,
                'city' => $city
            ];
            
            $graphData = $this->analysisService->getGraphVisualizationData($entity, $entityId, $depth, $filters);
            
            // Calculer les statistiques
            $stats = [
                'nodes' => count($graphData['nodes']),
                'edges' => count($graphData['edges']),
                'clubs' => count(array_filter($graphData['nodes'], fn($n) => $n['data']['type'] === 'Club')),
                'teachers' => count(array_filter($graphData['nodes'], fn($n) => $n['data']['type'] === 'Teacher')),
                'users' => count(array_filter($graphData['nodes'], fn($n) => $n['data']['type'] === 'User')),
                'contracts' => count(array_filter($graphData['nodes'], fn($n) => $n['data']['type'] === 'Contract'))
            ];
            
            return response()->json([
                'success' => true,
                'data' => $graphData,
                'stats' => $stats,
                'message' => 'Données de visualisation récupérées avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des données: ' . $e->getMessage()
            ], 500);
        }
    }
