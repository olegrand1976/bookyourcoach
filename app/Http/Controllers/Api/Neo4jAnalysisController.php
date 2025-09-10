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
     * Exécuter une requête Cypher personnalisée
     */
    public function executeCustomQuery(Request $request): JsonResponse
    {
        try {
            $query = $request->get('query');
            $parameters = $request->get('parameters', []);
            
            if (!$query) {
                return response()->json([
                    'success' => false,
                    'message' => 'query est requis'
                ], 400);
            }
            
            // Validation basique pour éviter les requêtes dangereuses
            $dangerousKeywords = ['DELETE', 'DROP', 'REMOVE', 'SET', 'CREATE', 'MERGE'];
            foreach ($dangerousKeywords as $keyword) {
                if (stripos($query, $keyword) !== false) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Requête non autorisée: ' . $keyword
                    ], 400);
                }
            }
            
            $data = $this->analysisService->neo4j->run($query, $parameters);
            
            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Requête exécutée avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'exécution de la requête: ' . $e->getMessage()
            ], 500);
        }
    }
}
