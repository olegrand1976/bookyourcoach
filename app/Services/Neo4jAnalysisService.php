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
                Log::warning("Failed to get metric: {$key}", ['error' => $e->getMessage()]);
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
            $specialtyFilter = 'AND ANY(specialty IN t.specialties WHERE specialty IN $preferred_specialties)';
            $parameters['preferred_specialties'] = $preferredSpecialties;
        }

        $query = "
            MATCH (cl:Club {id: \$club_id})
            MATCH (t:Teacher)-[:IS_TEACHER]->(u:User)
            MATCH (t)<-[:HAS_CONTRACT]-(c:Contract)-[:WORKING_FOR]->(otherClub:Club)
            WHERE otherClub <> cl {$specialtyFilter}
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
     * Analyser la performance des clubs
     */
    public function analyzeClubPerformance(): array
    {
        $query = "
            MATCH (c:Club)
            OPTIONAL MATCH (c)<-[:MEMBERSHIP]-(u:User)
            OPTIONAL MATCH (c)<-[:WORKING_FOR]-(contract:Contract)-[:HAS_CONTRACT]->(t:Teacher)
            RETURN 
                c.name as club_name,
                c.city as club_city,
                count(DISTINCT u) as members_count,
                count(DISTINCT t) as teachers_count,
                count(DISTINCT contract) as contracts_count,
                avg(contract.hourly_rate) as avg_hourly_rate
            ORDER BY members_count DESC, teachers_count DESC
        ";

        return $this->neo4j->run($query);
    }
}
