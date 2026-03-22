<?php

namespace Tests\Unit\Services;

use App\Models\Club;
use App\Models\CourseType;
use App\Models\Lesson;
use App\Models\Location;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Services\RecurringSlotValidator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Cas prod : cours stockés en UTC, validation récurrence avec heures « club » en Europe/Paris.
 * L'ancien whereDate + whereTime sur colonnes UTC provoquait des faux conflits (bords de jour / composantes horaires).
 */
class RecurringSlotValidatorLessonOverlapTest extends TestCase
{
    use RefreshDatabase;

    private Club $club;

    private Student $student;

    private Teacher $teacher;

    private Teacher $otherTeacher;

    private Location $location;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('app.timezone', 'Europe/Paris');

        $this->club = Club::create([
            'name' => 'Club Overlap',
            'email' => 'overlap@club.com',
            'phone' => '0123456789',
            'is_active' => true,
        ]);

        $studentUser = User::create([
            'name' => 'Élève Overlap',
            'email' => 'student-overlap@test.com',
            'password' => bcrypt('password'),
            'role' => 'student',
        ]);
        $this->student = Student::create([
            'user_id' => $studentUser->id,
            'club_id' => $this->club->id,
        ]);

        $teacherUser = User::create([
            'name' => 'Prof A',
            'email' => 'prof-a@test.com',
            'password' => bcrypt('password'),
            'role' => 'teacher',
        ]);
        $this->teacher = Teacher::create([
            'user_id' => $teacherUser->id,
            'club_id' => $this->club->id,
            'is_available' => true,
        ]);

        $teacherUserB = User::create([
            'name' => 'Prof B',
            'email' => 'prof-b@test.com',
            'password' => bcrypt('password'),
            'role' => 'teacher',
        ]);
        $this->otherTeacher = Teacher::create([
            'user_id' => $teacherUserB->id,
            'club_id' => $this->club->id,
            'is_available' => true,
        ]);

        $this->location = Location::create([
            'name' => 'Lieu test',
            'address' => 'Rue 1',
            'city' => 'Ville',
            'postal_code' => '1000',
            'country' => 'BE',
        ]);
    }

    #[Test]
    public function validate_recurring_without_open_slot_allows_adjacent_lesson_before_in_utc(): void
    {
        $courseType = CourseType::factory()->create(['club_id' => $this->club->id]);

        // Mercredi 25 mars 2026 (CET) : cours existant 16:20–16:40 Paris = 15:20–15:40 UTC
        Lesson::create([
            'club_id' => $this->club->id,
            'student_id' => $this->student->id,
            'teacher_id' => $this->otherTeacher->id,
            'course_type_id' => $courseType->id,
            'location_id' => $this->location->id,
            'start_time' => '2026-03-25 15:20:00',
            'end_time' => '2026-03-25 15:40:00',
            'status' => 'confirmed',
            'price' => 18,
        ]);

        $validator = new RecurringSlotValidator;
        $wed = Carbon::WEDNESDAY;
        $result = $validator->validateRecurringAvailabilityWithoutOpenSlot(
            $this->teacher->id,
            $this->student->id,
            '2026-03-25',
            $wed,
            '16:40:00',
            '17:00:00',
            1
        );

        $this->assertTrue($result['valid'], 'Pas de chevauchement réel : cours se termine à 16:40 Paris, nouveau à 16:40 Paris. Message: ' . ($result['message'] ?? ''));
        $this->assertSame([], $result['conflicts']);
    }

    #[Test]
    public function validate_recurring_without_open_slot_detects_real_overlap(): void
    {
        $courseType = CourseType::factory()->create(['club_id' => $this->club->id]);

        Lesson::create([
            'club_id' => $this->club->id,
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'course_type_id' => $courseType->id,
            'location_id' => $this->location->id,
            'start_time' => '2026-03-25 15:30:00',
            'end_time' => '2026-03-25 15:50:00',
            'status' => 'confirmed',
            'price' => 18,
        ]);

        $validator = new RecurringSlotValidator;
        $result = $validator->validateRecurringAvailabilityWithoutOpenSlot(
            $this->teacher->id,
            $this->student->id,
            '2026-03-25',
            Carbon::WEDNESDAY,
            '16:40:00',
            '17:00:00',
            1
        );

        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['conflicts']);
    }
}
