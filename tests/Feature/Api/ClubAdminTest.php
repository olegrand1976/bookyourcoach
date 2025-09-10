<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Club;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;


class ClubAdminTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_get_clubs_list_as_admin()
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        Club::factory()->count(5)->create();

        $response = $this->actingAs($admin)
            ->getJson('/api/admin/clubs');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'description',
                        'address',
                        'phone',
                        'email',
                        'max_students',
                        'subscription_price',
                        'is_active',
                        'created_at',
                        'updated_at'
                    ]
                ]
            ]);
    }

    #[Test]
    public function it_can_create_club_as_admin()
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $clubData = [
            'name' => 'Nouveau Club',
            'description' => 'Description du nouveau club',
            'address' => '123 Rue Nouvelle',
            'phone' => '01 23 45 67 89',
            'email' => 'contact@nouveau-club.fr',
            'max_students' => 100,
            'subscription_price' => 150.00,
            'is_active' => true
        ];

        $response = $this->actingAs($admin)
            ->postJson('/api/admin/clubs', $clubData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'name',
                    'description',
                    'address',
                    'phone',
                    'email',
                    'max_students',
                    'subscription_price',
                    'is_active'
                ],
                'message'
            ]);

        $this->assertDatabaseHas('clubs', [
            'name' => 'Nouveau Club',
            'email' => 'contact@nouveau-club.fr'
        ]);
    }

    #[Test]
    public function it_can_get_specific_club_as_admin()
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $club = Club::factory()->create();

        $response = $this->actingAs($admin)
            ->getJson("/api/admin/clubs/{$club->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'name',
                    'description',
                    'address',
                    'phone',
                    'email',
                    'max_students',
                    'subscription_price',
                    'is_active',
                    'created_at',
                    'updated_at'
                ]
            ]);
    }

    #[Test]
    public function it_can_update_club_as_admin()
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $club = Club::factory()->create();

        $updateData = [
            'name' => 'Club Modifié',
            'description' => 'Description modifiée',
            'address' => 'Nouvelle adresse',
            'phone' => '01 98 76 54 32',
            'email' => 'nouveau@club.fr',
            'max_students' => 150,
            'subscription_price' => 200.00,
            'is_active' => false
        ];

        $response = $this->actingAs($admin)
            ->putJson("/api/admin/clubs/{$club->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Club mis à jour avec succès'
            ]);

        $this->assertDatabaseHas('clubs', [
            'id' => $club->id,
            'name' => 'Club Modifié',
            'description' => 'Description modifiée',
            'address' => 'Nouvelle adresse',
            'phone' => '01 98 76 54 32',
            'email' => 'nouveau@club.fr',
            'max_students' => 150,
            'subscription_price' => 200.00,
            'is_active' => false
        ]);
    }

    #[Test]
    public function it_can_delete_club_as_admin()
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $club = Club::factory()->create();

        $response = $this->actingAs($admin)
            ->deleteJson("/api/admin/clubs/{$club->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Club supprimé avec succès'
            ]);

        $this->assertDatabaseMissing('clubs', [
            'id' => $club->id
        ]);
    }

    #[Test]
    public function it_can_toggle_club_status_as_admin()
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $club = Club::factory()->create(['is_active' => true]);

        $response = $this->actingAs($admin)
            ->putJson("/api/admin/clubs/{$club->id}/toggle-status");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Statut du club mis à jour avec succès'
            ]);

        $this->assertDatabaseHas('clubs', [
            'id' => $club->id,
            'is_active' => false
        ]);

        // Toggle back
        $response = $this->actingAs($admin)
            ->putJson("/api/admin/clubs/{$club->id}/toggle-status");

        $response->assertStatus(200);

        $this->assertDatabaseHas('clubs', [
            'id' => $club->id,
            'is_active' => true
        ]);
    }

    #[Test]
    public function it_validates_club_creation_data()
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)
            ->postJson('/api/admin/clubs', [
                'name' => '', // Nom requis
                'email' => 'invalid-email', // Email invalide
                'max_students' => -1, // Doit être positif
                'subscription_price' => -50.00 // Doit être positif
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'max_students', 'subscription_price']);
    }

    #[Test]
    public function it_validates_club_update_data()
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $club = Club::factory()->create();

        $response = $this->actingAs($admin)
            ->putJson("/api/admin/clubs/{$club->id}", [
                'name' => '', // Nom requis
                'email' => 'invalid-email', // Email invalide
                'max_students' => -1, // Doit être positif
                'subscription_price' => -50.00 // Doit être positif
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'max_students', 'subscription_price']);
    }

    #[Test]
    public function it_handles_nonexistent_club()
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)
            ->getJson('/api/admin/clubs/999999');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Club non trouvé'
            ]);
    }

    #[Test]
    public function it_requires_admin_role_for_club_management()
    {
        $user = User::factory()->create(['role' => User::ROLE_STUDENT]);

        $response = $this->actingAs($user)
            ->getJson('/api/admin/clubs');

        $response->assertStatus(403);

        $response = $this->actingAs($user)
            ->postJson('/api/admin/clubs', [
                'name' => 'Test Club'
            ]);

        $response->assertStatus(403);

        $club = Club::factory()->create();
        $response = $this->actingAs($user)
            ->putJson("/api/admin/clubs/{$club->id}", [
                'name' => 'Modified Club'
            ]);

        $response->assertStatus(403);

        $response = $this->actingAs($user)
            ->deleteJson("/api/admin/clubs/{$club->id}");

        $response->assertStatus(403);
    }

    #[Test]
    public function it_requires_authentication_for_admin_club_routes()
    {
        $response = $this->getJson('/api/admin/clubs');
        $response->assertStatus(401);

        $response = $this->postJson('/api/admin/clubs', [
            'name' => 'Test Club'
        ]);
        $response->assertStatus(401);

        $club = Club::factory()->create();
        $response = $this->putJson("/api/admin/clubs/{$club->id}", [
            'name' => 'Modified Club'
        ]);
        $response->assertStatus(401);

        $response = $this->deleteJson("/api/admin/clubs/{$club->id}");
        $response->assertStatus(401);
    }

    #[Test]
    public function it_can_paginate_clubs_list()
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        Club::factory()->count(25)->create();

        $response = $this->actingAs($admin)
            ->getJson('/api/admin/clubs?page=1&per_page=10');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'description',
                        'address',
                        'phone',
                        'email',
                        'max_students',
                        'subscription_price',
                        'is_active'
                    ]
                ],
                'links',
                'meta' => [
                    'current_page',
                    'per_page',
                    'total'
                ]
            ]);

        $data = $response->json();
        $this->assertCount(10, $data['data']);
        $this->assertEquals(1, $data['meta']['current_page']);
        $this->assertEquals(10, $data['meta']['per_page']);
        $this->assertEquals(25, $data['meta']['total']);
    }

    #[Test]
    public function it_can_filter_clubs_by_status()
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        Club::factory()->count(5)->create(['is_active' => true]);
        Club::factory()->count(3)->create(['is_active' => false]);

        $response = $this->actingAs($admin)
            ->getJson('/api/admin/clubs?status=active');

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertCount(5, $data['data']);

        $response = $this->actingAs($admin)
            ->getJson('/api/admin/clubs?status=inactive');

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertCount(3, $data['data']);
    }

    #[Test]
    public function it_can_search_clubs_by_name()
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        Club::factory()->create(['name' => 'Club de Paris']);
        Club::factory()->create(['name' => 'Club de Lyon']);
        Club::factory()->create(['name' => 'Club de Marseille']);

        $response = $this->actingAs($admin)
            ->getJson('/api/admin/clubs?search=Paris');

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertCount(1, $data['data']);
        $this->assertEquals('Club de Paris', $data['data'][0]['name']);
    }

    #[Test]
    public function it_can_get_club_statistics()
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $club = Club::factory()->create();
        
        // Ajouter des utilisateurs au club
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $student = User::factory()->create(['role' => User::ROLE_STUDENT]);
        
        $club->users()->attach($teacher->id, [
            'role' => 'teacher',
            'is_admin' => false,
            'joined_at' => now()
        ]);
        $club->users()->attach($student->id, [
            'role' => 'student',
            'is_admin' => false,
            'joined_at' => now()
        ]);

        $response = $this->actingAs($admin)
            ->getJson("/api/admin/clubs/{$club->id}/stats");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total_members',
                    'total_teachers',
                    'total_students',
                    'occupancy_rate',
                    'created_at',
                    'last_activity'
                ]
            ]);
    }
}
