<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Club;
use App\Models\Teacher;
use App\Models\VolunteerLetterSend;
use App\Models\VolunteerExpenseLimit;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

class ClubVolunteerLetterControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_send_volunteer_letter_to_teacher()
    {
        // Arrange
        Queue::fake();
        
        $user = $this->actingAsClub();
        $club = Club::find($user->club_id);
        
        // Remplir les informations légales
        $club->update([
            'legal_representative_name' => 'John Doe',
            'legal_representative_role' => 'Directeur',
            'insurance_rc_company' => 'Assureur XYZ',
            'insurance_rc_policy_number' => 'POL-123',
        ]);

        $teacher = Teacher::factory()->create();
        $teacher->clubs()->attach($club->id, [
            'is_active' => true,
            'contract_type' => 'volunteer',
            'joined_at' => now(),
        ]);

        // Créer un plafond de défraiement
        VolunteerExpenseLimit::factory()->create([
            'year' => now()->year,
            'amount' => 45.50,
        ]);

        // Act
        $response = $this->postJson("/api/club/volunteer-letters/send/{$teacher->id}");

        // Assert
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                 ]);

        Queue::assertPushed(\App\Jobs\SendVolunteerLetterJob::class, function ($job) use ($club, $teacher) {
            return $job->clubId === $club->id && $job->teacherId === $teacher->id;
        });
    }

    #[Test]
    public function it_returns_error_if_legal_info_incomplete()
    {
        // Arrange
        $user = $this->actingAsClub();
        $club = Club::find($user->club_id);
        
        // Ne pas remplir les informations légales
        $club->update([
            'legal_representative_name' => null,
        ]);

        $teacher = Teacher::factory()->create();
        $teacher->clubs()->attach($club->id, [
            'is_active' => true,
            'contract_type' => 'volunteer',
            'joined_at' => now(),
        ]);

        // Act
        $response = $this->postJson("/api/club/volunteer-letters/send/{$teacher->id}");

        // Assert
        $response->assertStatus(400)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Les informations légales du club sont incomplètes'
                 ]);
    }

    #[Test]
    public function it_returns_error_if_teacher_not_in_club()
    {
        // Arrange
        $user = $this->actingAsClub();
        $club = Club::find($user->club_id);
        
        $club->update([
            'legal_representative_name' => 'John Doe',
            'insurance_rc_company' => 'Assureur XYZ',
        ]);

        $otherClub = Club::factory()->create();
        $teacher = Teacher::factory()->create();
        $teacher->clubs()->attach($otherClub->id, [
            'is_active' => true,
            'contract_type' => 'volunteer',
        ]);

        // Act
        $response = $this->postJson("/api/club/volunteer-letters/send/{$teacher->id}");

        // Assert
        $response->assertStatus(404)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Enseignant introuvable ou non affilié à votre club'
                 ]);
    }

    #[Test]
    public function it_returns_error_if_teacher_has_no_email()
    {
        // Arrange
        $user = $this->actingAsClub();
        $club = Club::find($user->club_id);
        
        $club->update([
            'legal_representative_name' => 'John Doe',
            'insurance_rc_company' => 'Assureur XYZ',
        ]);

        $teacherUser = User::factory()->create([
            'role' => 'teacher',
            'email' => null, // Pas d'email
        ]);
        
        $teacher = Teacher::factory()->create(['user_id' => $teacherUser->id]);
        $teacher->clubs()->attach($club->id, [
            'is_active' => true,
            'contract_type' => 'volunteer',
        ]);

        // Act
        $response = $this->postJson("/api/club/volunteer-letters/send/{$teacher->id}");

        // Assert
        $response->assertStatus(400)
                 ->assertJson([
                     'success' => false,
                     'message' => 'L\'enseignant n\'a pas d\'adresse email'
                 ]);
    }

    #[Test]
    public function it_can_send_letters_to_all_teachers()
    {
        // Arrange
        Queue::fake();
        
        $user = $this->actingAsClub();
        $club = Club::find($user->club_id);
        
        $club->update([
            'legal_representative_name' => 'John Doe',
            'insurance_rc_company' => 'Assureur XYZ',
        ]);

        $teacher1 = Teacher::factory()->create();
        $teacher2 = Teacher::factory()->create();
        
        $teacher1->clubs()->attach($club->id, [
            'is_active' => true,
            'contract_type' => 'volunteer',
            'joined_at' => now(),
        ]);
        $teacher2->clubs()->attach($club->id, [
            'is_active' => true,
            'contract_type' => 'volunteer',
            'joined_at' => now(),
        ]);

        VolunteerExpenseLimit::factory()->create([
            'year' => now()->year,
            'amount' => 45.50,
        ]);

        // Act
        $response = $this->postJson('/api/club/volunteer-letters/send-all');

        // Assert
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'results' => [
                         'total',
                         'queued',
                         'skipped',
                         'details',
                     ]
                 ]);

        $results = $response->json('results');
        $this->assertEquals(2, $results['total']);
        $this->assertEquals(2, $results['queued']);
        
        Queue::assertPushed(\App\Jobs\SendVolunteerLetterJob::class, 2);
    }

    #[Test]
    public function it_can_get_volunteer_letter_history()
    {
        // Arrange
        $user = $this->actingAsClub();
        $club = Club::find($user->club_id);

        $teacher = Teacher::factory()->create();
        $teacher->clubs()->attach($club->id, ['is_active' => true]);

        VolunteerLetterSend::factory()->count(5)->create([
            'club_id' => $club->id,
            'teacher_id' => $teacher->id,
            'sent_by_user_id' => $user->id,
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        // Act
        $response = $this->getJson('/api/club/volunteer-letters/history');

        // Assert
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'history' => [
                         '*' => [
                             'id',
                             'teacher_name',
                             'teacher_email',
                             'status',
                             'sent_at',
                             'sent_by_name',
                         ]
                     ]
                 ]);

        $history = $response->json('history');
        $this->assertCount(5, $history);
    }

    #[Test]
    public function it_requires_club_admin_to_send_letters()
    {
        // Arrange
        $user = User::factory()->create(['role' => 'club']);
        Sanctum::actingAs($user);
        
        // Ne pas créer d'association club_user
        
        $teacher = Teacher::factory()->create();

        // Act
        $response = $this->postJson("/api/club/volunteer-letters/send/{$teacher->id}");

        // Assert
        $response->assertStatus(403)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Vous devez être administrateur d\'un club'
                 ]);
    }

    #[Test]
    public function it_requires_authentication_to_access_endpoints()
    {
        // Act
        $response = $this->getJson('/api/club/volunteer-letters/history');

        // Assert
        $response->assertStatus(401);
    }

    #[Test]
    public function it_requires_club_role_to_access_endpoints()
    {
        // Arrange
        $teacherUser = $this->actingAsTeacher();

        // Act
        $response = $this->getJson('/api/club/volunteer-letters/history');

        // Assert
        $response->assertStatus(403);
    }
}

