<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Student;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Facades\DB;

/**
 * Tests pour la fonctionnalité de liaison de comptes étudiants (famille)
 */
class StudentFamilyLinksTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function admin_can_get_linked_students()
    {
        // Arrange
        $admin = $this->actingAsAdmin();
        $student1 = Student::factory()->create([
            'user_id' => User::factory()->create(['role' => 'student'])->id,
        ]);
        $student2 = Student::factory()->create([
            'user_id' => User::factory()->create(['role' => 'student'])->id,
        ]);

        // Créer un lien bidirectionnel
        DB::table('student_family_links')->insert([
            [
                'primary_student_id' => $student1->id,
                'linked_student_id' => $student2->id,
                'created_by' => $admin->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'primary_student_id' => $student2->id,
                'linked_student_id' => $student1->id,
                'created_by' => $admin->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Act
        $response = $this->getJson("/api/admin/students/{$student1->id}/linked");

        // Assert
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         '*' => [
                             'id',
                             'name',
                             'email',
                         ]
                     ]
                 ]);

        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals($student2->id, $data[0]['id']);
    }

    #[Test]
    public function admin_can_link_two_students()
    {
        // Arrange
        $admin = $this->actingAsAdmin();
        $student1 = Student::factory()->create([
            'user_id' => User::factory()->create(['role' => 'student', 'email' => 'student1@test.com'])->id,
        ]);
        $student2 = Student::factory()->create([
            'user_id' => User::factory()->create(['role' => 'student', 'email' => 'student2@test.com'])->id,
        ]);

        // Act
        $response = $this->postJson("/api/admin/students/{$student1->id}/link", [
            'linked_student_id' => $student2->id,
            'relationship_type' => 'sibling',
        ]);

        // Assert
        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Les étudiants ont été liés avec succès'
                 ]);

        // Vérifier que les liens bidirectionnels ont été créés
        $this->assertDatabaseHas('student_family_links', [
            'primary_student_id' => $student1->id,
            'linked_student_id' => $student2->id,
        ]);
        $this->assertDatabaseHas('student_family_links', [
            'primary_student_id' => $student2->id,
            'linked_student_id' => $student1->id,
        ]);
    }

    #[Test]
    public function admin_cannot_link_student_to_itself()
    {
        // Arrange
        $admin = $this->actingAsAdmin();
        $student = Student::factory()->create([
            'user_id' => User::factory()->create(['role' => 'student', 'email' => 'student@test.com'])->id,
        ]);

        // Act
        $response = $this->postJson("/api/admin/students/{$student->id}/link", [
            'linked_student_id' => $student->id,
        ]);

        // Assert
        $response->assertStatus(422)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Un étudiant ne peut pas être lié à lui-même'
                 ]);
    }

    #[Test]
    public function admin_can_unlink_students()
    {
        // Arrange
        $admin = $this->actingAsAdmin();
        $student1 = Student::factory()->create([
            'user_id' => User::factory()->create(['role' => 'student', 'email' => 'student1@test.com'])->id,
        ]);
        $student2 = Student::factory()->create([
            'user_id' => User::factory()->create(['role' => 'student', 'email' => 'student2@test.com'])->id,
        ]);

        // Créer les liens
        DB::table('student_family_links')->insert([
            [
                'primary_student_id' => $student1->id,
                'linked_student_id' => $student2->id,
                'created_by' => $admin->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'primary_student_id' => $student2->id,
                'linked_student_id' => $student1->id,
                'created_by' => $admin->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Act
        $response = $this->deleteJson("/api/admin/students/{$student1->id}/unlink/{$student2->id}");

        // Assert
        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Les étudiants ont été déliés avec succès'
                 ]);

        // Vérifier que les liens ont été supprimés
        $this->assertDatabaseMissing('student_family_links', [
            'primary_student_id' => $student1->id,
            'linked_student_id' => $student2->id,
        ]);
        $this->assertDatabaseMissing('student_family_links', [
            'primary_student_id' => $student2->id,
            'linked_student_id' => $student1->id,
        ]);
    }

    #[Test]
    public function student_can_get_linked_accounts()
    {
        // Arrange
        $user = $this->actingAsStudent();
        $student1 = $user->student;
        $student2 = Student::factory()->create([
            'user_id' => User::factory()->create(['role' => 'student', 'email' => 'student2@test.com'])->id,
        ]);

        // Créer un lien
        DB::table('student_family_links')->insert([
            [
                'primary_student_id' => $student1->id,
                'linked_student_id' => $student2->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'primary_student_id' => $student2->id,
                'linked_student_id' => $student1->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Act
        $response = $this->getJson('/api/student/linked-accounts');

        // Assert
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         '*' => [
                             'id',
                             'name',
                             'email',
                             'is_active',
                             'is_primary',
                         ]
                     ]
                 ]);

        $data = $response->json('data');
        $this->assertGreaterThanOrEqual(2, count($data)); // Au moins le compte principal + le compte lié
    }

    #[Test]
    public function student_can_switch_account()
    {
        // Arrange
        $user = $this->actingAsStudent();
        $student1 = $user->student;
        $student2 = Student::factory()->create([
            'user_id' => User::factory()->create(['role' => 'student', 'email' => 'student2@test.com'])->id,
        ]);

        // Créer un lien
        DB::table('student_family_links')->insert([
            [
                'primary_student_id' => $student1->id,
                'linked_student_id' => $student2->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'primary_student_id' => $student2->id,
                'linked_student_id' => $student1->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Act
        $response = $this->postJson("/api/student/switch-account/{$student2->id}");

        // Assert
        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Compte changé avec succès'
                 ]);

        // Vérifier que la session a été mise à jour
        $this->assertEquals($student2->id, session('active_student_id'));
    }

    #[Test]
    public function student_cannot_switch_to_unlinked_account()
    {
        // Arrange
        $user = $this->actingAsStudent();
        $student1 = $user->student;
        $student2 = Student::factory()->create([
            'user_id' => User::factory()->create(['role' => 'student', 'email' => 'student2@test.com'])->id,
        ]);

        // Ne pas créer de lien

        // Act
        $response = $this->postJson("/api/student/switch-account/{$student2->id}");

        // Assert
        $response->assertStatus(403)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Vous n\'avez pas accès à ce compte étudiant'
                 ]);
    }

    #[Test]
    public function student_can_get_active_account()
    {
        // Arrange
        $user = $this->actingAsStudent();
        $student = $user->student;

        // Act
        $response = $this->getJson('/api/student/active-account');

        // Assert
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'id',
                         'name',
                         'email',
                         'is_primary',
                     ]
                 ]);

        $data = $response->json('data');
        $this->assertEquals($student->id, $data['id']);
        $this->assertTrue($data['is_primary']);
    }

    #[Test]
    public function admin_can_link_student_with_email_to_student_without_email()
    {
        $admin = $this->actingAsAdmin();
        $studentWithEmail = Student::factory()->create([
            'user_id' => User::factory()->create(['role' => 'student', 'email' => 'with@test.com'])->id,
        ]);
        $studentWithoutEmail = Student::factory()->create(['user_id' => null]);

        $response = $this->postJson("/api/admin/students/{$studentWithEmail->id}/link", [
            'linked_student_id' => $studentWithoutEmail->id,
            'relationship_type' => 'sibling',
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Les étudiants ont été liés avec succès'
                 ]);

        $this->assertDatabaseHas('student_family_links', [
            'primary_student_id' => $studentWithEmail->id,
            'linked_student_id' => $studentWithoutEmail->id,
        ]);
        $this->assertDatabaseCount('student_family_links', 1);
    }

    #[Test]
    public function admin_cannot_link_when_primary_has_no_user_id()
    {
        $admin = $this->actingAsAdmin();
        $primaryWithoutEmail = Student::factory()->create(['user_id' => null]);
        $linkedWithEmail = Student::factory()->create([
            'user_id' => User::factory()->create(['role' => 'student', 'email' => 'linked@test.com'])->id,
        ]);

        $response = $this->postJson("/api/admin/students/{$primaryWithoutEmail->id}/link", [
            'linked_student_id' => $linkedWithEmail->id,
        ]);

        $response->assertStatus(422)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Seul un élève avec compte (email) peut être le compte principal'
                 ]);
        $this->assertDatabaseCount('student_family_links', 0);
    }

    #[Test]
    public function student_can_see_linked_account_without_email_in_linked_accounts()
    {
        $user = $this->actingAsStudent();
        $student1 = $user->student;
        $student2WithoutUser = Student::factory()->create(['user_id' => null, 'first_name' => 'Sœur', 'last_name' => 'Dupont']);

        DB::table('student_family_links')->insert([
            'primary_student_id' => $student1->id,
            'linked_student_id' => $student2WithoutUser->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->getJson('/api/student/linked-accounts');

        $response->assertStatus(200)
                 ->assertJsonPath('success', true);
        $data = $response->json('data');
        $this->assertGreaterThanOrEqual(2, count($data));
        $linked = collect($data)->firstWhere('id', $student2WithoutUser->id);
        $this->assertNotNull($linked);
        $this->assertNull($linked['email']);
        $this->assertNull($linked['user_id']);
    }
}
