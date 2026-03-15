<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Student;
use App\Models\Club;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Support\Facades\DB;

/**
 * Tests des endpoints club pour la liaison d'élèves (fiche élève).
 */
class ClubStudentLinksTest extends TestCase
{
    use RefreshDatabase;

    private function attachStudentToClub(Student $student, int $clubId): void
    {
        DB::table('club_students')->insert([
            'club_id' => $clubId,
            'student_id' => $student->id,
            'is_active' => true,
            'joined_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    #[Test]
    public function club_can_get_linked_students()
    {
        $user = $this->actingAsClub();
        $clubId = $user->club_id;

        $primary = Student::factory()->create([
            'user_id' => User::factory()->create(['role' => 'student', 'email' => 'primary@test.com'])->id,
        ]);
        $linked = Student::factory()->create(['user_id' => null, 'first_name' => 'Enfant', 'last_name' => 'Dupont']);
        $this->attachStudentToClub($primary, $clubId);
        $this->attachStudentToClub($linked, $clubId);

        DB::table('student_family_links')->insert([
            'primary_student_id' => $primary->id,
            'linked_student_id' => $linked->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->getJson("/api/club/students/{$primary->id}/linked");

        $response->assertStatus(200)
                 ->assertJsonPath('success', true)
                 ->assertJsonStructure(['data' => [['id', 'name', 'email', 'user_id']]]);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals($linked->id, $response->json('data.0.id'));
        $this->assertNull($response->json('data.0.email'));
    }

    #[Test]
    public function club_can_link_student_with_email_to_student_without_email()
    {
        $user = $this->actingAsClub();
        $clubId = $user->club_id;

        $primary = Student::factory()->create([
            'user_id' => User::factory()->create(['role' => 'student', 'email' => 'primary@test.com'])->id,
        ]);
        $linked = Student::factory()->create(['user_id' => null]);
        $this->attachStudentToClub($primary, $clubId);
        $this->attachStudentToClub($linked, $clubId);

        $response = $this->postJson("/api/club/students/{$primary->id}/link", [
            'linked_student_id' => $linked->id,
            'relationship_type' => 'sibling',
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Les élèves ont été liés avec succès'
                 ]);
        $this->assertDatabaseHas('student_family_links', [
            'primary_student_id' => $primary->id,
            'linked_student_id' => $linked->id,
        ]);
        $this->assertDatabaseCount('student_family_links', 1);
    }

    #[Test]
    public function club_cannot_link_when_primary_has_no_email()
    {
        $user = $this->actingAsClub();
        $clubId = $user->club_id;

        $primary = Student::factory()->create(['user_id' => null]);
        $linked = Student::factory()->create(['user_id' => null]);
        $this->attachStudentToClub($primary, $clubId);
        $this->attachStudentToClub($linked, $clubId);

        $response = $this->postJson("/api/club/students/{$primary->id}/link", [
            'linked_student_id' => $linked->id,
        ]);

        $response->assertStatus(422)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Seul un élève avec compte (email) peut être le compte principal'
                 ]);
        $this->assertDatabaseCount('student_family_links', 0);
    }

    #[Test]
    public function club_cannot_link_student_from_another_club()
    {
        $user = $this->actingAsClub();
        $clubId = $user->club_id;
        $otherClub = Club::factory()->create();

        $primary = Student::factory()->create([
            'user_id' => User::factory()->create(['role' => 'student', 'email' => 'primary@test.com'])->id,
        ]);
        $linked = Student::factory()->create(['user_id' => null]);
        $this->attachStudentToClub($primary, $clubId);
        $this->attachStudentToClub($linked, $otherClub->id);

        $response = $this->postJson("/api/club/students/{$primary->id}/link", [
            'linked_student_id' => $linked->id,
        ]);

        $response->assertStatus(403)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Les deux élèves doivent appartenir à votre club'
                 ]);
        $this->assertDatabaseCount('student_family_links', 0);
    }

    #[Test]
    public function club_can_unlink_students()
    {
        $user = $this->actingAsClub();
        $clubId = $user->club_id;

        $primary = Student::factory()->create([
            'user_id' => User::factory()->create(['role' => 'student', 'email' => 'p@test.com'])->id,
        ]);
        $linked = Student::factory()->create(['user_id' => null]);
        $this->attachStudentToClub($primary, $clubId);
        $this->attachStudentToClub($linked, $clubId);

        DB::table('student_family_links')->insert([
            'primary_student_id' => $primary->id,
            'linked_student_id' => $linked->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->deleteJson("/api/club/students/{$primary->id}/unlink/{$linked->id}");

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Les élèves ont été déliés avec succès'
                 ]);
        $this->assertDatabaseMissing('student_family_links', [
            'primary_student_id' => $primary->id,
            'linked_student_id' => $linked->id,
        ]);
    }

    #[Test]
    public function club_available_for_linking_returns_only_club_students()
    {
        $user = $this->actingAsClub();
        $clubId = $user->club_id;

        $inClub = Student::factory()->create(['user_id' => null, 'first_name' => 'In', 'last_name' => 'Club']);
        $otherClub = Club::factory()->create();
        $inOtherClub = Student::factory()->create(['user_id' => null, 'first_name' => 'Other', 'last_name' => 'Club']);
        $this->attachStudentToClub($inClub, $clubId);
        $this->attachStudentToClub($inOtherClub, $otherClub->id);

        $response = $this->getJson('/api/club/students/available-for-linking?exclude_student_id=99999');

        $response->assertStatus(200)
                 ->assertJsonPath('success', true);
        $ids = array_column($response->json('data'), 'id');
        $this->assertContains($inClub->id, $ids);
        $this->assertNotContains($inOtherClub->id, $ids);
    }
}
