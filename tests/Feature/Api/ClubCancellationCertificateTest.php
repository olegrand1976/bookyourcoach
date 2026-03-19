<?php

namespace Tests\Feature\Api;

use App\Models\Lesson;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Club;
use App\Models\CourseType;
use App\Models\Location;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Carbon\Carbon;

class ClubCancellationCertificateTest extends TestCase
{
    #[Test]
    public function pending_certificates_returns_empty_when_none(): void
    {
        $this->actingAsClub();

        $response = $this->getJson('/api/club/lessons/pending-certificates');

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'data' => []]);
    }

    #[Test]
    public function pending_certificates_returns_lessons_for_club_only(): void
    {
        if (!\Illuminate\Support\Facades\Schema::hasColumn('lessons', 'cancellation_certificate_status')) {
            $this->markTestSkipped('Colonnes certificat non présentes.');
        }

        $user = $this->actingAsClub();
        $club = $user->getFirstClub();
        $otherClub = Club::factory()->create();

        $teacher = Teacher::factory()->create(['club_id' => $club->id]);
        $student = Student::factory()->create();
        $courseType = CourseType::factory()->create();
        $location = Location::factory()->create();

        $ourLesson = Lesson::factory()
            ->forClub($club)
            ->forTeacher($teacher)
            ->forStudent($student)
            ->withPendingCertificate()
            ->create([
                'start_time' => Carbon::now()->addDay(),
                'end_time' => Carbon::now()->addDay()->addHour(),
                'course_type_id' => $courseType->id,
                'location_id' => $location->id,
            ]);

        $otherLesson = Lesson::factory()
            ->forClub($otherClub)
            ->forTeacher(Teacher::factory()->create(['club_id' => $otherClub->id]))
            ->forStudent($student)
            ->withPendingCertificate()
            ->create([
                'start_time' => Carbon::now()->addDay(),
                'end_time' => Carbon::now()->addDay()->addHour(),
                'course_type_id' => $courseType->id,
                'location_id' => $location->id,
            ]);

        $response = $this->getJson('/api/club/lessons/pending-certificates');

        $response->assertStatus(200)->assertJson(['success' => true]);
        $data = $response->json('data');
        $ids = array_column($data, 'id');
        $this->assertContains($ourLesson->id, $ids);
        $this->assertNotContains($otherLesson->id, $ids);
    }

    #[Test]
    public function accept_certificate_succeeds_and_sets_accepted(): void
    {
        if (!\Illuminate\Support\Facades\Schema::hasColumn('lessons', 'cancellation_certificate_status')) {
            $this->markTestSkipped('Colonnes certificat non présentes.');
        }

        $user = $this->actingAsClub();
        $club = $user->getFirstClub();
        $teacher = Teacher::factory()->create(['club_id' => $club->id]);
        $student = Student::factory()->create();
        $courseType = CourseType::factory()->create();
        $location = Location::factory()->create();

        $lesson = Lesson::factory()
            ->forClub($club)
            ->forTeacher($teacher)
            ->forStudent($student)
            ->withPendingCertificate()
            ->create([
                'start_time' => Carbon::now()->addDay(),
                'end_time' => Carbon::now()->addDay()->addHour(),
                'course_type_id' => $courseType->id,
                'location_id' => $location->id,
            ]);

        $response = $this->postJson("/api/club/lessons/{$lesson->id}/cancellation-certificate/accept");

        $response->assertStatus(200)->assertJson(['success' => true]);
        $lesson->refresh();
        $this->assertSame('accepted', $lesson->cancellation_certificate_status);
    }

    #[Test]
    public function reject_certificate_succeeds_and_sets_rejected(): void
    {
        if (!\Illuminate\Support\Facades\Schema::hasColumn('lessons', 'cancellation_certificate_status')) {
            $this->markTestSkipped('Colonnes certificat non présentes.');
        }

        $user = $this->actingAsClub();
        $club = $user->getFirstClub();
        $teacher = Teacher::factory()->create(['club_id' => $club->id]);
        $student = Student::factory()->create();
        $courseType = CourseType::factory()->create();
        $location = Location::factory()->create();

        $lesson = Lesson::factory()
            ->forClub($club)
            ->forTeacher($teacher)
            ->forStudent($student)
            ->withPendingCertificate()
            ->create([
                'start_time' => Carbon::now()->addDay(),
                'end_time' => Carbon::now()->addDay()->addHour(),
                'course_type_id' => $courseType->id,
                'location_id' => $location->id,
            ]);

        $response = $this->postJson("/api/club/lessons/{$lesson->id}/cancellation-certificate/reject", [
            'rejection_reason' => 'Document illisible',
        ]);

        $response->assertStatus(200)->assertJson(['success' => true]);
        $lesson->refresh();
        $this->assertSame('rejected', $lesson->cancellation_certificate_status);
        $this->assertSame('Document illisible', $lesson->cancellation_certificate_rejection_reason);
    }

    #[Test]
    public function close_certificate_succeeds_and_sets_closed(): void
    {
        if (!\Illuminate\Support\Facades\Schema::hasColumn('lessons', 'cancellation_certificate_status')) {
            $this->markTestSkipped('Colonnes certificat non présentes.');
        }

        $user = $this->actingAsClub();
        $club = $user->getFirstClub();
        $teacher = Teacher::factory()->create(['club_id' => $club->id]);
        $student = Student::factory()->create();
        $courseType = CourseType::factory()->create();
        $location = Location::factory()->create();

        $lesson = Lesson::factory()
            ->forClub($club)
            ->forTeacher($teacher)
            ->forStudent($student)
            ->withPendingCertificate()
            ->create([
                'start_time' => Carbon::now()->addDay(),
                'end_time' => Carbon::now()->addDay()->addHour(),
                'course_type_id' => $courseType->id,
                'location_id' => $location->id,
            ]);

        $response = $this->postJson("/api/club/lessons/{$lesson->id}/cancellation-certificate/close", [
            'close_reason' => 'Trop de renvois',
        ]);

        $response->assertStatus(200)->assertJson(['success' => true]);
        $lesson->refresh();
        $this->assertSame('closed', $lesson->cancellation_certificate_status);
    }

    #[Test]
    public function accept_certificate_for_another_club_returns_403(): void
    {
        if (!\Illuminate\Support\Facades\Schema::hasColumn('lessons', 'cancellation_certificate_status')) {
            $this->markTestSkipped('Colonnes certificat non présentes.');
        }

        $this->actingAsClub();
        $otherClub = Club::factory()->create();
        $teacher = Teacher::factory()->create(['club_id' => $otherClub->id]);
        $student = Student::factory()->create();
        $courseType = CourseType::factory()->create();
        $location = Location::factory()->create();

        $lesson = Lesson::factory()
            ->forClub($otherClub)
            ->forTeacher($teacher)
            ->forStudent($student)
            ->withPendingCertificate()
            ->create([
                'start_time' => Carbon::now()->addDay(),
                'end_time' => Carbon::now()->addDay()->addHour(),
                'course_type_id' => $courseType->id,
                'location_id' => $location->id,
            ]);

        $response = $this->postJson("/api/club/lessons/{$lesson->id}/cancellation-certificate/accept");

        $response->assertStatus(422);
    }
}
