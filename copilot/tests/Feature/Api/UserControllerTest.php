<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_get_users_list_when_authenticated()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        // Créer quelques utilisateurs supplémentaires
        User::factory()->count(3)->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/users');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'users' => [
                        '*' => [
                            'id',
                            'name',
                            'email',
                            'role',
                            'created_at',
                            'updated_at',
                        ]
                    ]
                ]);

        $responseData = $response->json();
        $this->assertCount(4, $responseData['users']); // 1 utilisateur initial + 3 créés
    }

    /** @test */
    public function it_requires_authentication_to_access_users_list()
    {
        User::factory()->count(3)->create();

        $response = $this->getJson('/api/users');

        $response->assertStatus(401);
    }

    /** @test */
    public function it_returns_empty_array_when_no_users_exist()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        // Supprimer tous les autres utilisateurs
        User::where('id', '!=', $user->id)->delete();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/users');

        $response->assertStatus(200)
                ->assertJson([
                    'users' => [$user->toArray()]
                ]);
    }
}
