<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Payment;
use App\Models\Lesson;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_get_dashboard_stats_as_admin()
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)
            ->getJson('/api/admin/dashboard');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'stats' => [
                        'total_users',
                        'total_teachers', 
                        'total_students'
                    ]
                ]
            ]);
    }

    /** @test */
    public function it_cannot_access_dashboard_as_non_admin()
    {
        $user = User::factory()->create(['role' => User::ROLE_STUDENT]);

        $response = $this->actingAs($user)
            ->getJson('/api/admin/dashboard');

        $response->assertStatus(403);
    }

    /** @test */
    public function it_requires_authentication_for_dashboard()
    {
        $response = $this->getJson('/api/admin/dashboard');

        $response->assertStatus(401);
    }

    /** @test */
    public function it_can_get_users_list_as_admin()
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        User::factory()->count(5)->create();

        $response = $this->actingAs($admin)
            ->getJson('/api/admin/users');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'email',
                        'role',
                        'is_active',
                        'created_at'
                    ]
                ]
            ]);
    }

    /** @test */
    public function it_can_update_user_status_as_admin()
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $user = User::factory()->create(['status' => 'active']);

        $response = $this->actingAs($admin)
            ->putJson("/api/admin/users/{$user->id}/status", [
                'status' => 'inactive'
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Statut utilisateur mis à jour'
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'status' => 'inactive'
        ]);
    }

    /** @test */
    public function it_cannot_update_user_status_as_non_admin()
    {
        $user = User::factory()->create(['role' => User::ROLE_STUDENT]);
        $targetUser = User::factory()->create();

        $response = $this->actingAs($user)
            ->putJson("/api/admin/users/{$targetUser->id}/status", [
                'status' => 'inactive'
            ]);

        $response->assertStatus(403);
    }
}
