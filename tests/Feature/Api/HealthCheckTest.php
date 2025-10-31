<?php

namespace Tests\Feature\Api;

use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    /**
     * Test du health check endpoint
     */
    public function test_health_check_returns_ok()
    {
        // Act
        $response = $this->getJson('/api/health');

        // Assert
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'status',
                     'timestamp',
                 ])
                 ->assertJson([
                     'status' => 'ok',
                 ]);
    }

    /**
     * Test que le health check est accessible sans authentification
     */
    public function test_health_check_is_public()
    {
        // Act - Pas d'authentification
        $response = $this->getJson('/api/health');

        // Assert
        $response->assertStatus(200);
    }
}

