<?php

namespace Tests\Unit\Services;

use App\Models\Club;
use App\Models\CourseType;
use App\Models\Discipline;
use App\Models\Lesson;
use App\Models\Location;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Services\ClubPlanningInsightService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ClubPlanningInsightServiceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_groups_participants_with_same_last_name(): void
    {
        $club = Club::create([
            'name' => 'Club Insight',
            'email' => 'club@example.com',
            'phone' => '0123456789',
            'is_active' => true,
        ]);

        $discipline = Discipline::create([
            'name' => 'Discipline',
            'slug' => 'discipline-insight',
            'is_active' => true,
        ]);

        $courseType = CourseType::create([
            'name' => 'Cours',
            'duration_minutes' => 60,
            'price' => 40.00,
            'discipline_id' => $discipline->id,
        ]);

        $location = Location::create([
            'name' => 'Salle A',
            'address' => '1 rue Test',
            'city' => 'Ville',
            'postal_code' => '1000',
            'country' => 'BE',
        ]);

        $teacherUser = User::create([
            'name' => 'Coach',
            'email' => 'coach@example.com',
            'password' => bcrypt('password'),
            'role' => 'teacher',
        ]);

        $teacher = Teacher::create([
            'user_id' => $teacherUser->id,
            'club_id' => $club->id,
            'is_available' => true,
        ]);

        $u1 = User::create(['name' => 'A Dupont', 'email' => 'a@example.com', 'password' => bcrypt('x'), 'role' => 'student']);
        $u2 = User::create(['name' => 'B Dupont', 'email' => 'b@example.com', 'password' => bcrypt('x'), 'role' => 'student']);

        $s1 = Student::create([
            'user_id' => $u1->id,
            'club_id' => $club->id,
            'first_name' => 'Alice',
            'last_name' => 'Dupont',
        ]);
        $s2 = Student::create([
            'user_id' => $u2->id,
            'club_id' => $club->id,
            'first_name' => 'Bob',
            'last_name' => 'Dupont',
        ]);

        $target = Carbon::parse('2026-06-10', 'Europe/Brussels');

        Lesson::create([
            'club_id' => $club->id,
            'teacher_id' => $teacher->id,
            'student_id' => $s1->id,
            'course_type_id' => $courseType->id,
            'location_id' => $location->id,
            'start_time' => $target->copy()->setTime(9, 0),
            'end_time' => $target->copy()->setTime(10, 0),
            'status' => 'confirmed',
            'price' => 40,
        ]);

        Lesson::create([
            'club_id' => $club->id,
            'teacher_id' => $teacher->id,
            'student_id' => $s2->id,
            'course_type_id' => $courseType->id,
            'location_id' => $location->id,
            'start_time' => $target->copy()->setTime(14, 0),
            'end_time' => $target->copy()->setTime(15, 0),
            'status' => 'confirmed',
            'price' => 40,
        ]);

        $service = new ClubPlanningInsightService;
        $payload = $service->buildPayload($club, $target, 'Europe/Brussels');

        $this->assertCount(2, $payload['lessons']);
        $this->assertCount(1, $payload['family_constraint_groups']);
        $this->assertEqualsCanonicalizing([$s1->id, $s2->id], $payload['family_constraint_groups'][0]['student_ids']);
    }
}
