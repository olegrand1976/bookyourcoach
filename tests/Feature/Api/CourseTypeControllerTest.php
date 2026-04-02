<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\CourseType;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CourseTypeControllerTest extends TestCase
{
    use RefreshDatabase;
    /** @test */
    public function it_can_list_course_types()
    {
        // Arrange
        $this->actingAsClub();
        
        CourseType::factory()->count(5)->create(['discipline_id' => null]);
        
        // Act
        $response = $this->getJson('/api/course-types');
        
        // Assert
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         '*' => [
                             'id',
                             'name',
                             'description',
                             'duration_minutes',
                             'price',
                         ]
                     ]
                 ]);
        
        $this->assertGreaterThanOrEqual(5, count($response->json('data')));
    }

    /** @test */
    public function it_requires_authentication()
    {
        // Act
        $response = $this->getJson('/api/course-types');
        
        // Assert
        $response->assertStatus(401);
    }

    /** @test */
    public function it_can_be_accessed_by_club_user()
    {
        // Arrange
        CourseType::factory()->count(3)->create(['discipline_id' => null]);
        
        // Act - Test avec club
        $this->actingAsClub();
        $response = $this->getJson('/api/course-types');
        
        // Assert
        $response->assertStatus(200);
        $this->assertGreaterThanOrEqual(3, count($response->json('data')));
    }

    /** @test */
    public function it_returns_correct_data_structure()
    {
        // Arrange
        $this->actingAsClub();
        
        CourseType::factory()->create([
            'name' => 'Dressage',
            'description' => 'Cours de dressage classique',
            'duration_minutes' => 60,
            'price' => 45.00,
            'discipline_id' => null,
        ]);
        
        // Act
        $response = $this->getJson('/api/course-types');
        
        // Assert
        $response->assertStatus(200)
                 ->assertJsonFragment([
                     'name' => 'Dressage',
                     'description' => 'Cours de dressage classique',
                     'duration_minutes' => 60,
                 ]);
    }
}

