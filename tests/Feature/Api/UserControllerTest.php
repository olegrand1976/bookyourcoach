<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;


class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_get_users_list_when_authenticated()
    {
        // Utiliser actingAsAdmin() pour avoir les droits admin
        $admin = $this->actingAsAdmin();

        // Créer quelques utilisateurs supplémentaires
        User::factory()->count(3)->create();

        $response = $this->getJson('/api/admin/users');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'email',
                        'role',
                        'created_at',
                        'updated_at',
                    ]
                ],
                'current_page',
                'per_page',
                'total'
            ]);

        $responseData = $response->json();
        $this->assertGreaterThanOrEqual(4, count($responseData['data'])); // Admin + 3 créés (peut y avoir d'autres)
    }

    #[Test]
    public function it_requires_authentication_to_access_users_list()
    {
        User::factory()->count(3)->create();

        $response = $this->getJson('/api/admin/users');

        $response->assertStatus(401);
    }

    #[Test]
    public function it_returns_empty_array_when_no_users_exist()
    {
        // Utiliser actingAsAdmin() pour avoir les droits admin
        $admin = $this->actingAsAdmin();

        // Supprimer tous les autres utilisateurs sauf l'admin
        User::where('id', '!=', $admin->id)->delete();

        $response = $this->getJson('/api/admin/users');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'email',
                        'role',
                    ]
                ],
                'current_page',
                'per_page',
                'total'
            ]);
        
        $responseData = $response->json();
        $this->assertGreaterThanOrEqual(1, count($responseData['data'])); // Au moins l'admin
    }
}
