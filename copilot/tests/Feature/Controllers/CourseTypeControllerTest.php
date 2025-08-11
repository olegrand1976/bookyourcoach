<?php

namespace Tests\Feature\Controllers;

use App\Models\CourseType;
use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class CourseTypeControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_list_course_types()
    {
        $user = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($user);

        CourseType::factory()->count(3)->create();

        $response = $this->getJson('/api/course-types');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'description',
                        'duration',
                        'price',
                        'created_at',
                        'updated_at'
                    ]
                ]
            ]);
    }

    /** @test */
    public function it_can_show_a_course_type()
    {
        $user = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($user);

        $courseType = CourseType::factory()->create();

        $response = $this->getJson("/api/course-types/{$courseType->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $courseType->id,
                    'name' => $courseType->name,
                    'description' => $courseType->description,
                    'duration' => $courseType->duration,
                    'price' => (string) $courseType->price,
                ]
            ]);
    }

    /** @test */
    public function it_can_create_a_course_type_as_admin()
    {
        $user = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($user);

        $courseTypeData = [
            'name' => 'Dressage Avancé',
            'description' => 'Cours de dressage pour cavaliers confirmés',
            'duration' => 75,
            'price' => 55.00,
        ];

        $response = $this->postJson('/api/course-types', $courseTypeData);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'name' => 'Dressage Avancé',
                    'description' => 'Cours de dressage pour cavaliers confirmés',
                    'duration' => 75,
                    'price' => '55.00',
                ]
            ]);

        $this->assertDatabaseHas('course_types', $courseTypeData);
    }

    /** @test */
    public function it_cannot_create_a_course_type_as_teacher()
    {
        $user = User::factory()->create(['role' => 'teacher']);
        Sanctum::actingAs($user);

        $courseTypeData = [
            'name' => 'Dressage Avancé',
            'description' => 'Cours de dressage pour cavaliers confirmés',
            'duration' => 75,
            'price' => 55.00,
        ];

        $response = $this->postJson('/api/course-types', $courseTypeData);

        $response->assertStatus(403);
    }

    /** @test */
    public function it_can_update_a_course_type_as_admin()
    {
        $user = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($user);

        $courseType = CourseType::factory()->create();

        $updateData = [
            'name' => 'Nom mis à jour',
            'description' => 'Description mise à jour',
            'duration' => 90,
            'price' => 65.00,
        ];

        $response = $this->putJson("/api/course-types/{$courseType->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $courseType->id,
                    'name' => 'Nom mis à jour',
                    'description' => 'Description mise à jour',
                    'duration' => 90,
                    'price' => '65.00',
                ]
            ]);

        $this->assertDatabaseHas('course_types', array_merge(['id' => $courseType->id], $updateData));
    }

    /** @test */
    public function it_can_delete_a_course_type_as_admin()
    {
        $user = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($user);

        $courseType = CourseType::factory()->create();

        $response = $this->deleteJson("/api/course-types/{$courseType->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('course_types', ['id' => $courseType->id]);
    }

    /** @test */
    public function it_validates_required_fields_when_creating()
    {
        $user = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/course-types', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    /** @test */
    public function it_validates_price_format()
    {
        $user = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/course-types', [
            'name' => 'Test',
            'price' => 'invalid-price'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['price']);
    }

    /** @test */
    public function it_returns_404_for_non_existent_course_type()
    {
        $user = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/course-types/999');

        $response->assertStatus(404);
    }

    /** @test */
    public function guest_cannot_access_course_types()
    {
        $response = $this->getJson('/api/course-types');

        $response->assertStatus(401);
    }
}
