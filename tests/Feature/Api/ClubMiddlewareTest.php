<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Club;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;


class ClubMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_allows_club_user_to_access_club_routes()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club = Club::factory()->create();
        
        $club->users()->attach($clubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        $response = $this->actingAs($clubUser)
            ->getJson('/api/club/dashboard');

        $response->assertStatus(200);
    }

    #[Test]
    public function it_allows_admin_user_to_access_club_routes()
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $club = Club::factory()->create();
        
        $club->users()->attach($admin->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        $response = $this->actingAs($admin)
            ->getJson('/api/club/dashboard');

        $response->assertStatus(200);
    }

    #[Test]
    public function it_denies_student_user_from_accessing_club_routes()
    {
        $student = User::factory()->create(['role' => User::ROLE_STUDENT]);

        $response = $this->actingAs($student)
            ->getJson('/api/club/dashboard');

        $response->assertStatus(403);
    }

    #[Test]
    public function it_denies_teacher_user_from_accessing_club_routes()
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);

        $response = $this->actingAs($teacher)
            ->getJson('/api/club/dashboard');

        $response->assertStatus(403);
    }

    #[Test]
    public function it_denies_club_user_without_club_association()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        // Ne pas associer l'utilisateur à un club

        $response = $this->actingAs($clubUser)
            ->getJson('/api/club/dashboard');

        $response->assertStatus(403);
    }

    #[Test]
    public function it_denies_admin_user_without_club_association()
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        // Ne pas associer l'admin à un club

        $response = $this->actingAs($admin)
            ->getJson('/api/club/dashboard');

        $response->assertStatus(403);
    }

    #[Test]
    public function it_denies_unauthenticated_user_from_accessing_club_routes()
    {
        $response = $this->getJson('/api/club/dashboard');

        $response->assertStatus(401);
    }

    #[Test]
    public function it_applies_middleware_to_all_club_routes()
    {
        $student = User::factory()->create(['role' => User::ROLE_STUDENT]);

        $routes = [
            '/api/club/dashboard',
            '/api/club/teachers',
            '/api/club/students',
            '/api/club/profile'
        ];

        foreach ($routes as $route) {
            $response = $this->actingAs($student)->getJson($route);
            $response->assertStatus(403);
        }
    }

    #[Test]
    public function it_allows_club_user_with_multiple_club_associations()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club1 = Club::factory()->create();
        $club2 = Club::factory()->create();
        
        $club1->users()->attach($clubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);
        $club2->users()->attach($clubUser->id, [
            'role' => 'manager',
            'is_admin' => false,
            'joined_at' => now()
        ]);

        $response = $this->actingAs($clubUser)
            ->getJson('/api/club/dashboard');

        $response->assertStatus(200);
    }

    #[Test]
    public function it_allows_club_user_with_different_roles_in_clubs()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club = Club::factory()->create();
        
        $roles = ['owner', 'manager', 'member'];
        
        foreach ($roles as $role) {
            $club->users()->detach($clubUser->id);
            $club->users()->attach($clubUser->id, [
                'role' => $role,
                'is_admin' => $role === 'owner',
                'joined_at' => now()
            ]);

            $response = $this->actingAs($clubUser)
                ->getJson('/api/club/dashboard');

            $response->assertStatus(200, "Failed for role: {$role}");
        }
    }

    #[Test]
    public function it_handles_post_requests_with_middleware()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club = Club::factory()->create();
        
        $club->users()->attach($clubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);

        $response = $this->actingAs($clubUser)
            ->postJson('/api/club/teachers', [
                'email' => $teacher->email
            ]);

        $response->assertStatus(200);
    }

    #[Test]
    public function it_handles_put_requests_with_middleware()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club = Club::factory()->create();
        
        $club->users()->attach($clubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        $response = $this->actingAs($clubUser)
            ->putJson('/api/club/profile', [
                'name' => 'Nouveau nom du club'
            ]);

        $response->assertStatus(200);
    }

    #[Test]
    public function it_returns_proper_error_messages()
    {
        $student = User::factory()->create(['role' => User::ROLE_STUDENT]);

        $response = $this->actingAs($student)
            ->getJson('/api/club/dashboard');

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Unauthorized. User does not have club privileges.'
            ]);
    }

    #[Test]
    public function it_returns_proper_error_message_for_unauthenticated_user()
    {
        $response = $this->getJson('/api/club/dashboard');

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.'
            ]);
    }

    #[Test]
    public function it_returns_proper_error_message_for_user_without_club_association()
    {
        $clubUser = User::factory()->create(['role' => 'club']);

        $response = $this->actingAs($clubUser)
            ->getJson('/api/club/dashboard');

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'User is not associated with any club.'
            ]);
    }
}
