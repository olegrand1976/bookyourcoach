<?php

namespace Tests\Feature\Api;

use App\Models\CourseType;
use App\Models\Lesson;
use App\Models\LessonActionLog;
use App\Models\Location;
use App\Models\Student;
use App\Models\Teacher;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ClubLessonActionLogTest extends TestCase
{
    #[Test]
    public function club_can_list_lesson_action_logs(): void
    {
        if (! Schema::hasTable('lesson_action_logs')) {
            $this->markTestSkipped('Table lesson_action_logs absente.');
        }

        $user = $this->actingAsClub();
        $club = $user->getFirstClub();
        $teacher = Teacher::factory()->create(['club_id' => $club->id]);
        $student = Student::factory()->create();
        $courseType = CourseType::factory()->create();
        $location = Location::factory()->create();

        $start = Carbon::now()->addDays(2);
        $lesson = Lesson::factory()
            ->forClub($club)
            ->forTeacher($teacher)
            ->forStudent($student)
            ->create([
                'start_time' => $start,
                'end_time' => $start->copy()->addHour(),
                'course_type_id' => $courseType->id,
                'location_id' => $location->id,
            ]);

        LessonActionLog::create([
            'club_id' => $club->id,
            'lesson_id' => $lesson->id,
            'student_id' => $student->id,
            'performed_by_user_id' => $user->id,
            'performed_by_role' => 'club',
            'action' => LessonActionLog::ACTION_CREATED,
            'meta' => ['student_names' => ['Test Eleve']],
        ]);

        $response = $this->getJson('/api/club/lesson-action-logs?action=' . LessonActionLog::ACTION_CREATED);

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonPath('data.0.action', LessonActionLog::ACTION_CREATED)
            ->assertJsonPath('data.0.student_id', $student->id);
    }

    #[Test]
    public function list_can_filter_by_student_id(): void
    {
        if (! Schema::hasTable('lesson_action_logs')) {
            $this->markTestSkipped('Table lesson_action_logs absente.');
        }

        $user = $this->actingAsClub();
        $club = $user->getFirstClub();
        $studentA = Student::factory()->create();
        $studentB = Student::factory()->create();

        LessonActionLog::create([
            'club_id' => $club->id,
            'student_id' => $studentA->id,
            'action' => LessonActionLog::ACTION_CANCELLED,
            'performed_by_role' => 'club',
        ]);
        LessonActionLog::create([
            'club_id' => $club->id,
            'student_id' => $studentB->id,
            'action' => LessonActionLog::ACTION_CANCELLED,
            'performed_by_role' => 'club',
        ]);

        $response = $this->getJson('/api/club/lesson-action-logs?student_id=' . $studentA->id);

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertSame($studentA->id, $response->json('data.0.student_id'));
    }

    #[Test]
    public function cancelled_filter_includes_student_cancelled_logs(): void
    {
        if (! Schema::hasTable('lesson_action_logs')) {
            $this->markTestSkipped('Table lesson_action_logs absente.');
        }

        $user = $this->actingAsClub();
        $club = $user->getFirstClub();
        $student = Student::factory()->create();

        LessonActionLog::create([
            'club_id' => $club->id,
            'student_id' => $student->id,
            'action' => LessonActionLog::ACTION_STUDENT_CANCELLED,
            'performed_by_role' => 'student',
            'meta' => ['student_names' => ['Eleve Test']],
        ]);

        $response = $this->getJson('/api/club/lesson-action-logs?action=' . LessonActionLog::ACTION_CANCELLED);

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertSame(LessonActionLog::ACTION_STUDENT_CANCELLED, $response->json('data.0.action'));
    }

    #[Test]
    public function student_filter_matches_lesson_when_log_student_id_is_null(): void
    {
        if (! Schema::hasTable('lesson_action_logs')) {
            $this->markTestSkipped('Table lesson_action_logs absente.');
        }

        $user = $this->actingAsClub();
        $club = $user->getFirstClub();
        $teacher = Teacher::factory()->create(['club_id' => $club->id]);
        $student = Student::factory()->create();
        $courseType = CourseType::factory()->create();
        $location = Location::factory()->create();

        $start = Carbon::now()->addDays(2);
        $lesson = Lesson::factory()
            ->forClub($club)
            ->forTeacher($teacher)
            ->forStudent($student)
            ->create([
                'start_time' => $start,
                'end_time' => $start->copy()->addHour(),
                'course_type_id' => $courseType->id,
                'location_id' => $location->id,
            ]);

        LessonActionLog::create([
            'club_id' => $club->id,
            'lesson_id' => $lesson->id,
            'student_id' => null,
            'action' => LessonActionLog::ACTION_STUDENT_CANCELLED,
            'performed_by_role' => 'student',
        ]);

        $response = $this->getJson('/api/club/lesson-action-logs?student_id=' . $student->id . '&action=' . LessonActionLog::ACTION_CANCELLED);

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertSame($lesson->id, $response->json('data.0.lesson_id'));
    }

    #[Test]
    public function index_backfills_cancelled_lesson_without_log(): void
    {
        if (! Schema::hasTable('lesson_action_logs')) {
            $this->markTestSkipped('Table lesson_action_logs absente.');
        }

        $user = $this->actingAsClub();
        $club = $user->getFirstClub();
        $teacher = Teacher::factory()->create(['club_id' => $club->id]);
        $student = Student::factory()->create();
        $courseType = CourseType::factory()->create();
        $location = Location::factory()->create();

        $start = Carbon::now()->subDays(5);
        $lesson = Lesson::factory()
            ->forClub($club)
            ->forTeacher($teacher)
            ->forStudent($student)
            ->create([
                'start_time' => $start,
                'end_time' => $start->copy()->addHour(),
                'course_type_id' => $courseType->id,
                'location_id' => $location->id,
                'status' => 'cancelled',
                'notes' => "[Annulé par l'élève] Test",
            ]);

        $this->assertFalse(
            LessonActionLog::query()
                ->where('lesson_id', $lesson->id)
                ->whereIn('action', LessonActionLog::CANCELLATION_ACTIONS)
                ->exists()
        );

        $from = Carbon::now()->subDays(30)->toDateString();
        $response = $this->getJson('/api/club/lesson-action-logs?student_id=' . $student->id . '&action=' . LessonActionLog::ACTION_CANCELLED . '&from=' . $from);

        $response->assertStatus(200);
        $lessonIds = collect($response->json('data'))->pluck('lesson_id');
        $this->assertTrue($lessonIds->contains($lesson->id));

        $this->assertTrue(
            LessonActionLog::query()
                ->where('lesson_id', $lesson->id)
                ->where('action', LessonActionLog::ACTION_STUDENT_CANCELLED)
                ->exists()
        );
    }

    #[Test]
    public function date_filter_matches_lesson_start_time_when_log_created_at_is_older(): void
    {
        if (! Schema::hasTable('lesson_action_logs')) {
            $this->markTestSkipped('Table lesson_action_logs absente.');
        }

        $user = $this->actingAsClub();
        $club = $user->getFirstClub();
        $teacher = Teacher::factory()->create(['club_id' => $club->id]);
        $student = Student::factory()->create();
        $courseType = CourseType::factory()->create();
        $location = Location::factory()->create();

        $lessonStart = Carbon::now()->addDays(10);
        $lesson = Lesson::factory()
            ->forClub($club)
            ->forTeacher($teacher)
            ->forStudent($student)
            ->create([
                'start_time' => $lessonStart,
                'end_time' => $lessonStart->copy()->addHour(),
                'course_type_id' => $courseType->id,
                'location_id' => $location->id,
            ]);

        $log = LessonActionLog::create([
            'club_id' => $club->id,
            'lesson_id' => $lesson->id,
            'student_id' => $student->id,
            'action' => LessonActionLog::ACTION_CANCELLED,
            'performed_by_role' => 'club',
            'meta' => ['lesson_start_time' => $lessonStart->toIso8601String()],
        ]);
        $log->created_at = Carbon::now()->subDays(120);
        $log->updated_at = Carbon::now()->subDays(120);
        $log->saveQuietly();

        $from = Carbon::now()->subDays(7)->toDateString();
        $response = $this->getJson('/api/club/lesson-action-logs?student_id=' . $student->id . '&from=' . $from . '&action=' . LessonActionLog::ACTION_CANCELLED);

        $response->assertStatus(200);
        $this->assertTrue(collect($response->json('data'))->pluck('lesson_id')->contains($lesson->id));
    }
}
