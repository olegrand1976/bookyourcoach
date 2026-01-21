<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Club;
use App\Models\Teacher;
use App\Models\Student;
use App\Models\Lesson;
use App\Models\CourseType;
use App\Models\Location;
use App\Models\VolunteerExpenseLimit;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class ClubControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_get_club_profile()
    {
        // Arrange
        $user = $this->actingAsClub();
        $club = Club::find($user->club_id);

        // Act
        $response = $this->getJson('/api/club/profile');

        // Assert
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'id',
                         'name',
                         'email',
                     ]
                 ]);

        $this->assertEquals($club->id, $response->json('data.id'));
    }

    #[Test]
    public function it_can_update_club_profile()
    {
        // Arrange
        $user = $this->actingAsClub();
        $club = Club::find($user->club_id);

        $updateData = [
            'name' => 'Nouveau Nom du Club',
            'email' => 'nouveau@club.fr',
            'phone' => '+33123456789',
            'address' => '123 Rue Example',
            'city' => 'Paris',
            'postal_code' => '75001',
            'website' => 'https://nouveau-club.fr',
            'description' => 'Nouvelle description',
            'company_number' => 'BE0123456789',
            'legal_representative_name' => 'Jean Dupont',
            'legal_representative_role' => 'Directeur',
            'insurance_rc_company' => 'Assurance RC',
            'insurance_rc_policy_number' => 'RC123456',
            'insurance_additional_details' => 'Détails assurance',
            'expense_reimbursement_type' => 'forfait',
            'expense_reimbursement_details' => 'Détails remboursement',
        ];

        // Act
        $response = $this->putJson('/api/club/profile', $updateData);

        // Assert
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data',
                 ]);

        $this->assertDatabaseHas('clubs', [
            'id' => $club->id,
            'name' => 'Nouveau Nom du Club',
            'email' => 'nouveau@club.fr',
        ]);
    }

    #[Test]
    public function it_can_update_legal_fields_in_profile()
    {
        // Arrange
        $user = $this->actingAsClub();
        $club = Club::find($user->club_id);

        $updateData = [
            'name' => $club->name ?? 'Club Test',
            'email' => $club->email ?? 'test@club.fr',
            'company_number' => $club->company_number ?? 'BE0123456789',
            'legal_representative_name' => 'John Doe',
            'legal_representative_role' => 'Directeur',
            'insurance_rc_company' => 'Assureur XYZ',
            'insurance_rc_policy_number' => 'POL-123456',
            'insurance_additional_details' => 'Détails assurance',
            'expense_reimbursement_type' => 'forfait',
            'expense_reimbursement_details' => '15€ par jour',
        ];

        // Act
        $response = $this->putJson('/api/club/profile', $updateData);

        // Assert
        $response->assertStatus(200);

        $this->assertDatabaseHas('clubs', [
            'id' => $club->id,
            'legal_representative_name' => 'John Doe',
            'insurance_rc_company' => 'Assureur XYZ',
        ]);
    }

    #[Test]
    public function it_can_get_diagnose_columns()
    {
        // Arrange
        $user = $this->actingAsClub();

        // Act
        $response = $this->getJson('/api/club/diagnose-columns');

        // Assert
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'all_columns',
                     'legal_fields_status',
                     'current_club_data',
                     'total_columns',
                     'legal_fields_existing',
                 ]);
    }

    #[Test]
    public function it_can_get_custom_specialties()
    {
        // Arrange
        $user = $this->actingAsClub();
        $club = Club::find($user->club_id);

        \App\Models\ClubCustomSpecialty::factory()->count(3)->create([
            'club_id' => $club->id,
        ]);

        // Act
        $response = $this->getJson('/api/club/custom-specialties');

        // Assert
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         '*' => [
                             'id',
                             'club_id',
                             'name',
                         ]
                     ]
                 ]);

        $this->assertCount(3, $response->json('data'));
    }

    #[Test]
    public function it_can_list_teachers()
    {
        // Arrange
        $user = $this->actingAsClub();
        $club = Club::find($user->club_id);

        $teacher1 = Teacher::factory()->create();
        $teacher2 = Teacher::factory()->create();

        // Associer les enseignants au club
        $teacher1->clubs()->attach($club->id, [
            'is_active' => true,
            'contract_type' => 'volunteer',
            'joined_at' => now(),
        ]);
        $teacher2->clubs()->attach($club->id, [
            'is_active' => true,
            'contract_type' => 'employee',
            'joined_at' => now(),
        ]);

        // Act
        $response = $this->getJson('/api/club/teachers');

        // Assert
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'teachers',
                 ]);

        $teachers = $response->json('teachers');
        $this->assertCount(2, $teachers);
    }

    #[Test]
    public function it_can_filter_teachers_by_contract_type()
    {
        // Arrange
        $user = $this->actingAsClub();
        $club = Club::find($user->club_id);

        $teacher1 = Teacher::factory()->create();
        $teacher2 = Teacher::factory()->create();

        $teacher1->clubs()->attach($club->id, [
            'is_active' => true,
            'contract_type' => 'volunteer',
            'joined_at' => now(),
        ]);
        $teacher2->clubs()->attach($club->id, [
            'is_active' => true,
            'contract_type' => 'employee',
            'joined_at' => now(),
        ]);

        // Act
        $response = $this->getJson('/api/club/teachers?contract_type=volunteer');

        // Assert
        $response->assertStatus(200);
        $teachers = $response->json('teachers');
        $this->assertCount(1, $teachers);
        $this->assertEquals('volunteer', $teachers[0]['contract_type']);
    }

    #[Test]
    public function it_can_create_teacher()
    {
        // Arrange
        $user = $this->actingAsClub();
        $club = Club::find($user->club_id);

        Notification::fake();

        $teacherData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'phone' => '+33123456789',
            'contract_type' => 'volunteer',
            'hourly_rate' => 0,
        ];

        // Act
        $response = $this->postJson('/api/club/teachers', $teacherData);

        // Assert
        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         'id',
                         'user',
                     ]
                 ]);

        $this->assertDatabaseHas('users', [
            'email' => 'john.doe@example.com',
            'role' => 'teacher',
        ]);

        // Vérifier que l'enseignant est associé au club
        $teacher = Teacher::whereHas('user', function($q) {
            $q->where('email', 'john.doe@example.com');
        })->first();

        $this->assertTrue($teacher->clubs->contains($club));
    }

    #[Test]
    public function it_can_create_teacher_with_niss_bank_account_and_address()
    {
        // Arrange
        $user = $this->actingAsClub();
        $club = Club::find($user->club_id);

        Notification::fake();

        $teacherData = [
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane.smith@example.com',
            'phone' => '+33123456789',
            'niss' => '76.01.10-427.03',
            'bank_account_number' => 'BE12 3456 7890 1234',
            'street' => 'Rue de la Paix',
            'street_number' => '123',
            'street_box' => 'Bte 5',
            'postal_code' => '1000',
            'city' => 'Bruxelles',
            'country' => 'Belgium',
            'contract_type' => 'volunteer',
            'hourly_rate' => 0,
        ];

        // Act
        $response = $this->postJson('/api/club/teachers', $teacherData);

        // Assert
        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         'id',
                         'user',
                     ]
                 ]);

        $this->assertDatabaseHas('users', [
            'email' => 'jane.smith@example.com',
            'role' => 'teacher',
            'niss' => '76.01.10-427.03',
            'bank_account_number' => 'BE12 3456 7890 1234',
            'street' => 'Rue de la Paix',
            'street_number' => '123',
            'street_box' => 'Bte 5',
            'postal_code' => '1000',
            'city' => 'Bruxelles',
            'country' => 'Belgium',
        ]);

        // Vérifier que l'enseignant est associé au club
        $teacher = Teacher::whereHas('user', function($q) {
            $q->where('email', 'jane.smith@example.com');
        })->first();

        $this->assertTrue($teacher->clubs->contains($club));
    }

    #[Test]
    public function it_can_update_teacher_with_niss_bank_account_and_address()
    {
        // Arrange
        $user = $this->actingAsClub();
        $club = Club::find($user->club_id);

        $teacher = Teacher::factory()->create();
        $teacherUser = $teacher->user;
        $teacher->clubs()->attach($club->id, [
            'is_active' => true,
            'contract_type' => 'volunteer',
            'hourly_rate' => 0,
            'joined_at' => now(),
        ]);

        $updateData = [
            'first_name' => 'Updated',
            'last_name' => 'Name',
            'email' => $teacherUser->email,
            'niss' => '76.01.10-427.03',
            'bank_account_number' => 'BE12 3456 7890 1234',
            'street' => 'Rue de la Paix',
            'street_number' => '123',
            'street_box' => 'Bte 5',
            'postal_code' => '1000',
            'city' => 'Bruxelles',
            'country' => 'Belgium',
            'contract_type' => 'employee',
            'hourly_rate' => 25.00,
        ];

        // Act
        $response = $this->putJson("/api/club/teachers/{$teacher->id}", $updateData);

        // Assert
        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ]);

        $teacherUser->refresh();
        $this->assertEquals('Updated Name', $teacherUser->name);
        $this->assertEquals('76.01.10-427.03', $teacherUser->niss);
        $this->assertEquals('BE12 3456 7890 1234', $teacherUser->bank_account_number);
        $this->assertEquals('Rue de la Paix', $teacherUser->street);
        $this->assertEquals('123', $teacherUser->street_number);
        $this->assertEquals('Bte 5', $teacherUser->street_box);
        $this->assertEquals('1000', $teacherUser->postal_code);
        $this->assertEquals('Bruxelles', $teacherUser->city);
        $this->assertEquals('Belgium', $teacherUser->country);

        $this->assertDatabaseHas('club_teachers', [
            'club_id' => $club->id,
            'teacher_id' => $teacher->id,
            'contract_type' => 'employee',
            'hourly_rate' => 25.00,
        ]);
    }

    #[Test]
    public function it_validates_teacher_creation_data()
    {
        // Arrange
        $user = $this->actingAsClub();

        // Act
        $response = $this->postJson('/api/club/teachers', [
            'email' => 'invalid-email', // Email invalide
        ]);

        // Assert
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email', 'first_name', 'last_name']);
    }

    #[Test]
    public function it_can_update_teacher()
    {
        // Arrange
        $user = $this->actingAsClub();
        $club = Club::find($user->club_id);

        $teacher = Teacher::factory()->create();
        $teacher->clubs()->attach($club->id, [
            'is_active' => true,
            'contract_type' => 'volunteer',
            'hourly_rate' => 0,
            'joined_at' => now(),
        ]);

        $updateData = [
            'contract_type' => 'employee',
            'hourly_rate' => 25.00,
        ];

        // Act
        $response = $this->putJson("/api/club/teachers/{$teacher->id}", $updateData);

        // Assert
        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ]);

        $this->assertDatabaseHas('club_teachers', [
            'club_id' => $club->id,
            'teacher_id' => $teacher->id,
            'contract_type' => 'employee',
            'hourly_rate' => 25.00,
        ]);
    }

    #[Test]
    public function it_validates_contract_type_on_update()
    {
        // Arrange
        $user = $this->actingAsClub();
        $club = Club::find($user->club_id);

        $teacher = Teacher::factory()->create();
        $teacher->clubs()->attach($club->id, ['is_active' => true]);

        // Act
        $response = $this->putJson("/api/club/teachers/{$teacher->id}", [
            'contract_type' => 'invalid_type', // Type invalide
        ]);

        // Assert
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['contract_type']);
    }

    #[Test]
    public function it_can_delete_teacher()
    {
        // Arrange
        $user = $this->actingAsClub();
        $club = Club::find($user->club_id);

        $teacher = Teacher::factory()->create();
        $teacher->clubs()->attach($club->id, [
            'is_active' => true,
            'joined_at' => now(),
        ]);

        // Act
        $response = $this->deleteJson("/api/club/teachers/{$teacher->id}");

        // Assert
        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ]);

        // Vérifier que l'association est désactivée, pas supprimée
        $this->assertDatabaseHas('club_teachers', [
            'club_id' => $club->id,
            'teacher_id' => $teacher->id,
            'is_active' => false,
        ]);
    }

    #[Test]
    public function it_can_resend_teacher_invitation()
    {
        // Arrange
        $user = $this->actingAsClub();
        $club = Club::find($user->club_id);

        $teacher = Teacher::factory()->create();
        $teacher->clubs()->attach($club->id, ['is_active' => true]);

        Notification::fake();

        // Act
        $response = $this->postJson("/api/club/teachers/{$teacher->id}/resend-invitation");

        // Assert
        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Invitation renvoyée avec succès',
                 ]);

        Notification::assertSentTo($teacher->user, \App\Notifications\TeacherWelcomeNotification::class);
    }

    #[Test]
    public function it_can_list_students()
    {
        // Arrange
        $user = $this->actingAsClub();
        $club = Club::find($user->club_id);

        $student1 = Student::factory()->create(['club_id' => $club->id]);
        $student2 = Student::factory()->create(['club_id' => $club->id]);

        // Associer les étudiants au club
        DB::table('club_students')->insert([
            [
                'club_id' => $club->id,
                'student_id' => $student1->id,
                'is_active' => true,
                'joined_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'club_id' => $club->id,
                'student_id' => $student2->id,
                'is_active' => true,
                'joined_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Act
        $response = $this->getJson('/api/club/students');

        // Assert
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         '*' => [
                             'id',
                             'name',
                             'email',
                             'phone',
                         ]
                     ]
                 ]);

        $students = $response->json('data');
        $this->assertGreaterThanOrEqual(2, count($students));
    }

    #[Test]
    public function it_requires_club_role_to_access_endpoints()
    {
        // Arrange
        $teacherUser = $this->actingAsTeacher();

        // Act
        $response = $this->getJson('/api/club/profile');

        // Assert
        $response->assertStatus(403); // Forbidden car le middleware club vérifie le rôle
    }

    #[Test]
    public function it_requires_authentication_to_access_endpoints()
    {
        // Act
        $response = $this->getJson('/api/club/profile');

        // Assert
        $response->assertStatus(401);
    }
}

