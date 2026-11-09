<?php

namespace Tests\Feature\Api;

use App\Models\Club;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Email d'un élève côté club : modification depuis sa fiche (PUT /api/club/students/{id})
 * et création (POST /api/club/students). users.email est unique tous rôles confondus.
 */
class ClubStudentEmailUpdateTest extends TestCase
{
    use RefreshDatabase;

    private function studentWithAccount(Club $club, array $userAttributes = []): Student
    {
        $studentUser = User::factory()->create(array_merge([
            'role' => 'student',
            'email' => 'eleve@example.com',
        ], $userAttributes));

        $student = Student::factory()->create([
            'club_id' => $club->id,
            'user_id' => $studentUser->id,
        ]);

        DB::table('club_students')->insert([
            'club_id' => $club->id,
            'student_id' => $student->id,
            'is_active' => true,
            'joined_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $student;
    }

    /** Charge utile identique à celle envoyée par EditStudentModal.vue. */
    private function payload(Student $student, string $email): array
    {
        return [
            'first_name' => $student->first_name,
            'last_name' => $student->last_name,
            'date_of_birth' => null,
            'goals' => null,
            'medical_info' => null,
            'disciplines' => null,
            'email' => $email,
            'phone' => (string) ($student->phone ?? ''),
        ];
    }

    #[Test]
    public function le_club_modifie_l_email_d_un_eleve_avec_compte()
    {
        $club = Club::find($this->actingAsClub()->club_id);
        $student = $this->studentWithAccount($club);

        $this->putJson("/api/club/students/{$student->id}", $this->payload($student, 'nouveau@example.com'))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('users', ['id' => $student->user_id, 'email' => 'nouveau@example.com']);
    }

    #[Test]
    public function un_email_deja_pris_par_un_autre_eleve_renvoie_une_erreur_de_validation()
    {
        $club = Club::find($this->actingAsClub()->club_id);
        $student = $this->studentWithAccount($club);
        User::factory()->create(['role' => 'student', 'email' => 'pris@example.com']);

        $this->putJson("/api/club/students/{$student->id}", $this->payload($student, 'pris@example.com'))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    #[Test]
    public function un_email_deja_pris_par_un_enseignant_renvoie_une_erreur_de_validation()
    {
        $club = Club::find($this->actingAsClub()->club_id);
        $student = $this->studentWithAccount($club);
        User::factory()->create(['role' => 'teacher', 'email' => 'coach@example.com']);

        $this->putJson("/api/club/students/{$student->id}", $this->payload($student, 'coach@example.com'))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        $this->assertDatabaseHas('users', ['id' => $student->user_id, 'email' => 'eleve@example.com']);
    }

    #[Test]
    public function un_email_mal_forme_renvoie_une_erreur_de_validation()
    {
        $club = Club::find($this->actingAsClub()->club_id);
        $student = $this->studentWithAccount($club);

        $this->putJson("/api/club/students/{$student->id}", $this->payload($student, 'pas-un-email'))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    #[Test]
    public function vider_l_email_d_un_eleve_avec_compte_est_refuse_proprement()
    {
        $club = Club::find($this->actingAsClub()->club_id);
        $student = $this->studentWithAccount($club);

        $this->putJson("/api/club/students/{$student->id}", $this->payload($student, ''))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        $this->assertDatabaseHas('users', ['id' => $student->user_id, 'email' => 'eleve@example.com']);
    }

    #[Test]
    public function ajouter_un_email_a_un_eleve_sans_compte_cree_son_compte()
    {
        Notification::fake();
        $club = Club::find($this->actingAsClub()->club_id);
        $student = Student::factory()->create(['club_id' => $club->id, 'user_id' => null]);
        DB::table('club_students')->insert([
            'club_id' => $club->id,
            'student_id' => $student->id,
            'is_active' => true,
            'joined_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->putJson("/api/club/students/{$student->id}", $this->payload($student, 'premier@example.com'))
            ->assertOk()
            ->assertJson(['success' => true, 'user_created' => true]);

        $this->assertDatabaseHas('users', ['email' => 'premier@example.com', 'role' => 'student']);
    }

    #[Test]
    public function creer_un_eleve_avec_l_email_d_un_enseignant_renvoie_une_erreur_de_validation()
    {
        Notification::fake();
        $this->actingAsClub();
        User::factory()->create(['role' => 'teacher', 'email' => 'coach@example.com']);

        $this->postJson('/api/club/students', [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'coach@example.com',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        $this->assertSame(0, User::where('email', 'coach@example.com')->where('role', 'student')->count());
    }
}
