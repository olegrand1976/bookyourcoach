<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ActivityTypesTest extends TestCase
{
    use RefreshDatabase;
    /**
     * Test de la route publique activity-types
     */
    public function test_it_returns_activity_types()
    {
        // Act
        $response = $this->getJson('/api/activity-types');

        // Assert
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         '*' => [
                             'id',
                             'name',
                             'icon',
                             'description',
                         ]
                     ]
                 ]);

        $activityTypes = $response->json('data');
        $this->assertGreaterThanOrEqual(1, count($activityTypes));
    }

    /**
     * Test que la route est publique (pas d'authentification requise)
     */
    public function test_it_is_public_route()
    {
        // Act - Pas d'authentification
        $response = $this->getJson('/api/activity-types');

        // Assert
        $response->assertStatus(200);
    }

    /**
     * Test que les types d'activités contiennent les champs attendus
     */
    public function test_activity_types_have_required_fields()
    {
        // Act
        $response = $this->getJson('/api/activity-types');

        // Assert
        $response->assertStatus(200);
        
        $activityTypes = $response->json('data');
        foreach ($activityTypes as $type) {
            $this->assertArrayHasKey('id', $type);
            $this->assertArrayHasKey('name', $type);
            $this->assertArrayHasKey('icon', $type);
            $this->assertArrayHasKey('description', $type);
        }
    }
}

