<?php

namespace Tests\Feature\Controllers;

use App\Models\Location;
use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;


class LocationControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_list_locations()
    {
        $user = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($user);

        Location::factory()->count(3)->create();

        $response = $this->getJson('/api/locations');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'address',
                        'city',
                        'postal_code',
                        'country',
                        'latitude',
                        'longitude',
                        'facilities',
                        'created_at',
                        'updated_at'
                    ]
                ]
            ]);
    }

    #[Test]
    public function it_can_show_a_location()
    {
        $user = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($user);

        $location = Location::factory()->create();

        $response = $this->getJson("/api/locations/{$location->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $location->id,
                    'name' => $location->name,
                    'address' => $location->address,
                    'city' => $location->city,
                    'postal_code' => $location->postal_code,
                    'country' => $location->country,
                ]
            ]);
    }

    #[Test]
    public function it_can_create_a_location_as_admin()
    {
        $user = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($user);

        $locationData = [
            'name' => 'Centre Équestre Test',
            'address' => '123 Rue Test',
            'city' => 'Bruxelles',
            'postal_code' => '1000',
            'country' => 'Belgique',
            'latitude' => 50.8503,
            'longitude' => 4.3517,
            'facilities' => ['carrière', 'manège', 'parking']
        ];

        $response = $this->postJson('/api/locations', $locationData);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'name' => 'Centre Équestre Test',
                    'address' => '123 Rue Test',
                    'city' => 'Bruxelles',
                    'postal_code' => '1000',
                    'country' => 'Belgique',
                    'latitude' => 50.8503,
                    'longitude' => 4.3517,
                    'facilities' => ['carrière', 'manège', 'parking']
                ]
            ]);

        $this->assertDatabaseHas('locations', [
            'name' => 'Centre Équestre Test',
            'address' => '123 Rue Test',
            'city' => 'Bruxelles',
            'postal_code' => '1000',
            'country' => 'Belgique',
        ]);
    }

    #[Test]
    public function it_cannot_create_a_location_as_student()
    {
        $user = User::factory()->create(['role' => 'student']);
        Sanctum::actingAs($user);

        $locationData = [
            'name' => 'Centre Équestre Test',
            'address' => '123 Rue Test',
            'city' => 'Bruxelles',
            'postal_code' => '1000',
            'country' => 'Belgique',
        ];

        $response = $this->postJson('/api/locations', $locationData);

        $response->assertStatus(403);
    }

    #[Test]
    public function it_can_update_a_location_as_admin()
    {
        $user = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($user);

        $location = Location::factory()->create();

        $updateData = [
            'name' => 'Nom mis à jour',
            'address' => 'Adresse mise à jour',
            'city' => 'Ville mise à jour',
            'postal_code' => '9999',
            'country' => 'France',
            'latitude' => 48.8566,
            'longitude' => 2.3522,
            'facilities' => ['manège couvert', 'paddock']
        ];

        $response = $this->putJson("/api/locations/{$location->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $location->id,
                    'name' => 'Nom mis à jour',
                    'address' => 'Adresse mise à jour',
                    'city' => 'Ville mise à jour',
                    'postal_code' => '9999',
                    'country' => 'France',
                    'latitude' => 48.8566,
                    'longitude' => 2.3522,
                    'facilities' => ['manège couvert', 'paddock']
                ]
            ]);
    }

    #[Test]
    public function it_can_delete_a_location_as_admin()
    {
        $user = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($user);

        $location = Location::factory()->create();

        $response = $this->deleteJson("/api/locations/{$location->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('locations', ['id' => $location->id]);
    }

    #[Test]
    public function it_validates_required_fields_when_creating()
    {
        $user = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/locations', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'address', 'city', 'postal_code', 'country']);
    }

    #[Test]
    public function it_validates_latitude_longitude_range()
    {
        $user = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/locations', [
            'name' => 'Test',
            'address' => 'Test',
            'city' => 'Test',
            'postal_code' => '1000',
            'country' => 'Test',
            'latitude' => 91, // Invalid latitude
            'longitude' => 181, // Invalid longitude
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['latitude', 'longitude']);
    }

    #[Test]
    public function it_validates_facilities_array()
    {
        $user = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/locations', [
            'name' => 'Test',
            'address' => 'Test',
            'city' => 'Test',
            'postal_code' => '1000',
            'country' => 'Test',
            'facilities' => 'not-an-array', // Invalid facilities
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['facilities']);
    }

    #[Test]
    public function it_returns_404_for_non_existent_location()
    {
        $user = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/locations/999');

        $response->assertStatus(404);
    }

    #[Test]
    public function guest_cannot_access_locations()
    {
        $response = $this->getJson('/api/locations');

        $response->assertStatus(401);
    }

    #[Test]
    public function teacher_can_read_locations_but_not_modify()
    {
        $user = User::factory()->create(['role' => 'teacher']);
        Sanctum::actingAs($user);

        $location = Location::factory()->create();

        // Can read
        $response = $this->getJson('/api/locations');
        $response->assertStatus(200);

        $response = $this->getJson("/api/locations/{$location->id}");
        $response->assertStatus(200);

        // Cannot modify
        $response = $this->postJson('/api/locations', [
            'name' => 'Test',
            'address' => 'Test',
            'city' => 'Test',
            'postal_code' => '1000',
            'country' => 'Test',
        ]);
        $response->assertStatus(403);

        $response = $this->putJson("/api/locations/{$location->id}", [
            'name' => 'Updated',
        ]);
        $response->assertStatus(403);

        $response = $this->deleteJson("/api/locations/{$location->id}");
        $response->assertStatus(403);
    }
}
