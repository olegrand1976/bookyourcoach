<?php

namespace Tests\Feature\Api;

use App\Models\Club;
use App\Models\Student;
use App\Models\User;
use App\Services\FamilyLinkService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ClubStudentInviteCodeTest extends TestCase
{
    use RefreshDatabase;

    private function attachStudentToClub(int $clubId, int $studentId): void
    {
        DB::table('club_students')->insert([
            'club_id' => $clubId,
            'student_id' => $studentId,
            'is_active' => true,
            'joined_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    #[Test]
    public function club_creating_student_without_email_generates_invite_code(): void
    {
        $clubUser = $this->actingAsClub();

        $response = $this->postJson('/api/club/students', [
            'first_name' => 'Léa',
            'last_name' => 'Martin',
            'date_of_birth' => now()->subYears(10)->format('Y-m-d'),
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('user_created', false);

        $this->assertNotEmpty($response->json('invite_code'));
        $this->assertNotEmpty($response->json('invite_code_expires_at'));

        $this->assertDatabaseHas('students', [
            'first_name' => 'Léa',
            'user_id' => null,
        ]);
    }

    #[Test]
    public function club_can_fetch_current_invite_code(): void
    {
        $clubUser = $this->actingAsClub();
        $clubId = $clubUser->club_id;
        $child = Student::factory()->create(['user_id' => null]);
        $this->attachStudentToClub($clubId, $child->id);
        $code = app(FamilyLinkService::class)->generateInviteCode($child);

        $response = $this->getJson("/api/club/students/{$child->id}/invite-code");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.invite_code', $code)
            ->assertJsonPath('data.is_expired', false);
    }

    #[Test]
    public function club_can_regenerate_invite_code(): void
    {
        $clubUser = $this->actingAsClub();
        $clubId = $clubUser->club_id;
        $child = Student::factory()->create(['user_id' => null]);
        $this->attachStudentToClub($clubId, $child->id);
        $first = app(FamilyLinkService::class)->generateInviteCode($child);

        $response = $this->postJson("/api/club/students/{$child->id}/invite-code/regenerate");

        $response->assertOk()->assertJsonPath('success', true);

        $newCode = $response->json('data.invite_code');
        $this->assertNotEmpty($newCode);
        $this->assertNotSame($first, $newCode);
    }

    #[Test]
    public function club_cannot_access_invite_code_of_student_outside_its_club(): void
    {
        $this->actingAsClub();
        $foreignStudent = Student::factory()->create(['user_id' => null]);
        app(FamilyLinkService::class)->generateInviteCode($foreignStudent);

        $response = $this->getJson("/api/club/students/{$foreignStudent->id}/invite-code");

        $response->assertStatus(404);
    }

    #[Test]
    public function cannot_regenerate_for_already_linked_student(): void
    {
        $clubUser = $this->actingAsClub();
        $clubId = $clubUser->club_id;
        $parent = User::factory()->create(['role' => 'student']);
        $child = Student::factory()->create(['user_id' => $parent->id]);
        $this->attachStudentToClub($clubId, $child->id);

        $response = $this->postJson("/api/club/students/{$child->id}/invite-code/regenerate");

        $response->assertStatus(422)->assertJsonPath('success', false);
    }
}
