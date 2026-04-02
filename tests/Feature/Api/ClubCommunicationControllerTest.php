<?php

namespace Tests\Feature\Api;

use App\Mail\ClubGeneralCommunicationMail;
use App\Models\ClubCommunicationLog;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ClubCommunicationControllerTest extends TestCase
{
    #[Test]
    public function guest_cannot_access_recipient_counts(): void
    {
        $this->getJson('/api/club/communications/recipient-counts')->assertStatus(401);
    }

    #[Test]
    public function teacher_cannot_access_recipient_counts(): void
    {
        $this->actingAsTeacher();
        $this->getJson('/api/club/communications/recipient-counts')->assertStatus(403);
    }

    #[Test]
    public function club_can_get_recipient_counts(): void
    {
        $this->actingAsClub();

        $response = $this->getJson('/api/club/communications/recipient-counts');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'data' => [
                    'teachers_with_email',
                    'students_with_email',
                    'unique_total_for_both',
                ],
            ]);
    }

    #[Test]
    public function club_can_send_communication_to_teachers(): void
    {
        Mail::fake();

        $user = $this->actingAsClub();
        $club = $user->clubs()->first();

        $teacherUser = User::factory()->create([
            'role' => 'teacher',
            'status' => 'active',
            'is_active' => true,
            'email' => 'prof-comm-test@example.com',
        ]);
        $teacher = Teacher::factory()->create(['user_id' => $teacherUser->id]);
        $teacher->clubs()->attach($club->id, ['is_active' => true, 'joined_at' => now()]);

        $response = $this->postJson('/api/club/communications/send', [
            'selection_mode' => 'all',
            'audience' => 'teachers',
            'subject' => 'Infos club',
            'body' => "Ligne 1\nLigne 2",
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        Mail::assertSent(ClubGeneralCommunicationMail::class, function (ClubGeneralCommunicationMail $mail) {
            return $mail->mailSubject === 'Infos club'
                && str_contains($mail->bodyText, 'Ligne 1');
        });

        $this->assertDatabaseHas('club_communication_logs', [
            'club_id' => $club->id,
            'sent_by_user_id' => $user->id,
            'audience' => 'teachers',
            'selection_mode' => 'all',
            'subject' => 'Infos club',
            'recipient_count' => 1,
            'sent_count' => 1,
            'failed_count' => 0,
        ]);
    }

    #[Test]
    public function send_fails_when_no_valid_recipients(): void
    {
        $this->actingAsClub();

        $response = $this->postJson('/api/club/communications/send', [
            'selection_mode' => 'all',
            'audience' => 'teachers',
            'subject' => 'Test',
            'body' => 'Corps',
        ]);

        $response->assertStatus(422)
            ->assertJson(['success' => false]);

        $this->assertSame(0, ClubCommunicationLog::count());
    }

    #[Test]
    public function send_validates_required_fields(): void
    {
        $this->actingAsClub();

        $this->postJson('/api/club/communications/send', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['selection_mode', 'subject', 'body']);
    }

    #[Test]
    public function club_can_list_contacts_for_selection(): void
    {
        $user = $this->actingAsClub();
        $club = $user->clubs()->first();

        $teacherUser = User::factory()->create([
            'role' => 'teacher',
            'status' => 'active',
            'is_active' => true,
            'email' => 'prof-contacts@example.com',
            'name' => 'Prof Contacts',
        ]);
        $teacher = Teacher::factory()->create(['user_id' => $teacherUser->id]);
        $teacher->clubs()->attach($club->id, ['is_active' => true, 'joined_at' => now()]);

        $studentUser = User::factory()->create([
            'role' => 'student',
            'status' => 'active',
            'is_active' => true,
            'email' => 'eleve-contacts@example.com',
            'name' => 'Élève Contacts',
        ]);
        $student = Student::factory()->create(['user_id' => $studentUser->id]);
        $student->clubs()->attach($club->id, ['is_active' => true, 'joined_at' => now()]);

        $response = $this->getJson('/api/club/communications/contacts');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.teachers.0.email', 'prof-contacts@example.com')
            ->assertJsonPath('data.students.0.email', 'eleve-contacts@example.com');
    }

    #[Test]
    public function club_can_paginate_history_by_scope(): void
    {
        $user = $this->actingAsClub();
        $club = $user->clubs()->first();

        ClubCommunicationLog::create([
            'club_id' => $club->id,
            'sent_by_user_id' => $user->id,
            'audience' => 'teachers',
            'selection_mode' => 'all',
            'selected_teacher_ids' => null,
            'selected_student_ids' => null,
            'subject' => 'Sujet T',
            'body' => 'Corps',
            'recipient_count' => 2,
            'sent_count' => 2,
            'failed_count' => 0,
            'teacher_recipient_count' => 2,
            'student_recipient_count' => 0,
        ]);

        ClubCommunicationLog::create([
            'club_id' => $club->id,
            'sent_by_user_id' => $user->id,
            'audience' => 'students',
            'selection_mode' => 'all',
            'selected_teacher_ids' => null,
            'selected_student_ids' => null,
            'subject' => 'Sujet S',
            'body' => 'Corps',
            'recipient_count' => 1,
            'sent_count' => 1,
            'failed_count' => 0,
            'teacher_recipient_count' => 0,
            'student_recipient_count' => 1,
        ]);

        $this->getJson('/api/club/communications/history?scope=teachers')
            ->assertStatus(200)
            ->assertJsonPath('data.items.0.subject', 'Sujet T');

        $this->getJson('/api/club/communications/history?scope=students')
            ->assertStatus(200)
            ->assertJsonPath('data.items.0.subject', 'Sujet S');
    }

    #[Test]
    public function club_can_send_to_selected_teacher_only(): void
    {
        Mail::fake();

        $user = $this->actingAsClub();
        $club = $user->clubs()->first();

        $teacherUser = User::factory()->create([
            'role' => 'teacher',
            'status' => 'active',
            'is_active' => true,
            'email' => 'prof-sel@example.com',
        ]);
        $teacher = Teacher::factory()->create(['user_id' => $teacherUser->id]);
        $teacher->clubs()->attach($club->id, ['is_active' => true, 'joined_at' => now()]);

        $response = $this->postJson('/api/club/communications/send', [
            'selection_mode' => 'selected',
            'teacher_ids' => [$teacher->id],
            'student_ids' => [],
            'subject' => 'Ciblé',
            'body' => 'Message',
        ]);

        $response->assertStatus(200)->assertJson(['success' => true]);

        Mail::assertSent(ClubGeneralCommunicationMail::class);

        $this->assertDatabaseHas('club_communication_logs', [
            'club_id' => $club->id,
            'selection_mode' => 'selected',
            'audience' => 'teachers',
        ]);
    }
}
