<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Neo4jService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class GraphAnalyticsController extends Controller
{
    private Neo4jService $neo4jService;

    public function __construct(Neo4jService $neo4jService)
    {
        $this->neo4jService = $neo4jService;
    }

    /**
     * Dashboard principal des analyses graphiques
     */
    public function getDashboard(): JsonResponse
    {
        try {
            // Vérifier la connexion Neo4j
            if (!$this->neo4jService->checkConnection()) {
                return response()->json([
                    'error' => 'Neo4j non disponible',
                    'data' => null
                ], 503);
            }

            $data = [
                'network_stats' => $this->getNetworkStats(),
                'top_teachers' => $this->getTopTeachers(),
                'skills_network' => $this->getSkillsNetwork(),
                'student_progress' => $this->getStudentProgress(),
                'recommendations' => $this->getRecommendations()
            ];

            return response()->json([
                'success' => true,
                'data' => $data,
                'timestamp' => now()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la récupération des données',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Statistiques du réseau
     */
    public function getNetworkStats(): array
    {
        $query = '
            MATCH (n)
            RETURN labels(n)[0] as label, count(n) as count
            ORDER BY count DESC
        ';

        $result = $this->neo4jService->client->run($query);
        $stats = [];

        foreach ($result->toArray() as $row) {
            $stats[$row['label']] = $row['count'];
        }

        return [
            'total_nodes' => array_sum($stats),
            'node_types' => $stats,
            'total_relationships' => $this->getTotalRelationships()
        ];
    }

    /**
     * Top enseignants par performance
     */
    public function getTopTeachers(): array
    {
        $query = '
            MATCH (t:Teacher)
            MATCH (t)-[:TEACHES]->(l:Lesson)
            WITH t, count(l) as totalLessons, avg(l.rating) as avgRating
            MATCH (t)-[:HAS_SKILL]->(s:Skill)
            WITH t, totalLessons, avgRating, count(s) as skillCount
            RETURN t.id as teacher_id,
                   t.name as name,
                   t.experience_years as experience,
                   totalLessons,
                   avgRating,
                   skillCount
            ORDER BY avgRating DESC, totalLessons DESC
            LIMIT 10
        ';

        $result = $this->neo4jService->client->run($query);
        return $result->toArray();
    }

    /**
     * Réseau des compétences
     */
    public function getSkillsNetwork(): array
    {
        return $this->neo4jService->analyzeSkillsNetwork();
    }

    /**
     * Progression des étudiants
     */
    public function getStudentProgress(): array
    {
        $query = '
            MATCH (s:Student)
            MATCH (s)-[:TAKES_LESSON]->(l:Lesson)
            WITH s, count(l) as totalLessons, avg(l.progress) as avgProgress
            RETURN s.id as student_id,
                   s.name as name,
                   s.level as level,
                   totalLessons,
                   avgProgress
            ORDER BY avgProgress DESC
            LIMIT 20
        ';

        $result = $this->neo4jService->client->run($query);
        return $result->toArray();
    }

    /**
     * Recommandations générales
     */
    public function getRecommendations(): array
    {
        $query = '
            MATCH (t:Teacher)-[:HAS_SKILL]->(s:Skill)
            WITH s, count(t) as teacherCount
            WHERE teacherCount < 3
            RETURN s.name as skill_name,
                   s.category as category,
                   teacherCount,
                   "Besoin de plus d\'enseignants" as recommendation
            ORDER BY teacherCount ASC
            LIMIT 5
        ';

        $result = $this->neo4jService->client->run($query);
        return $result->toArray();
    }

    /**
     * Matching enseignant-étudiant
     */
    public function getTeacherMatching(Request $request): JsonResponse
    {
        $studentId = $request->input('student_id');
        $requirements = $request->input('requirements', []);

        try {
            $matches = $this->neo4jService->findMatchingTeachers(
                \App\Models\Student::find($studentId),
                $requirements
            );

            return response()->json([
                'success' => true,
                'matches' => $matches
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors du matching',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Analyse de performance d'un enseignant
     */
    public function getTeacherPerformance(Request $request): JsonResponse
    {
        $teacherId = $request->input('teacher_id');

        try {
            $performance = $this->neo4jService->analyzeTeacherPerformance($teacherId);
            $recommendations = $this->neo4jService->getTeacherRecommendations($teacherId);

            return response()->json([
                'success' => true,
                'performance' => $performance,
                'recommendations' => $recommendations
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de l\'analyse',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Prédiction de réussite d'un étudiant
     */
    public function predictStudentSuccess(Request $request): JsonResponse
    {
        $studentId = $request->input('student_id');

        try {
            $prediction = $this->neo4jService->predictStudentSuccess($studentId);

            return response()->json([
                'success' => true,
                'prediction' => $prediction
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la prédiction',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Visualisation du graphe complet
     */
    public function getGraphVisualization(): JsonResponse
    {
        try {
            $query = '
                MATCH (n)-[r]->(m)
                RETURN n, r, m
                LIMIT 100
            ';

            $result = $this->neo4jService->client->run($query);
            $graph = [
                'nodes' => [],
                'edges' => []
            ];

            $nodeIds = [];
            foreach ($result->toArray() as $row) {
                // Ajouter les nœuds
                if (!in_array($row['n']['id'], $nodeIds)) {
                    $graph['nodes'][] = [
                        'id' => $row['n']['id'],
                        'label' => $row['n']['name'] ?? $row['n']['id'],
                        'type' => array_keys($row['n'])[0] ?? 'unknown',
                        'properties' => $row['n']
                    ];
                    $nodeIds[] = $row['n']['id'];
                }

                if (!in_array($row['m']['id'], $nodeIds)) {
                    $graph['nodes'][] = [
                        'id' => $row['m']['id'],
                        'label' => $row['m']['name'] ?? $row['m']['id'],
                        'type' => array_keys($row['m'])[0] ?? 'unknown',
                        'properties' => $row['m']
                    ];
                    $nodeIds[] = $row['m']['id'];
                }

                // Ajouter l'arête
                $graph['edges'][] = [
                    'source' => $row['n']['id'],
                    'target' => $row['m']['id'],
                    'type' => $row['r']['type'] ?? 'RELATES_TO',
                    'properties' => $row['r']
                ];
            }

            return response()->json([
                'success' => true,
                'graph' => $graph
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la récupération du graphe',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Synchroniser toutes les données
     */
    public function syncAllData(): JsonResponse
    {
        try {
            $results = $this->neo4jService->syncAllData();

            return response()->json([
                'success' => true,
                'sync_results' => $results,
                'message' => 'Synchronisation terminée'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la synchronisation',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Vérifier le statut de Neo4j
     */
    public function getStatus(): JsonResponse
    {
        $isConnected = $this->neo4jService->checkConnection();
        
        return response()->json([
            'neo4j_connected' => $isConnected,
            'timestamp' => now()
        ]);
    }

    /**
     * Obtenir le nombre total de relations
     */
    private function getTotalRelationships(): int
    {
        $query = 'MATCH ()-[r]->() RETURN count(r) as total';
        $result = $this->neo4jService->client->run($query);
        return $result->toArray()[0]['total'] ?? 0;
    }
}