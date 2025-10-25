<?php

namespace App\Services;

use App\Services\Neo4jService;
use Illuminate\Support\Facades\Log;

class Neo4jAnalysisService
{
    protected Neo4jService $neo4j;

    public function __construct(Neo4jService $neo4j)
    {
        $this->neo4j = $neo4j;
    }

    /**
     * Analyser les relations entre utilisateurs et clubs
     */
    public function analyzeUserClubRelations(): array
    {
        $query = "
            MATCH (u:User)-[r:MEMBERSHIP]->(c:Club)
            RETURN 
                c.name as club_name,
                c.city as club_city,
                count(u) as member_count,
                collect(u.name)[0..5] as sample_members
            ORDER BY member_count DESC
        ";

        return $this->neo4j->run($query);
    }

    /**
     * Analyser les enseignants par spécialité
     */
    public function analyzeTeachersBySpecialty(): array
    {
        $query = "
            MATCH (t:Teacher)
            WHERE t.specialties IS NOT NULL
            UNWIND t.specialties as specialty
            RETURN 
                specialty,
                count(t) as teacher_count,
                avg(t.experience_years) as avg_experience,
                avg(t.hourly_rate) as avg_hourly_rate
            ORDER BY teacher_count DESC
        ";

        return $this->neo4j->run($query);
    }

    /**
     * Analyser les contrats par type et statut
     */
    public function analyzeContracts(): array
    {
        $query = "
            MATCH (c:Contract)-[:HAS_CONTRACT]->(t:Teacher)
            MATCH (c)-[:WORKING_FOR]->(cl:Club)
            RETURN 
                c.type as contract_type,
                c.status as contract_status,
                count(c) as contract_count,
                avg(c.hourly_rate) as avg_hourly_rate,
                avg(c.hours_per_week) as avg_hours_per_week
            ORDER BY contract_count DESC
        ";

        return $this->neo4j->run($query);
    }

    /**
     * Trouver les enseignants les plus connectés
     */
    public function findMostConnectedTeachers(int $limit = 10): array
    {
        $query = "
            MATCH (t:Teacher)-[:IS_TEACHER]->(u:User)
            MATCH (t)<-[:HAS_CONTRACT]-(c:Contract)-[:WORKING_FOR]->(cl:Club)
            RETURN 
                u.name as teacher_name,
                t.experience_years,
                t.specialties,
                count(DISTINCT cl) as clubs_count,
                count(DISTINCT c) as contracts_count,
                avg(c.hourly_rate) as avg_hourly_rate
            ORDER BY clubs_count DESC, contracts_count DESC
            LIMIT \$limit
        ";

        return $this->neo4j->run($query, ['limit' => $limit]);
    }

    /**
     * Analyser la répartition géographique des clubs
     */
    public function analyzeGeographicDistribution(): array
    {
        $query = "
            MATCH (c:Club)
            WHERE c.city IS NOT NULL
            RETURN 
                c.city,
                c.country,
                count(c) as clubs_count,
                collect(c.name)[0..3] as sample_clubs
            ORDER BY clubs_count DESC
        ";

        return $this->neo4j->run($query);
    }

    /**
     * Analyser les relations entre enseignants et clubs
     */
    public function analyzeTeacherClubRelations(): array
    {
        $query = "
            MATCH (t:Teacher)-[:IS_TEACHER]->(u:User)
            MATCH (t)<-[:HAS_CONTRACT]-(c:Contract)-[:WORKING_FOR]->(cl:Club)
            RETURN 
                cl.name as club_name,
                cl.city as club_city,
                count(DISTINCT t) as teachers_count,
                avg(c.hourly_rate) as avg_hourly_rate,
                collect(DISTINCT t.specialties)[0..5] as specialties
            ORDER BY teachers_count DESC
        ";

        return $this->neo4j->run($query);
    }

    /**
     * Analyser les tendances temporelles des contrats
     */
    public function analyzeContractTrends(): array
    {
        $query = "
            MATCH (c:Contract)
            WHERE c.start_date IS NOT NULL
            WITH 
                date(c.start_date) as start_date,
                c.type as contract_type,
                c.status as contract_status
            RETURN 
                start_date,
                contract_type,
                contract_status,
                count(c) as contracts_count
            ORDER BY start_date DESC
            LIMIT 50
        ";

        return $this->neo4j->run($query);
    }

