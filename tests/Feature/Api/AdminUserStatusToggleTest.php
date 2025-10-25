<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AdminUserStatusToggleTest extends TestCase
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

    public function test_admin_can_toggle_user_status_to_inactive()
    {
        // Créer un utilisateur à désactiver
        $user = User::factory()->create([
            'role' => 'student',
            'is_active' => true
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ])->putJson("/api/admin/users/{$user->id}/status", [
            'is_active' => false
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Statut utilisateur mis à jour'
                ]);

        // Vérifier que l'utilisateur est bien désactivé
        $user->refresh();
        $this->assertFalse($user->is_active);
    }

    public function test_admin_can_toggle_user_status_to_active()
    {
        // Créer un utilisateur à activer
        $user = User::factory()->create([
            'role' => 'student',
            'is_active' => false
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ])->putJson("/api/admin/users/{$user->id}/status", [
            'is_active' => true
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Statut utilisateur mis à jour'
                ]);

        // Vérifier que l'utilisateur est bien activé
        $user->refresh();
        $this->assertTrue($user->is_active);
    }

    public function test_non_admin_cannot_toggle_user_status()
    {
        $user = User::factory()->create(['role' => 'student']);
        $token = $user->createToken('test-token')->plainTextToken;

        $targetUser = User::factory()->create(['role' => 'student']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ])->putJson("/api/admin/users/{$targetUser->id}/status", [
            'is_active' => false
        ]);

        $response->assertStatus(403)
                ->assertJson([
                    'message' => 'Access denied - Admin rights required'
                ]);
    }

    public function test_user_status_toggle_without_token()
    {
        $user = User::factory()->create(['role' => 'student']);

        $response = $this->putJson("/api/admin/users/{$user->id}/status", [
            'is_active' => false
        ]);

        $response->assertStatus(401)
                ->assertJson([
                    'message' => 'Missing token'
                ]);
    }

    public function test_user_status_toggle_with_invalid_token()
    {
        $user = User::factory()->create(['role' => 'student']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer invalid-token',
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ])->putJson("/api/admin/users/{$user->id}/status", [
            'is_active' => false
        ]);

        $response->assertStatus(401)
                ->assertJson([
                    'message' => 'Invalid token'
                ]);
    }

    public function test_user_status_toggle_nonexistent_user()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ])->putJson('/api/admin/users/99999/status', [
            'is_active' => false
        ]);

        $response->assertStatus(404);
    }

    public function test_user_status_toggle_missing_is_active_field()
    {
        $user = User::factory()->create(['role' => 'student']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ])->putJson("/api/admin/users/{$user->id}/status", []);

        // La route devrait retourner une erreur de validation
        $response->assertStatus(422)
                ->assertJsonValidationErrors('is_active');
    }
}
