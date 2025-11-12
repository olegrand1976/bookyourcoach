<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Student;
use App\Models\Discipline;
use App\Models\CourseType;
use App\Models\StudentPreference;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class StudentPreferencesControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_get_disciplines()
    {
        // Arrange
        $user = $this->actingAsStudent();
        
        $discipline1 = Discipline::factory()->create(['is_active' => true]);
        $discipline2 = Discipline::factory()->create(['is_active' => true]);
        Discipline::factory()->create(['is_active' => false]); // Inactive

        CourseType::factory()->count(2)->create([
            'discipline_id' => $discipline1->id,
            'is_active' => true,
        ]);

        CourseType::factory()->count(3)->create([
            'discipline_id' => $discipline2->id,
            'is_active' => true,
        ]);

        // Act
        $response = $this->getJson('/api/student/disciplines');

        // Assert
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         '*' => [
                             'id',
                             'name',
                             'course_types' => [
                                 '*' => [
                                     'id',
                                     'name',
                                     'discipline_id',
                                 ]
                             ]
                         ]
                     ]
                 ]);

        $disciplines = $response->json('data');
        $this->assertCount(2, $disciplines); // Seulement les actives
        $this->assertEquals(2, count($disciplines[0]['course_types']));
        $this->assertEquals(3, count($disciplines[1]['course_types']));
    }

    #[Test]
    public function it_can_get_student_preferences()
    {
        // Arrange
        $user = $this->actingAsStudent();
        $student = $user->student;
        
        $discipline1 = Discipline::factory()->create();
        $discipline2 = Discipline::factory()->create();
        $courseType1 = CourseType::factory()->create(['discipline_id' => $discipline1->id]);
        $courseType2 = CourseType::factory()->create(['discipline_id' => $discipline1->id]);

        StudentPreference::factory()->create([
            'student_id' => $student->id,
            'discipline_id' => $discipline1->id,
            'course_type_id' => $courseType1->id,
        ]);

        StudentPreference::factory()->create([
            'student_id' => $student->id,
            'discipline_id' => $discipline1->id,
            'course_type_id' => $courseType2->id,
        ]);

        StudentPreference::factory()->create([
            'student_id' => $student->id,
            'discipline_id' => $discipline2->id,
            'course_type_id' => null,
        ]);

        // Act
        $response = $this->getJson('/api/student/preferences/advanced');

        // Assert
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data'
                 ]);

        $data = $response->json('data');
        $this->assertArrayHasKey($discipline1->id, $data);
        $this->assertArrayHasKey($discipline2->id, $data);
        $this->assertCount(2, $data[$discipline1->id]); // 2 préférences pour discipline1
        $this->assertCount(1, $data[$discipline2->id]); // 1 préférence pour discipline2
    }

    #[Test]
    public function it_returns_404_if_student_profile_not_found()
    {
        // Arrange
        $user = User::factory()->create([
            'role' => 'student',
            'status' => 'active',
        ]);

        Sanctum::actingAs($user);

        // Act
        $response = $this->getJson('/api/student/preferences/advanced');

        // Assert
        $response->assertStatus(404)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Profil étudiant non trouvé'
                 ]);
    }

    #[Test]
    public function it_can_add_preference()
    {
        // Arrange
        $user = $this->actingAsStudent();
        $discipline = Discipline::factory()->create();
        $courseType = CourseType::factory()->create(['discipline_id' => $discipline->id]);

        // Act
        $response = $this->postJson('/api/student/preferences/advanced', [
            'discipline_id' => $discipline->id,
            'course_type_id' => $courseType->id,
            'is_preferred' => true,
            'priority_level' => 1,
        ]);

        // Assert
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'id',
                         'student_id',
                         'discipline_id',
                         'course_type_id',
                     ],
                     'message'
                 ]);

        $this->assertDatabaseHas('student_preferences', [
            'student_id' => $user->student->id,
            'discipline_id' => $discipline->id,
            'course_type_id' => $courseType->id,
        ]);
    }

    #[Test]
    public function it_validates_discipline_id_on_add_preference()
    {
        // Arrange
        $user = $this->actingAsStudent();

        // Act
        $response = $this->postJson('/api/student/preferences/advanced', [
            'discipline_id' => 99999, // ID inexistant
        ]);

        // Assert
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['discipline_id']);
    }

    #[Test]
    public function it_cannot_add_duplicate_preference()
    {
        // Arrange
        $user = $this->actingAsStudent();
        $student = $user->student;
        $discipline = Discipline::factory()->create();
        $courseType = CourseType::factory()->create(['discipline_id' => $discipline->id]);

        StudentPreference::factory()->create([
            'student_id' => $student->id,
            'discipline_id' => $discipline->id,
            'course_type_id' => $courseType->id,
        ]);

        // Act
        $response = $this->postJson('/api/student/preferences/advanced', [
            'discipline_id' => $discipline->id,
            'course_type_id' => $courseType->id,
        ]);

        // Assert
        $response->assertStatus(400)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Cette préférence existe déjà'
                 ]);
    }

    #[Test]
    public function it_can_remove_preference()
    {
        // Arrange
        $user = $this->actingAsStudent();
        $student = $user->student;
        $discipline = Discipline::factory()->create();
        $courseType = CourseType::factory()->create(['discipline_id' => $discipline->id]);

        $preference = StudentPreference::factory()->create([
            'student_id' => $student->id,
            'discipline_id' => $discipline->id,
            'course_type_id' => $courseType->id,
        ]);

        // Act
        $response = $this->deleteJson('/api/student/preferences/advanced', [
            'discipline_id' => $discipline->id,
            'course_type_id' => $courseType->id,
        ]);

        // Assert
        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ]);

        $this->assertDatabaseMissing('student_preferences', [
            'id' => $preference->id,
        ]);
    }

    #[Test]
    public function it_can_update_preferences()
    {
        // Arrange
        $user = $this->actingAsStudent();
        $student = $user->student;
        $discipline1 = Discipline::factory()->create();
        $discipline2 = Discipline::factory()->create();
        $courseType1 = CourseType::factory()->create(['discipline_id' => $discipline1->id]);
        $courseType2 = CourseType::factory()->create(['discipline_id' => $discipline2->id]);

        // Créer une préférence existante
        StudentPreference::factory()->create([
            'student_id' => $student->id,
            'discipline_id' => $discipline1->id,
            'course_type_id' => $courseType1->id,
        ]);

        // Act
        $response = $this->putJson('/api/student/preferences/advanced', [
            'preferences' => [
                [
                    'discipline_id' => $discipline1->id,
                    'course_type_id' => $courseType1->id,
                    'is_preferred' => true,
                    'priority_level' => 1,
                ],
                [
                    'discipline_id' => $discipline2->id,
                    'course_type_id' => $courseType2->id,
                    'is_preferred' => true,
                    'priority_level' => 2,
                ],
            ]
        ]);

        // Assert
        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Préférences mises à jour avec succès'
                 ]);

        $this->assertDatabaseHas('student_preferences', [
            'student_id' => $student->id,
            'discipline_id' => $discipline1->id,
            'course_type_id' => $courseType1->id,
        ]);

        $this->assertDatabaseHas('student_preferences', [
            'student_id' => $student->id,
            'discipline_id' => $discipline2->id,
            'course_type_id' => $courseType2->id,
        ]);
    }

    #[Test]
    public function it_validates_preferences_array_on_update()
    {
        // Arrange
        $user = $this->actingAsStudent();

        // Act
        $response = $this->putJson('/api/student/preferences/advanced', [
            'preferences' => 'invalid', // Pas un array
        ]);

        // Assert
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['preferences']);
    }

    #[Test]
    public function it_validates_discipline_id_in_preferences()
    {
        // Arrange
        $user = $this->actingAsStudent();

        // Act
        $response = $this->putJson('/api/student/preferences/advanced', [
            'preferences' => [
                [
                    'discipline_id' => 99999, // ID inexistant
                    'course_type_id' => null,
                ]
            ]
        ]);

        // Assert
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['preferences.0.discipline_id']);
    }

    #[Test]
    public function it_requires_authentication_to_access_preferences()
    {
        // Act
        $response = $this->getJson('/api/student/preferences/advanced');

        // Assert
        $response->assertStatus(401);
    }

    #[Test]
    public function it_requires_student_role_to_access_preferences()
    {
        // Arrange
        $clubUser = $this->actingAsClub();

        // Act
        $response = $this->getJson('/api/student/preferences/advanced');

        // Assert
        $response->assertStatus(403); // Forbidden car le middleware student vérifie le rôle
    }
}