    /**
     * Trouver les clusters d'utilisateurs par ville
     */
    public function findUserClustersByCity(): array
    {
        $query = "
            MATCH (u:User)-[:MEMBERSHIP]->(c:Club)
            WHERE u.city IS NOT NULL AND c.city IS NOT NULL
            RETURN 
                u.city as user_city,
                c.city as club_city,
                count(u) as user_count,
                count(DISTINCT c) as clubs_count
            ORDER BY user_count DESC
        ";

        return $this->neo4j->run($query);
    }

    /**
     * Analyser les spécialités les plus demandées
     */
    public function analyzeMostDemandedSpecialties(): array
    {
        $query = "
            MATCH (t:Teacher)-[:IS_TEACHER]->(u:User)
            MATCH (t)<-[:HAS_CONTRACT]-(c:Contract)-[:WORKING_FOR]->(cl:Club)
            WHERE t.specialties IS NOT NULL
            UNWIND t.specialties as specialty
            RETURN 
                specialty,
                count(DISTINCT t) as teachers_count,
                count(DISTINCT cl) as clubs_count,
                count(DISTINCT c) as contracts_count,
                avg(c.hourly_rate) as avg_hourly_rate
            ORDER BY contracts_count DESC
        ";

        return $this->neo4j->run($query);
    }

    /**
     * Analyser les relations complexes (chemins de longueur 2+)
     */
    public function analyzeComplexRelations(): array
    {
        $query = "
            MATCH path = (u:User)-[:MEMBERSHIP]->(c:Club)<-[:WORKING_FOR]-(contract:Contract)-[:HAS_CONTRACT]->(t:Teacher)-[:IS_TEACHER]->(u2:User)
            WHERE u <> u2
            RETURN 
                u.name as user1_name,
                u2.name as user2_name,
                c.name as club_name,
                t.specialties,
                length(path) as path_length
            ORDER BY path_length DESC
            LIMIT 20
        ";

        return $this->neo4j->run($query);
    }

    /**
     * Obtenir des métriques globales
     */
    public function getGlobalMetrics(): array
    {
        $queries = [
            'total_users' => 'MATCH (u:User) RETURN count(u) as count',
            'total_clubs' => 'MATCH (c:Club) RETURN count(c) as count',
            'total_teachers' => 'MATCH (t:Teacher) RETURN count(t) as count',
            'total_contracts' => 'MATCH (c:Contract) RETURN count(c) as count',
            'active_contracts' => 'MATCH (c:Contract) WHERE c.status = "active" RETURN count(c) as count',
            'total_relationships' => 'MATCH ()-[r]->() RETURN count(r) as count',
            'avg_club_members' => 'MATCH (c:Club)<-[:MEMBERSHIP]-(u:User) RETURN avg(size(collect(u))) as avg',
            'avg_teacher_contracts' => 'MATCH (t:Teacher)<-[:HAS_CONTRACT]-(c:Contract) RETURN avg(size(collect(c))) as avg',
        ];

        $metrics = [];
        foreach ($queries as $key => $query) {
            try {
                $result = $this->neo4j->run($query);
                $metrics[$key] = $result[0]['count'] ?? $result[0]['avg'] ?? 0;
            } catch (\Exception $e) {
                $metrics[$key] = 0;
                Log::warning("Failed to get metric: " . $key, ['error' => $e->getMessage()]);
            }
        }

        return $metrics;
    }

    /**
     * Recommander des enseignants pour un club
     */
    public function recommendTeachersForClub(int $clubId, array $preferredSpecialties = []): array
    {
        $specialtyFilter = '';
        $parameters = ['club_id' => $clubId];

        if (!empty($preferredSpecialties)) {
            $specialtyFilter = "AND ANY(specialty IN t.specialties WHERE specialty IN \$preferred_specialties)";
            $parameters['preferred_specialties'] = $preferredSpecialties;
        }

        $query = "
            MATCH (cl:Club {id: \$club_id})
            MATCH (t:Teacher)-[:IS_TEACHER]->(u:User)
            MATCH (t)<-[:HAS_CONTRACT]-(c:Contract)-[:WORKING_FOR]->(otherClub:Club)
            WHERE otherClub <> cl " . $specialtyFilter . "
            RETURN 
                u.name as teacher_name,
                t.specialties,
                t.experience_years,
                avg(c.hourly_rate) as avg_hourly_rate,
                count(DISTINCT otherClub) as experience_clubs,
                collect(DISTINCT otherClub.name)[0..3] as sample_clubs
            ORDER BY experience_clubs DESC, t.experience_years DESC
            LIMIT 10
        ";

        return $this->neo4j->run($query, $parameters);
    }

