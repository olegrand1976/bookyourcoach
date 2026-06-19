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

    /** @test */
    public function club_does_not_see_course_types_from_another_club(): void
    {
        $discipline = \App\Models\Discipline::factory()->create();

        $user = $this->actingAsClub();
        $club = \App\Models\Club::find($user->club_id);
        $club->update(['disciplines' => [$discipline->id]]);

        $otherClub = \App\Models\Club::factory()->create([
            'disciplines' => [$discipline->id],
        ]);

        $foreignType = CourseType::factory()->create([
            'club_id' => $otherClub->id,
            'discipline_id' => $discipline->id,
            'name' => 'Type étranger',
            'is_active' => true,
        ]);

        $ownType = CourseType::factory()->create([
            'club_id' => $club->id,
            'discipline_id' => $discipline->id,
            'name' => 'Type du club',
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/course-types');

        $response->assertStatus(200);
        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertContains($ownType->id, $ids);
        $this->assertNotContains($foreignType->id, $ids);
    }
}

