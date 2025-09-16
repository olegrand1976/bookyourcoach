<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AdminUsersFiltersTest extends TestCase
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

    public function test_admin_can_get_users_without_filters()
    {
        // Créer des utilisateurs de test
        User::factory()->count(3)->create(['role' => 'teacher']);
        User::factory()->count(2)->create(['role' => 'student']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->getJson('/api/admin/users');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data',
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                    'from',
                    'to'
                ]);

        // Vérifier qu'on a bien tous les utilisateurs (1 admin + 3 teachers + 2 students = 6)
        $this->assertEquals(6, $response->json('total'));
        $this->assertCount(6, $response->json('data'));
    }

    public function test_admin_can_filter_users_by_role()
    {
        // Créer des utilisateurs de test
        User::factory()->count(2)->create(['role' => 'teacher']);
        User::factory()->count(3)->create(['role' => 'student']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->getJson('/api/admin/users?role=teacher');

        $response->assertStatus(200);
        
        $users = $response->json('data');
        $this->assertCount(2, $users);
        
        // Vérifier que tous les utilisateurs retournés sont des enseignants
        foreach ($users as $user) {
            $this->assertEquals('teacher', $user['role']);
        }
    }

    public function test_admin_can_filter_users_by_status()
    {
        // Créer des utilisateurs avec différents statuts
        User::factory()->create(['role' => 'student', 'is_active' => true]);
        User::factory()->create(['role' => 'student', 'is_active' => false]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->getJson('/api/admin/users?status=active');

        $response->assertStatus(200);
        
        $users = $response->json('data');
        // Devrait inclure l'admin (actif) + 1 étudiant actif = 2
        $this->assertGreaterThanOrEqual(2, count($users));
        
        // Vérifier que tous les utilisateurs retournés sont actifs
        foreach ($users as $user) {
            $this->assertTrue($user['is_active']);
        }
    }

    public function test_admin_can_filter_users_by_postal_code()
    {
        // Créer des utilisateurs avec différents codes postaux
        User::factory()->create(['role' => 'student', 'postal_code' => '1000']);
        User::factory()->create(['role' => 'student', 'postal_code' => '2000']);
        User::factory()->create(['role' => 'student', 'postal_code' => '1000']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->getJson('/api/admin/users?postal_code=1000');

        $response->assertStatus(200);
        
        $users = $response->json('data');
        $this->assertCount(2, $users);
        
        // Vérifier que tous les utilisateurs retournés ont le bon code postal
        foreach ($users as $user) {
            $this->assertEquals('1000', $user['postal_code']);
        }
    }

    public function test_admin_can_search_users_by_name()
    {
        // Créer des utilisateurs avec des noms spécifiques
        User::factory()->create(['role' => 'student', 'name' => 'Jean Dupont']);
        User::factory()->create(['role' => 'student', 'name' => 'Marie Martin']);
        User::factory()->create(['role' => 'student', 'name' => 'Pierre Dupont']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->getJson('/api/admin/users?search=Dupont');

        $response->assertStatus(200);
        
        $users = $response->json('data');
        $this->assertCount(2, $users);
        
        // Vérifier que tous les utilisateurs retournés contiennent "Dupont"
        foreach ($users as $user) {
            $this->assertStringContainsString('Dupont', $user['name']);
        }
    }

    public function test_admin_can_search_users_by_email()
    {
        // Créer des utilisateurs avec des emails spécifiques
        User::factory()->create(['role' => 'student', 'email' => 'test@example.com']);
        User::factory()->create(['role' => 'student', 'email' => 'admin@example.org']);
        User::factory()->create(['role' => 'student', 'email' => 'user@test.com']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->getJson('/api/admin/users?search=example');

        $response->assertStatus(200);
        
        $users = $response->json('data');
        $this->assertCount(2, $users);
        
        // Vérifier que tous les utilisateurs retournés contiennent "example"
        foreach ($users as $user) {
            $this->assertStringContainsString('example', $user['email']);
        }
    }

    public function test_admin_users_pagination()
    {
        // Créer plus d'utilisateurs que la limite par page
        User::factory()->count(15)->create(['role' => 'student']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->getJson('/api/admin/users?per_page=5&page=1');

        $response->assertStatus(200);
        
        $data = $response->json();
        $this->assertEquals(1, $data['current_page']);
        $this->assertEquals(5, $data['per_page']);
        $this->assertEquals(16, $data['total']); // 1 admin + 15 students
        $this->assertCount(5, $data['data']);
    }

    public function test_non_admin_cannot_access_users()
    {
        $user = User::factory()->create(['role' => 'student']);
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json'
        ])->getJson('/api/admin/users');

        $response->assertStatus(403)
                ->assertJson([
                    'message' => 'Access denied - Admin rights required'
                ]);
    }

    public function test_users_without_token()
    {
        $response = $this->getJson('/api/admin/users');

        $response->assertStatus(401)
                ->assertJson([
                    'message' => 'Missing token'
                ]);
    }

    public function test_users_with_invalid_token()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer invalid-token',
            'Accept' => 'application/json'
        ])->getJson('/api/admin/users');

        $response->assertStatus(401)
                ->assertJson([
                    'message' => 'Invalid token'
                ]);
    }

    public function test_combined_filters()
    {
        // Créer des utilisateurs avec des critères spécifiques
        User::factory()->create([
            'role' => 'teacher', 
            'postal_code' => '1000', 
            'name' => 'Jean Teacher'
        ]);
        User::factory()->create([
            'role' => 'teacher', 
            'postal_code' => '2000', 
            'name' => 'Marie Teacher'
        ]);
        User::factory()->create([
            'role' => 'student', 
            'postal_code' => '1000', 
            'name' => 'Pierre Student'
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->getJson('/api/admin/users?role=teacher&postal_code=1000');

        $response->assertStatus(200);
        
        $users = $response->json('data');
        $this->assertCount(1, $users);
        
        $user = $users[0];
        $this->assertEquals('teacher', $user['role']);
        $this->assertEquals('1000', $user['postal_code']);
    }
}
