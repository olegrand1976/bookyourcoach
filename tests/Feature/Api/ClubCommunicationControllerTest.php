<?php

namespace Tests\Feature\Api;

use App\Mail\ClubGeneralCommunicationMail;
use App\Models\ClubCommunicationLog;
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
            ->assertJsonValidationErrors(['audience', 'subject', 'body']);
    }
}
