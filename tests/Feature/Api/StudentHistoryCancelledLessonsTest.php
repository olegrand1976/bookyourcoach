<?php

namespace Tests\Feature\Api;

use App\Models\Club;
use App\Models\CourseType;
use App\Models\Lesson;
use App\Models\LessonActionLog;
use App\Models\Location;
use App\Models\Student;
use App\Models\Subscription;
use App\Models\SubscriptionInstance;
use App\Models\SubscriptionTemplate;
use App\Models\Teacher;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StudentHistoryCancelledLessonsTest extends TestCase
{
    use RefreshDatabase;

    private Club $club;

    private Teacher $teacher;

    private Student $student;

    private CourseType $courseType;

    private Location $location;

    protected function setUp(): void
    {
        parent::setUp();

        $user = $this->actingAsClub();
        $this->club = Club::find($user->club_id);
        $this->teacher = Teacher::factory()->create(['club_id' => $this->club->id]);
        $this->student = Student::factory()->create(['club_id' => $this->club->id]);
        $this->courseType = CourseType::factory()->create();
        $this->location = Location::factory()->create();

        DB::table('club_students')->insert([
            'club_id' => $this->club->id,
            'student_id' => $this->student->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $template = SubscriptionTemplate::create([
            'club_id' => $this->club->id,
            'name' => 'Abo test',
            'model_number' => 'HIST001',
            'total_lessons' => 20,
            'price' => 200,
            'validity_months' => 6,
            'is_active' => true,
        ]);

        $subscription = Subscription::create([
            'club_id' => $this->club->id,
            'template_id' => $template->id,
            'student_id' => $this->student->id,
            'name' => 'Abo test',
            'total_lessons' => 20,
            'price' => 200,
        ]);

        SubscriptionInstance::create([
            'subscription_id' => $subscription->id,
            'student_id' => $this->student->id,
            'status' => 'active',
            'lessons_remaining' => 20,
            'lessons_used' => 0,
            'started_at' => now(),
            'expires_at' => now()->addMonths(6),
        ]);
    }

    #[Test]
    public function history_includes_cancelled_lessons_beyond_recent_limit(): void
    {
        for ($i = 0; $i < 105; $i++) {
            Lesson::factory()->create([
                'club_id' => $this->club->id,
                'teacher_id' => $this->teacher->id,
                'student_id' => $this->student->id,
                'course_type_id' => $this->courseType->id,
                'location_id' => $this->location->id,
                'start_time' => now()->subDays($i + 10)->setTime(10, 0),
                'end_time' => now()->subDays($i + 10)->setTime(11, 0),
                'status' => 'completed',
            ]);
        }

        $cancelledOld = Lesson::factory()->create([
            'club_id' => $this->club->id,
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'course_type_id' => $this->courseType->id,
            'location_id' => $this->location->id,
            'start_time' => now()->subYear()->setTime(14, 0),
            'end_time' => now()->subYear()->setTime(15, 0),
            'status' => 'cancelled',
        ]);

        $response = $this->getJson("/api/club/students/{$this->student->id}/history");

        $response->assertStatus(200);
        $lessonIds = collect($response->json('data.lessons'))->pluck('id');
        $this->assertTrue($lessonIds->contains($cancelledOld->id));

        $cancelledInStats = $response->json('data.stats.cancelled_lessons');
        $this->assertGreaterThanOrEqual(1, $cancelledInStats);
    }

    #[Test]
    public function history_excludes_lessons_from_other_clubs(): void
    {
        $otherClub = Club::factory()->create();
        $otherTeacher = Teacher::factory()->create(['club_id' => $otherClub->id]);

        $otherLesson = Lesson::factory()->create([
            'club_id' => $otherClub->id,
            'teacher_id' => $otherTeacher->id,
            'student_id' => $this->student->id,
            'course_type_id' => $this->courseType->id,
            'location_id' => $this->location->id,
            'start_time' => now()->subDay(),
            'end_time' => now(),
            'status' => 'cancelled',
        ]);

        $ownLesson = Lesson::factory()->create([
            'club_id' => $this->club->id,
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'course_type_id' => $this->courseType->id,
            'location_id' => $this->location->id,
            'start_time' => now()->subDay(),
            'end_time' => now(),
            'status' => 'confirmed',
        ]);

        $response = $this->getJson("/api/club/students/{$this->student->id}/history");

        $response->assertStatus(200);
        $lessonIds = collect($response->json('data.lessons'))->pluck('id');
        $this->assertTrue($lessonIds->contains($ownLesson->id));
        $this->assertFalse($lessonIds->contains($otherLesson->id));
    }

    #[Test]
    public function club_simple_delete_creates_cancelled_lesson_visible_in_history_and_action_log(): void
    {
        if (! Schema::hasTable('lesson_action_logs')) {
            $this->markTestSkipped('Table lesson_action_logs absente.');
        }

        $lesson = Lesson::factory()->create([
            'club_id' => $this->club->id,
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'course_type_id' => $this->courseType->id,
            'location_id' => $this->location->id,
            'start_time' => Carbon::now()->addDays(3),
            'end_time' => Carbon::now()->addDays(3)->addHour(),
            'status' => 'confirmed',
        ]);

        $deleteResponse = $this->deleteJson("/api/lessons/{$lesson->id}");
        $deleteResponse->assertStatus(200)->assertJsonPath('message', 'Cours annulé avec succès');

        $historyResponse = $this->getJson("/api/club/students/{$this->student->id}/history");
        $historyResponse->assertStatus(200);
        $historyIds = collect($historyResponse->json('data.lessons'))->pluck('id');
        $this->assertTrue($historyIds->contains($lesson->id));

        $cancelledRow = collect($historyResponse->json('data.lessons'))->firstWhere('id', $lesson->id);
        $this->assertSame('cancelled', $cancelledRow['status']);

        $logsResponse = $this->getJson('/api/club/lesson-action-logs?action=' . LessonActionLog::ACTION_CANCELLED);
        $logsResponse->assertStatus(200);
        $logLessonIds = collect($logsResponse->json('data'))->pluck('lesson_id');
        $this->assertTrue($logLessonIds->contains($lesson->id));
    }
}