    /**
     * Obtenir les données pour la visualisation graphique
     */
    public function getGraphVisualizationData(string $entity, int $entityId, int $depth = 2, array $filters = []): array
    {
        $statusFilter = $filters['status'] ?? '';
        $cityFilter = $filters['city'] ?? '';
        
        // Construire la requête selon l'entité de départ
        $query = $this->buildGraphQuery($entity, $entityId, $depth, $statusFilter, $cityFilter);
        
        $result = $this->neo4j->run($query, [
            'entity_id' => $entityId,
            'depth' => $depth
        ]);
        
        return $this->formatGraphData($result, $entity, $entityId);
    }

    /**
     * Construire la requête Cypher selon l'entité
     */
    protected function buildGraphQuery(string $entity, int $entityId, int $depth, string $statusFilter, string $cityFilter): string
    {
        $statusCondition = $statusFilter ? "AND c.status = \"" . $statusFilter . "\"" : '';
        $cityCondition = $cityFilter ? "AND (cl.city = \"" . $cityFilter . "\" OR u.city = \"" . $cityFilter . "\")" : '';
        
        switch ($entity) {
            case 'club':
                $query = "MATCH path = (cl:Club {id: \$entity_id})-[*1.." . $depth . "]-(connected)
                    WHERE connected:User OR connected:Teacher OR connected:Contract";
                if ($cityCondition) {
                    $query .= " " . $cityCondition;
                }
                $query .= "
                    WITH cl, connected, path
                    OPTIONAL MATCH (connected)-[r]-(other)
                    WHERE other <> cl
                    RETURN 
                        cl as start_node,
                        collect(DISTINCT connected) as nodes,
                        collect(DISTINCT r) as relationships";
                return $query;
                
            case 'teacher':
                $query = "MATCH (t:Teacher {id: \$entity_id})-[:IS_TEACHER]->(u:User)
                    MATCH path = (t)-[*1.." . $depth . "]-(connected)
                    WHERE connected:Club OR connected:Contract OR connected:User";
                if ($statusCondition) {
                    $query .= " " . $statusCondition;
                }
                if ($cityCondition) {
                    $query .= " " . $cityCondition;
                }
                $query .= "
                    WITH t, u, connected, path
                    OPTIONAL MATCH (connected)-[r]-(other)
                    WHERE other <> t AND other <> u
                    RETURN 
                        t as start_node,
                        u as teacher_user,
                        collect(DISTINCT connected) as nodes,
                        collect(DISTINCT r) as relationships";
                return $query;
                
            case 'user':
                $query = "MATCH (u:User {id: \$entity_id})
                    MATCH path = (u)-[*1.." . $depth . "]-(connected)
                    WHERE connected:Club OR connected:Teacher OR connected:Contract";
                if ($cityCondition) {
                    $query .= " " . $cityCondition;
                }
                $query .= "
                    WITH u, connected, path
                    OPTIONAL MATCH (connected)-[r]-(other)
                    WHERE other <> u
                    RETURN 
                        u as start_node,
                        collect(DISTINCT connected) as nodes,
                        collect(DISTINCT r) as relationships";
                return $query;
                
            case 'contract':
                $query = "MATCH (c:Contract {id: \$entity_id})-[:HAS_CONTRACT]->(t:Teacher)
                    MATCH (c)-[:WORKING_FOR]->(cl:Club)
                    MATCH path = (c)-[*1.." . $depth . "]-(connected)
                    WHERE connected:Club OR connected:Teacher OR connected:User";
                if ($statusCondition) {
                    $query .= " " . $statusCondition;
                }
                if ($cityCondition) {
                    $query .= " " . $cityCondition;
                }
                $query .= "
                    WITH c, t, cl, connected, path
                    OPTIONAL MATCH (connected)-[r]-(other)
                    WHERE other <> c
                    RETURN 
                        c as start_node,
                        t as teacher,
                        cl as club,
                        collect(DISTINCT connected) as nodes,
                        collect(DISTINCT r) as relationships";
                return $query;
                
            default:
                throw new \InvalidArgumentException("Entité non supportée: " . $entity);
        }
    }

