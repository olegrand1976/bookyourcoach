<?php

namespace Tests\Feature\Api;

use App\Models\Discipline;
use App\Models\ActivityType;
use App\Models\CourseType;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class DisciplineControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_list_disciplines()
    {
        // Arrange
        Discipline::factory()->count(5)->create(['is_active' => true]);
        Discipline::factory()->count(2)->create(['is_active' => false]); // Inactives

        // Act
        $response = $this->getJson('/api/disciplines');

        // Assert
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         '*' => [
                             'id',
                             'name',
                             'is_active',
                         ]
                     ]
                 ]);

        $disciplines = $response->json('data');
        $this->assertCount(5, $disciplines); // Seulement les actives
    }

    #[Test]
    public function it_can_show_discipline()
    {
        // Arrange
        $discipline = Discipline::factory()->create([
            'is_active' => true,
        ]);

        CourseType::factory()->count(3)->create([
            'discipline_id' => $discipline->id,
            'is_active' => true,
        ]);

        // Act
        $response = $this->getJson("/api/disciplines/{$discipline->id}");

        // Assert
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'id',
                         'name',
                         'description',
                         'course_types',
                     ]
                 ])
                 ->assertJsonFragment([
                     'id' => $discipline->id,
                 ]);
    }

    #[Test]
    public function it_returns_404_for_nonexistent_discipline()
    {
        // Act
        $response = $this->getJson('/api/disciplines/99999');

        // Assert
        $response->assertStatus(404);
    }

    #[Test]
    public function it_can_get_disciplines_by_activity_type()
    {
        // Arrange
        $activityType = ActivityType::factory()->create();
        
        $discipline1 = Discipline::factory()->create([
            'activity_type_id' => $activityType->id,
            'is_active' => true,
        ]);
        
        $discipline2 = Discipline::factory()->create([
            'activity_type_id' => $activityType->id,
            'is_active' => true,
        ]);

        Discipline::factory()->create([
            'activity_type_id' => ActivityType::factory()->create()->id,
            'is_active' => true,
        ]);

        // Act
        $response = $this->getJson("/api/disciplines/by-activity/{$activityType->id}");

        // Assert
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data',
                 ]);

        $disciplines = $response->json('data');
        $disciplineIds = collect($disciplines)->pluck('id')->toArray();
        
        $this->assertContains($discipline1->id, $disciplineIds);
        $this->assertContains($discipline2->id, $disciplineIds);
        $this->assertCount(2, $disciplines);
    }

    #[Test]
    public function it_returns_empty_array_for_activity_with_no_disciplines()
    {
        // Arrange
        $activityType = ActivityType::factory()->create();

        // Act
        $response = $this->getJson("/api/disciplines/by-activity/{$activityType->id}");

        // Assert
        $response->assertStatus(200);
        $this->assertEmpty($response->json('data'));
    }

    #[Test]
    public function it_is_public_route_no_authentication_required()
    {
        // Act - Pas d'authentification
        $response = $this->getJson('/api/disciplines');

        // Assert
        $response->assertStatus(200); // Accessible sans authentification
    }
}

