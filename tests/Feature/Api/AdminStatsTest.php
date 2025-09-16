<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\User;
use App\Models\Club;
use App\Models\Lesson;
use App\Models\Payment;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AdminStatsTest extends TestCase
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

    public function test_admin_can_get_stats()
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

        // Vérifier que les stats sont des nombres
        $stats = $response->json('stats');
        $this->assertIsInt($stats['total_users']);
        $this->assertIsInt($stats['total_teachers']);
        $this->assertIsInt($stats['total_students']);
        $this->assertIsInt($stats['total_clubs']);
        $this->assertIsInt($stats['total_lessons']);
        $this->assertIsInt($stats['total_payments']);
        $this->assertIsNumeric($stats['revenue_this_month']);

        // Vérifier que recentUsers est un tableau
        $this->assertIsArray($response->json('recentUsers'));
    }

    public function test_non_admin_cannot_get_stats()
    {
        $user = User::factory()->create(['role' => 'student']);
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json'
        ])->getJson('/api/admin/stats');

        $response->assertStatus(403)
                ->assertJson([
                    'message' => 'Access denied - Admin rights required'
                ]);
    }

    public function test_stats_without_token()
    {
        $response = $this->getJson('/api/admin/stats');

        $response->assertStatus(401)
                ->assertJson([
                    'message' => 'Missing token'
                ]);
    }

    public function test_stats_with_invalid_token()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer invalid-token',
            'Accept' => 'application/json'
        ])->getJson('/api/admin/stats');

        $response->assertStatus(401)
                ->assertJson([
                    'message' => 'Invalid token'
                ]);
    }

    public function test_stats_empty_database()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->getJson('/api/admin/stats');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'stats' => [
                        'total_users' => 1, // Seulement l'admin
                        'total_teachers' => 0,
                        'total_students' => 0,
                        'total_clubs' => 0,
                        'total_lessons' => 0,
                        'total_payments' => 0,
                        'revenue_this_month' => 0
                    ]
                ]);

        // Vérifier que recentUsers contient au moins l'admin
        $recentUsers = $response->json('recentUsers');
        $this->assertCount(1, $recentUsers);
        $this->assertEquals('admin@test.com', $recentUsers[0]['email']);
    }
}
