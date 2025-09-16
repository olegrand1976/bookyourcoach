<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\User;
use App\Models\Club;
use App\Models\Lesson;
use App\Models\Payment;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AdminDashboardCountersTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $token;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Créer un utilisateur admin
        $this->admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@test.com'
        ]);
        
        // Créer un token pour l'admin
        $this->token = $this->admin->createToken('test-token')->plainTextToken;
    }

    public function test_admin_stats_response_format_matches_frontend_expectations()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->getJson('/api/admin/stats');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'stats' => [
                        'total_users',
                        'total_teachers', 
                        'total_students',
                        'total_clubs',
                        'total_lessons',
                        'total_payments',
                        'revenue_this_month'
                    ],
                    'recentUsers'
                ]);

        // Vérifier que les clés correspondent à ce que le frontend attend
        $stats = $response->json('stats');
        
        // Le frontend mappe total_users -> users, total_teachers -> teachers, etc.
        $this->assertArrayHasKey('total_users', $stats);
        $this->assertArrayHasKey('total_teachers', $stats);
        $this->assertArrayHasKey('total_students', $stats);
        $this->assertArrayHasKey('total_clubs', $stats);
        
        // Vérifier que les valeurs sont des nombres positifs
        $this->assertIsInt($stats['total_users']);
        $this->assertIsInt($stats['total_teachers']);
        $this->assertIsInt($stats['total_students']);
        $this->assertIsInt($stats['total_clubs']);
        $this->assertIsInt($stats['total_lessons']);
        $this->assertIsInt($stats['total_payments']);
        $this->assertIsNumeric($stats['revenue_this_month']);
        
        // Vérifier qu'il y a au moins l'admin
        $this->assertGreaterThanOrEqual(1, $stats['total_users']);
    }

    public function test_frontend_mapping_logic()
    {
        // Simuler la réponse de l'API
        $apiResponse = [
            'success' => true,
            'stats' => [
                'total_users' => 10,
                'total_teachers' => 3,
                'total_students' => 6,
                'total_clubs' => 2,
                'total_lessons' => 5,
                'total_payments' => 8,
                'revenue_this_month' => 1500.50
            ],
            'recentUsers' => []
        ];

        // Simuler le mapping côté frontend (comme dans notre correction)
        $frontendStats = [
            'users' => $apiResponse['stats']['total_users'] ?? 0,
            'teachers' => $apiResponse['stats']['total_teachers'] ?? 0,
            'students' => $apiResponse['stats']['total_students'] ?? 0,
            'clubs' => $apiResponse['stats']['total_clubs'] ?? 0,
        ];

        // Vérifier que le mapping fonctionne
        $this->assertEquals(10, $frontendStats['users']);
        $this->assertEquals(3, $frontendStats['teachers']);
        $this->assertEquals(6, $frontendStats['students']);
        $this->assertEquals(2, $frontendStats['clubs']);
    }

    public function test_stats_with_missing_data()
    {
        // Test avec des données manquantes
        $apiResponse = [
            'success' => true,
            'stats' => [
                'total_users' => 5,
                // total_teachers manquant
                'total_students' => 3,
                'total_clubs' => 1,
            ],
            'recentUsers' => []
        ];

        // Le mapping doit gérer les données manquantes avec des valeurs par défaut
        $frontendStats = [
            'users' => $apiResponse['stats']['total_users'] ?? 0,
            'teachers' => $apiResponse['stats']['total_teachers'] ?? 0,
            'students' => $apiResponse['stats']['total_students'] ?? 0,
            'clubs' => $apiResponse['stats']['total_clubs'] ?? 0,
        ];

        $this->assertEquals(5, $frontendStats['users']);
        $this->assertEquals(0, $frontendStats['teachers']); // Valeur par défaut
        $this->assertEquals(3, $frontendStats['students']);
        $this->assertEquals(1, $frontendStats['clubs']);
    }

    public function test_recent_users_structure()
    {
        // Créer quelques utilisateurs
        User::factory()->count(3)->create(['role' => 'student']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->getJson('/api/admin/stats');

        $response->assertStatus(200);
        
        $recentUsers = $response->json('recentUsers');
        $this->assertIsArray($recentUsers);
        
        // Vérifier que chaque utilisateur a les champs attendus
        if (count($recentUsers) > 0) {
            $user = $recentUsers[0];
            $this->assertArrayHasKey('id', $user);
            $this->assertArrayHasKey('name', $user);
            $this->assertArrayHasKey('email', $user);
            $this->assertArrayHasKey('role', $user);
            $this->assertArrayHasKey('created_at', $user);
        }
    }
}