    /**
     * Formater les données pour Cytoscape
     */
    protected function formatGraphData(array $result, string $entity, int $entityId): array
    {
        $nodes = [];
        $edges = [];
        $nodeIds = [];
        
        if (empty($result)) {
            return ['nodes' => [], 'edges' => []];
        }
        
        $data = $result[0];
        
        // Ajouter le nœud de départ
        $startNode = $data['start_node'] ?? null;
        if ($startNode) {
            $nodeId = $this->getNodeId($startNode);
            $nodes[] = [
                'data' => [
                    'id' => $nodeId,
                    'label' => $this->getNodeLabel($startNode),
                    'color' => $this->getNodeColor($startNode),
                    'size' => 40,
                    'type' => $this->getNodeType($startNode),
                    'properties' => $startNode
                ]
            ];
            $nodeIds[] = $nodeId;
        }
        
        // Ajouter les nœuds connectés
        $connectedNodes = $data['nodes'] ?? [];
        foreach ($connectedNodes as $node) {
            $nodeId = $this->getNodeId($node);
            if (!in_array($nodeId, $nodeIds)) {
                $nodes[] = [
                    'data' => [
                        'id' => $nodeId,
                        'label' => $this->getNodeLabel($node),
                        'color' => $this->getNodeColor($node),
                        'size' => 30,
                        'type' => $this->getNodeType($node),
                        'properties' => $node
                    ]
                ];
                $nodeIds[] = $nodeId;
            }
        }
        
        // Ajouter les relations
        $relationships = $data['relationships'] ?? [];
        foreach ($relationships as $rel) {
            $sourceId = $this->getNodeId($rel['start']);
            $targetId = $this->getNodeId($rel['end']);
            
            if (in_array($sourceId, $nodeIds) && in_array($targetId, $nodeIds)) {
                $edges[] = [
                    'data' => [
                        'id' => $sourceId . '-' . $targetId,
                        'source' => $sourceId,
                        'target' => $targetId,
                        'label' => $this->getEdgeLabel($rel),
                        'color' => $this->getEdgeColor($rel),
                        'properties' => $rel
                    ]
                ];
            }
        }
        
        return [
            'nodes' => $nodes,
            'edges' => $edges
        ];
    }

    /**
     * Obtenir l'ID unique d'un nœud
     */
    protected function getNodeId(array $node): string
    {
        $type = $this->getNodeType($node);
        $id = $node['id'] ?? uniqid();
        return $type . "_" . $id;
    }

    /**
     * Obtenir le type de nœud
     */
    protected function getNodeType(array $node): string
    {
        if (isset($node['email'])) return 'User';
        if (isset($node['bio'])) return 'Teacher';
        if (isset($node['type'])) return 'Contract';
        if (isset($node['name']) && !isset($node['email'])) return 'Club';
        return 'Unknown';
    }

    /**
     * Obtenir le label d'un nœud
     */
    protected function getNodeLabel(array $node): string
    {
        if (isset($node['name'])) {
            return $node['name'];
        }
        if (isset($node['first_name']) && isset($node['last_name'])) {
            return $node['first_name'] . ' ' . $node['last_name'];
        }
        if (isset($node['email'])) {
            return $node['email'];
        }
        return 'Nœud ' . ($node['id'] ?? '');
    }

    /**
     * Obtenir la couleur d'un nœud
     */
    protected function getNodeColor(array $node): string
    {
        $type = $this->getNodeType($node);
        $colors = [
            'Club' => '#3B82F6',      // Bleu
            'Teacher' => '#10B981',   // Vert
            'User' => '#8B5CF6',      // Violet
            'Contract' => '#F59E0B',  // Orange
            'Unknown' => '#6B7280'    // Gris
        ];
        return $colors[$type] ?? '#6B7280';
    }

    /**
     * Obtenir le label d'une relation
     */
    protected function getEdgeLabel(array $rel): string
    {
        $type = $rel['type'] ?? '';
        $labels = [
            'MEMBERSHIP' => 'Membre',
            'IS_TEACHER' => 'Est enseignant',
            'HAS_CONTRACT' => 'A contrat',
            'WORKING_FOR' => 'Travaille pour'
        ];
        return $labels[$type] ?? $type;
    }

    /**
     * Obtenir la couleur d'une relation
     */
    protected function getEdgeColor(array $rel): string
    {
        $type = $rel['type'] ?? '';
        $colors = [
            'MEMBERSHIP' => '#3B82F6',
            'IS_TEACHER' => '#10B981',
            'HAS_CONTRACT' => '#F59E0B',
            'WORKING_FOR' => '#EF4444'
        ];
        return $colors[$type] ?? '#6B7280';
    }
}
