<?php

namespace App\Services;

use App\Models\User;
use App\Models\Club;
use App\Models\Teacher;
use App\Models\Contract;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class Neo4jSyncService
{
    protected Neo4jService $neo4j;
    protected int $batchSize;
    protected int $timeout;
    protected int $retryAttempts;
    protected int $retryDelay;

    public function __construct(Neo4jService $neo4j)
    {
        $this->neo4j = $neo4j;
        $this->batchSize = config('neo4j.sync.batch_size', 100);
        $this->timeout = config('neo4j.sync.timeout', 300);
        $this->retryAttempts = config('ne4j.sync.retry_attempts', 3);
        $this->retryDelay = config('neo4j.sync.retry_delay', 5);
    }

    /**
     * Synchroniser toutes les données
     */
    public function syncAll(): array
    {
        $results = [];
        
        Log::info('Starting Neo4j synchronization');
        
        try {
            // Créer les index
            $this->neo4j->createIndexes();
            
            // Synchroniser les entités
            $results['users'] = $this->syncUsers();
            $results['clubs'] = $this->syncClubs();
            $results['teachers'] = $this->syncTeachers();
            $results['contracts'] = $this->syncContracts();
            
            // Créer les relations
            $results['relationships'] = $this->createRelationships();
            
            Log::info('Neo4j synchronization completed', $results);
            
        } catch (\Exception $e) {
            Log::error('Neo4j synchronization failed', ['error' => $e->getMessage()]);
            throw $e;
        }

        return $results;
    }

    /**
     * Synchroniser les utilisateurs
     */
    public function syncUsers(): int
    {
        $count = 0;
        $users = User::with(['club', 'teacher'])->get();
        
        foreach ($users as $user) {
            $this->retry(function() use ($user) {
                $properties = [
                    'id' => $user->id,
                    'email' => $user->email,
                    'name' => $user->name,
                    'role' => $user->role,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'phone' => $user->phone,
                    'birth_date' => $user->birth_date?->toDateString(),
                    'street' => $user->street,
                    'street_number' => $user->street_number,
                    'postal_code' => $user->postal_code,
                    'city' => $user->city,
                    'country' => $user->country,
                    'created_at' => $user->created_at?->toISOString(),
                    'updated_at' => $user->updated_at?->toISOString(),
                ];
                
                $this->neo4j->createNode('User', $properties);
            });
            
            $count++;
        }
        
        Log::info("Synchronized {$count} users to Neo4j");
        return $count;
    }

    /**
     * Synchroniser les clubs
     */
    public function syncClubs(): int
    {
        $count = 0;
        $clubs = Club::all();
        
        foreach ($clubs as $club) {
            $this->retry(function() use ($club) {
                $properties = [
                    'id' => $club->id,
                    'name' => $club->name,
                    'description' => $club->description,
                    'address' => $club->address,
                    'city' => $club->city,
                    'postal_code' => $club->postal_code,
                    'phone' => $club->phone,
                    'email' => $club->email,
                    'website' => $club->website,
                    'created_at' => $club->created_at?->toISOString(),
                    'updated_at' => $club->updated_at?->toISOString(),
                ];
                
                $this->neo4j->createNode('Club', $properties);
            });
            
            $count++;
        }
        
        Log::info("Synchronized {$count} clubs to Neo4j");
        return $count;
    }

    /**
     * Synchroniser les enseignants
     */
    public function syncTeachers(): int
    {
        $count = 0;
        $teachers = Teacher::with(['user', 'specialties'])->get();
        
        foreach ($teachers as $teacher) {
            $this->retry(function() use ($teacher) {
                $properties = [
                    'id' => $teacher->id,
                    'user_id' => $teacher->user_id,
                    'bio' => $teacher->bio,
                    'experience_years' => $teacher->experience_years,
                    'hourly_rate' => $teacher->hourly_rate,
                    'availability' => $teacher->availability,
                    'certifications' => $teacher->certifications,
                    'created_at' => $teacher->created_at?->toISOString(),
                    'updated_at' => $teacher->updated_at?->toISOString(),
                ];
                
                // Ajouter les spécialités
                if ($teacher->specialties) {
                    $properties['specialties'] = $teacher->specialties->pluck('name')->toArray();
                }
                
                $this->neo4j->createNode('Teacher', $properties);
            });
            
            $count++;
        }
        
        Log::info("Synchronized {$count} teachers to Neo4j");
        return $count;
    }

    /**
     * Synchroniser les contrats
     */
    public function syncContracts(): int
    {
        $count = 0;
        $contracts = Contract::with(['teacher', 'club'])->get();
        
        foreach ($contracts as $contract) {
            $this->retry(function() use ($contract) {
                $properties = [
                    'id' => $contract->id,
                    'teacher_id' => $contract->teacher_id,
                    'club_id' => $contract->club_id,
                    'type' => $contract->type,
                    'start_date' => $contract->start_date?->toDateString(),
                    'end_date' => $contract->end_date?->toDateString(),
                    'hours_per_week' => $contract->hours_per_week,
                    'hourly_rate' => $contract->hourly_rate,
                    'status' => $contract->status,
                    'created_at' => $contract->created_at?->toISOString(),
                    'updated_at' => $contract->updated_at?->toISOString(),
                ];
                
                $this->neo4j->createNode('Contract', $properties);
            });
            
            $count++;
        }
        
        Log::info("Synchronized {$count} contracts to Neo4j");
        return $count;
    }

    /**
     * Créer les relations entre les entités
     */
    public function createRelationships(): int
    {
        $count = 0;
        
        // Relations User -> Club (MEMBERSHIP)
        $userClubs = DB::table('club_user')->get();
        foreach ($userClubs as $userClub) {
            $this->retry(function() use ($userClub) {
                $this->neo4j->run("
                    MATCH (u:User {id: \$user_id}), (c:Club {id: \$club_id})
                    MERGE (u)-[:MEMBERSHIP {created_at: \$created_at}]->(c)
                ", [
                    'user_id' => $userClub->user_id,
                    'club_id' => $userClub->club_id,
                    'created_at' => $userClub->created_at
                ]);
            });
            $count++;
        }
        
        // Relations Teacher -> User (IS_TEACHER)
        $teachers = Teacher::with('user')->get();
        foreach ($teachers as $teacher) {
            $this->retry(function() use ($teacher) {
                $this->neo4j->run("
                    MATCH (t:Teacher {id: \$teacher_id}), (u:User {id: \$user_id})
                    MERGE (t)-[:IS_TEACHER]->(u)
                ", [
                    'teacher_id' => $teacher->id,
                    'user_id' => $teacher->user_id
                ]);
            });
            $count++;
        }
        
        // Relations Contract -> Teacher (HAS_CONTRACT)
        $contracts = Contract::with('teacher')->get();
        foreach ($contracts as $contract) {
            $this->retry(function() use ($contract) {
                $this->neo4j->run("
                    MATCH (c:Contract {id: \$contract_id}), (t:Teacher {id: \$teacher_id})
                    MERGE (c)-[:HAS_CONTRACT]->(t)
                ", [
                    'contract_id' => $contract->id,
                    'teacher_id' => $contract->teacher_id
                ]);
            });
            $count++;
        }
        
        // Relations Contract -> Club (WORKING_FOR)
        $contracts = Contract::with('club')->get();
        foreach ($contracts as $contract) {
            $this->retry(function() use ($contract) {
                $this->neo4j->run("
                    MATCH (c:Contract {id: \$contract_id}), (cl:Club {id: \$club_id})
                    MERGE (c)-[:WORKING_FOR]->(cl)
                ", [
                    'contract_id' => $contract->id,
                    'club_id' => $contract->club_id
                ]);
            });
            $count++;
        }
        
        Log::info("Created {$count} relationships in Neo4j");
        return $count;
    }

    /**
     * Synchroniser un utilisateur spécifique
     */
    public function syncUser(User $user): void
    {
        $this->retry(function() use ($user) {
            $properties = [
                'id' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
                'role' => $user->role,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'phone' => $user->phone,
                'birth_date' => $user->birth_date?->toDateString(),
                'street' => $user->street,
                'street_number' => $user->street_number,
                'postal_code' => $user->postal_code,
                'city' => $user->city,
                'country' => $user->country,
                'updated_at' => now()->toISOString(),
            ];
            
            $this->neo4j->updateNode('User', 'id', $user->id, $properties);
        });
    }

    /**
     * Supprimer un utilisateur de Neo4j
     */
    public function deleteUser(int $userId): void
    {
        $this->retry(function() use ($userId) {
            $this->neo4j->deleteNode('User', 'id', $userId);
        });
    }

    /**
     * Exécuter une opération avec retry
     */
    protected function retry(callable $operation): void
    {
        $attempts = 0;
        
        while ($attempts < $this->retryAttempts) {
            try {
                $operation();
                return;
            } catch (\Exception $e) {
                $attempts++;
                
                if ($attempts >= $this->retryAttempts) {
                    throw $e;
                }
                
                Log::warning("Neo4j operation failed, retrying in {$this->retryDelay} seconds", [
                    'attempt' => $attempts,
                    'error' => $e->getMessage()
                ]);
                
                sleep($this->retryDelay);
            }
        }
    }

    /**
     * Obtenir des statistiques de synchronisation
     */
    public function getSyncStats(): array
    {
        $mysqlStats = [
            'users' => User::count(),
            'clubs' => Club::count(),
            'teachers' => Teacher::count(),
            'contracts' => Contract::count(),
        ];
        
        $neo4jStats = $this->neo4j->getStats();
        
        return [
            'mysql' => $mysqlStats,
            'neo4j' => $neo4jStats,
            'sync_status' => $this->calculateSyncStatus($mysqlStats, $neo4jStats)
        ];
    }

    /**
     * Calculer le statut de synchronisation
     */
    protected function calculateSyncStatus(array $mysqlStats, array $neo4jStats): array
    {
        $status = [];
        
        foreach ($mysqlStats as $entity => $mysqlCount) {
            $neo4jCount = $neo4jStats[$entity] ?? 0;
            $status[$entity] = [
                'mysql_count' => $mysqlCount,
                'neo4j_count' => $neo4jCount,
                'synced' => $mysqlCount === $neo4jCount,
                'percentage' => $mysqlCount > 0 ? round(($neo4jCount / $mysqlCount) * 100, 2) : 0
            ];
        }
        
        return $status;
    }
}
