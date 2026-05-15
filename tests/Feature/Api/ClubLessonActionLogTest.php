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

        $response = $this->getJson('/api/club/lesson-action-logs');

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
}
