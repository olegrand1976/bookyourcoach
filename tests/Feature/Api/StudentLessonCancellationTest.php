<?php

namespace Tests\Feature\Api;

use App\Models\Lesson;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Club;
use App\Models\CourseType;
use App\Models\Location;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Carbon\Carbon;

class StudentLessonCancellationTest extends TestCase
{
    #[Test]
    public function cancel_booking_succeeds_when_active_linked_student_is_on_lesson_pivot_only(): void
    {
        $user = $this->actingAsStudent();
        $studentPrimary = $user->student;
        $studentPivot = Student::factory()->create([
            'user_id' => User::factory()->create([
                'role' => 'student',
                'email' => 'pivot-peer@example.com',
            ])->id,
        ]);
        DB::table('student_family_links')->insert([
            [
                'primary_student_id' => $studentPrimary->id,
                'linked_student_id' => $studentPivot->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'primary_student_id' => $studentPivot->id,
                'linked_student_id' => $studentPrimary->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $club = Club::factory()->create();
        $teacher = Teacher::factory()->create();
        $courseType = CourseType::factory()->create();
        $location = Location::factory()->create();

        $startTime = Carbon::now()->addHours(10);
        $lesson = Lesson::factory()
            ->forClub($club)
            ->forTeacher($teacher)
            ->forStudent($studentPrimary)
            ->create([
                'status' => 'confirmed',
                'start_time' => $startTime,
                'end_time' => $startTime->copy()->addHour(),
                'course_type_id' => $courseType->id,
                'location_id' => $location->id,
            ]);
        $lesson->students()->syncWithoutDetaching([$studentPivot->id]);

        $response = $this->postJson("/api/student/bookings/{$lesson->id}/cancel", [
            'active_student_id' => $studentPivot->id,
            'reason' => 'Annulation co-inscrit',
        ]);

        $response->assertStatus(200)->assertJson(['success' => true]);
        $lesson->refresh();
        $this->assertSame('cancelled', $lesson->status);
    }

    #[Test]
    public function cancel_booking_at_least_8h_before_succeeds_without_reason(): void
    {
        $user = $this->actingAsStudent();
        $student = $user->student;
        $club = Club::factory()->create();
        $teacher = Teacher::factory()->create();
        $courseType = CourseType::factory()->create();
        $location = Location::factory()->create();

        $startTime = Carbon::now()->addHours(10);
        $lesson = Lesson::factory()
            ->forClub($club)
            ->forTeacher($teacher)
            ->forStudent($student)
            ->create([
                'status' => 'confirmed',
                'start_time' => $startTime,
                'end_time' => $startTime->copy()->addHour(),
                'course_type_id' => $courseType->id,
                'location_id' => $location->id,
            ]);

        $response = $this->postJson("/api/student/bookings/{$lesson->id}/cancel", [
            'reason' => 'Optionnel',
        ]);

        $response->assertStatus(200)->assertJson(['success' => true]);
        $lesson->refresh();
        $this->assertSame('cancelled', $lesson->status);
    }

    #[Test]
    public function cancel_booking_less_than_8h_with_reason_other_succeeds(): void
    {
        $user = $this->actingAsStudent();
        $student = $user->student;
        $club = Club::factory()->create();
        $teacher = Teacher::factory()->create();
        $courseType = CourseType::factory()->create();
        $location = Location::factory()->create();

        $startTime = Carbon::now()->addHours(5);
        $lesson = Lesson::factory()
            ->forClub($club)
            ->forTeacher($teacher)
            ->forStudent($student)
            ->create([
                'status' => 'confirmed',
                'start_time' => $startTime,
                'end_time' => $startTime->copy()->addHour(),
                'course_type_id' => $courseType->id,
                'location_id' => $location->id,
            ]);

        $response = $this->postJson("/api/student/bookings/{$lesson->id}/cancel", [
            'cancellation_reason' => 'other',
            'reason' => 'Empêchement',
        ]);

        $response->assertStatus(200)->assertJson(['success' => true]);
        $lesson->refresh();
        $this->assertSame('cancelled', $lesson->status);
        $this->assertSame('other', $lesson->cancellation_reason);
    }

    #[Test]
    public function cancel_booking_less_than_8h_medical_without_certificate_returns_422(): void
    {
        $user = $this->actingAsStudent();
        $student = $user->student;
        $club = Club::factory()->create();
        $teacher = Teacher::factory()->create();
        $courseType = CourseType::factory()->create();
        $location = Location::factory()->create();

        $startTime = Carbon::now()->addHours(5);
        $lesson = Lesson::factory()
            ->forClub($club)
            ->forTeacher($teacher)
            ->forStudent($student)
            ->create([
                'status' => 'confirmed',
                'start_time' => $startTime,
                'end_time' => $startTime->copy()->addHour(),
                'course_type_id' => $courseType->id,
                'location_id' => $location->id,
            ]);

        $response = $this->postJson("/api/student/bookings/{$lesson->id}/cancel", [
            'cancellation_reason' => 'medical',
        ]);

        $response->assertStatus(422)
            ->assertJsonFragment(['cancellation_certificate' => ['Le certificat médical est obligatoire.']]);
    }

    #[Test]
    public function cancel_booking_less_than_8h_medical_with_certificate_sets_pending_and_submitted_by(): void
    {
        Storage::fake('public');
        $user = $this->actingAsStudent();
        $student = $user->student;
        $club = Club::factory()->create();
        $teacher = Teacher::factory()->create();
        $courseType = CourseType::factory()->create();
        $location = Location::factory()->create();

        $startTime = Carbon::now()->addHours(5);
        $lesson = Lesson::factory()
            ->forClub($club)
            ->forTeacher($teacher)
            ->forStudent($student)
            ->create([
                'status' => 'confirmed',
                'start_time' => $startTime,
                'end_time' => $startTime->copy()->addHour(),
                'course_type_id' => $courseType->id,
                'location_id' => $location->id,
            ]);

        $file = UploadedFile::fake()->create('certificat.pdf', 100, 'application/pdf');

        $response = $this->post("/api/student/bookings/{$lesson->id}/cancel", [
            'cancellation_reason' => 'medical',
            'reason' => 'Angine',
            'cancellation_certificate' => $file,
        ], [
            'Accept' => 'application/json',
        ]);

        $response->assertStatus(200)->assertJson(['success' => true]);
        $lesson->refresh();
        $this->assertSame('cancelled', $lesson->status);
        $this->assertSame('medical', $lesson->cancellation_reason);
        if (\Illuminate\Support\Facades\Schema::hasColumn('lessons', 'cancellation_certificate_status')) {
            $this->assertSame('pending', $lesson->cancellation_certificate_status);
            $this->assertNotNull($lesson->cancellation_certificate_path);
        }
        if (\Illuminate\Support\Facades\Schema::hasColumn('lessons', 'cancellation_certificate_submitted_by_student_id')) {
            $this->assertSame((int) $student->id, (int) $lesson->cancellation_certificate_submitted_by_student_id);
        }
    }

    #[Test]
    public function cancel_booking_already_cancelled_returns_400(): void
    {
        $user = $this->actingAsStudent();
        $student = $user->student;
        $club = Club::factory()->create();
        $teacher = Teacher::factory()->create();
        $courseType = CourseType::factory()->create();
        $location = Location::factory()->create();

        $startTime = Carbon::now()->addHours(10);
        $lesson = Lesson::factory()
            ->forClub($club)
            ->forTeacher($teacher)
            ->forStudent($student)
            ->cancelled()
            ->create([
                'start_time' => $startTime,
                'end_time' => $startTime->copy()->addHour(),
                'course_type_id' => $courseType->id,
                'location_id' => $location->id,
            ]);

        $response = $this->postJson("/api/student/bookings/{$lesson->id}/cancel");

        $response->assertStatus(400)->assertJson(['message' => 'Ce cours est déjà annulé.']);
    }

    #[Test]
    public function resubmit_cancellation_certificate_after_reject_succeeds(): void
    {
        Storage::fake('public');
        $user = $this->actingAsStudent();
        $student = $user->student;
        $club = Club::factory()->create();
        $teacher = Teacher::factory()->create();
        $courseType = CourseType::factory()->create();
        $location = Location::factory()->create();

        $lesson = Lesson::factory()
            ->forClub($club)
            ->forTeacher($teacher)
            ->forStudent($student)
            ->withRejectedCertificate('Document illisible')
            ->create([
                'start_time' => Carbon::now()->addDay(),
                'end_time' => Carbon::now()->addDay()->addHour(),
                'course_type_id' => $courseType->id,
                'location_id' => $location->id,
            ]);

        $file = UploadedFile::fake()->create('nouveau_cert.pdf', 100, 'application/pdf');

        $response = $this->post("/api/student/bookings/{$lesson->id}/cancellation-certificate/resubmit", [
            'cancellation_certificate' => $file,
        ], [
            'Accept' => 'application/json',
        ]);

        $response->assertStatus(200)->assertJson(['success' => true]);
        $lesson->refresh();
        if (\Illuminate\Support\Facades\Schema::hasColumn('lessons', 'cancellation_certificate_status')) {
            $this->assertSame('pending', $lesson->cancellation_certificate_status);
        }
    }

    #[Test]
    public function resubmit_certificate_when_not_rejected_returns_400(): void
    {
        $user = $this->actingAsStudent();
        $student = $user->student;
        $club = Club::factory()->create();
        $teacher = Teacher::factory()->create();
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

        $file = UploadedFile::fake()->create('cert.pdf', 100, 'application/pdf');

        $response = $this->post("/api/student/bookings/{$lesson->id}/cancellation-certificate/resubmit", [
            'cancellation_certificate' => $file,
        ], [
            'Accept' => 'application/json',
        ]);

        $response->assertStatus(400);
    }
}
