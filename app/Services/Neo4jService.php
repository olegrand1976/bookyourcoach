<?php

namespace App\Services;

use Laudis\Neo4j\ClientBuilder;
use Laudis\Neo4j\Contracts\ClientInterface;
use Laudis\Neo4j\Authentication\Authenticate;
use Illuminate\Support\Facades\Log;

class Neo4jService
{
    protected ClientInterface $client;
    protected string $connection;

    public function __construct(string $connection = 'default')
    {
        $this->connection = $connection;
        $this->client = $this->createClient();
    }

    protected function createClient(): ClientInterface
    {
        $config = config("neo4j.connections.{$this->connection}");
        
        return ClientBuilder::create()
            ->withDriver('bolt', $config['uri'], Authenticate::basic($config['username'], $config['password']))
            ->build();
    }

    /**
     * Exécuter une requête Cypher
     */
    public function run(string $query, array $parameters = []): array
    {
        try {
            $result = $this->client->run($query, $parameters);
            return $result->toArray();
        } catch (\Exception $e) {
            Log::error('Neo4j Query Error', [
                'query' => $query,
                'parameters' => $parameters,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Créer un nœud
     */
    public function createNode(string $label, array $properties = []): array
    {
        $query = "CREATE (n:{$label}) SET n += \$properties RETURN n";
        $result = $this->run($query, ['properties' => $properties]);
        return $result[0]['n'] ?? [];
    }

    /**
     * Créer une relation
     */
    public function createRelationship(string $fromLabel, string $toLabel, string $relationshipType, array $properties = []): array
    {
        $query = "
            MATCH (a:{$fromLabel}), (b:{$toLabel})
            CREATE (a)-[r:{$relationshipType}]->(b)
            SET r += \$properties
            RETURN r
        ";
        $result = $this->run($query, ['properties' => $properties]);
        return $result[0]['r'] ?? [];
    }

    /**
     * Trouver un nœud par propriété
     */
    public function findNode(string $label, string $property, $value): ?array
    {
        $query = "MATCH (n:{$label}) WHERE n.{$property} = \$value RETURN n LIMIT 1";
        $result = $this->run($query, ['value' => $value]);
        return $result[0]['n'] ?? null;
    }

    /**
     * Mettre à jour un nœud
     */
    public function updateNode(string $label, string $property, $value, array $updates): array
    {
        $query = "
            MATCH (n:{$label}) 
            WHERE n.{$property} = \$value 
            SET n += \$updates 
            RETURN n
        ";
        $result = $this->run($query, ['value' => $value, 'updates' => $updates]);
        return $result[0]['n'] ?? [];
    }

    /**
     * Supprimer un nœud et ses relations
     */
    public function deleteNode(string $label, string $property, $value): bool
    {
        $query = "MATCH (n:{$label}) WHERE n.{$property} = \$value DETACH DELETE n";
        $this->run($query, ['value' => $value]);
        return true;
    }

    /**
     * Créer les index nécessaires
     */
    public function createIndexes(): void
    {
        $indexes = config('neo4j.indexes');
        
        foreach ($indexes as $entity => $entityIndexes) {
            foreach ($entityIndexes as $indexName => $query) {
                try {
                    $this->run($query);
                    Log::info("Neo4j index created: {$indexName}");
                } catch (\Exception $e) {
                    Log::warning("Failed to create Neo4j index: {$indexName}", ['error' => $e->getMessage()]);
                }
            }
        }
    }

    /**
     * Vérifier la connexion
     */
    public function testConnection(): bool
    {
        try {
            $result = $this->run('RETURN 1 as test');
            return !empty($result);
        } catch (\Exception $e) {
            Log::error('Neo4j connection test failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Obtenir des statistiques de la base
     */
    public function getStats(): array
    {
        $queries = [
            'nodes' => 'MATCH (n) RETURN count(n) as count',
            'relationships' => 'MATCH ()-[r]->() RETURN count(r) as count',
            'users' => 'MATCH (u:User) RETURN count(u) as count',
            'clubs' => 'MATCH (c:Club) RETURN count(c) as count',
            'teachers' => 'MATCH (t:Teacher) RETURN count(t) as count',
        ];

        $stats = [];
        foreach ($queries as $key => $query) {
            try {
                $result = $this->run($query);
                $stats[$key] = $result[0]['count'] ?? 0;
            } catch (\Exception $e) {
                $stats[$key] = 0;
            }
        }

        return $stats;
    }
}