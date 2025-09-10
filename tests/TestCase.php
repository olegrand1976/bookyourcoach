<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use App\Models\User;
use Tests\CreatesApplication;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication, RefreshDatabase;

    /**
     * Authentifie un utilisateur admin et retourne l'instance.
     */
    protected function actingAsAdmin(): User
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'status' => 'active',
            'is_active' => true,
        ]);

        // Créer un token Sanctum pour l'admin
        $token = $admin->createToken('test-token')->plainTextToken;
        
        // Définir l'en-tête Authorization pour le middleware admin
        $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ]);

        return $admin;
    }
}
