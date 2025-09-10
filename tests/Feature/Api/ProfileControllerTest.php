<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Profile;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;


class ProfileControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_get_profiles_list_when_authenticated()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        // Créer quelques profils
        Profile::factory()->count(3)->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/profiles');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'profiles' => [
                    '*' => [
                        'id',
                        'user_id',
                        'first_name',
                        'last_name',
                        'phone',
                        'created_at',
                        'updated_at',
                    ]
                ]
            ]);
    }

    #[Test]
    public function it_can_create_a_profile()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $profileData = [
            'first_name' => 'Jean',
            'last_name' => 'Dupont',
            'phone' => '+33123456789',
            'address' => '123 Rue de la Paix',
            'city' => 'Paris',
            'postal_code' => '75001',
            'country' => 'France',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/profiles', $profileData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'profile' => [
                    'id',
                    'user_id',
                    'first_name',
                    'last_name',
                    'phone',
                    'address',
                    'city',
                    'postal_code',
                    'country',
                    'created_at',
                    'updated_at',
                ]
            ]);

        $this->assertDatabaseHas('profiles', [
            'user_id' => $user->id,
            'first_name' => 'Jean',
            'last_name' => 'Dupont',
            'phone' => '+33123456789',
        ]);
    }

    #[Test]
    public function it_validates_required_fields_when_creating_profile()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/profiles', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['first_name', 'last_name']);
    }

    #[Test]
    public function it_can_show_a_specific_profile()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $profile = Profile::factory()->create([
            'user_id' => $user->id,
            'first_name' => 'Jean',
            'last_name' => 'Dupont',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson("/api/profiles/{$profile->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'profile' => [
                    'id',
                    'user_id',
                    'first_name',
                    'last_name',
                    'user',
                ]
            ])
            ->assertJson([
                'profile' => [
                    'id' => $profile->id,
                    'first_name' => 'Jean',
                    'last_name' => 'Dupont',
                ]
            ]);
    }

    #[Test]
    public function it_can_update_a_profile()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $profile = Profile::factory()->create([
            'user_id' => $user->id,
            'first_name' => 'Jean',
            'last_name' => 'Dupont',
        ]);

        $updateData = [
            'first_name' => 'Pierre',
            'last_name' => 'Martin',
            'phone' => '+33987654321',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson("/api/profiles/{$profile->id}", $updateData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'profile'
            ])
            ->assertJson([
                'message' => 'Profile updated successfully',
                'profile' => [
                    'first_name' => 'Pierre',
                    'last_name' => 'Martin',
                    'phone' => '+33987654321',
                ]
            ]);

        $this->assertDatabaseHas('profiles', [
            'id' => $profile->id,
            'first_name' => 'Pierre',
            'last_name' => 'Martin',
            'phone' => '+33987654321',
        ]);
    }

    #[Test]
    public function it_can_delete_a_profile()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $profile = Profile::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->deleteJson("/api/profiles/{$profile->id}");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Profile deleted successfully'
            ]);

        $this->assertDatabaseMissing('profiles', [
            'id' => $profile->id,
        ]);
    }

    #[Test]
    public function it_requires_authentication_to_access_profiles()
    {
        $response = $this->getJson('/api/profiles');
        $response->assertStatus(401);

        $response = $this->postJson('/api/profiles', []);
        $response->assertStatus(401);
    }
}
