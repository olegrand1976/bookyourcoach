<?php

namespace Tests\Unit\Services;

use App\Models\Club;
use App\Models\CourseType;
use App\Models\Lesson;
use App\Models\Location;
use App\Models\Student;
use App\Models\Subscription;
use App\Models\SubscriptionInstance;
use App\Models\SubscriptionRecurringSlot;
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

    #[Test]
    public function same_lesson_blocking_teacher_and_student_yields_single_lesson_overlap(): void
    {
        $courseType = CourseType::factory()->create(['club_id' => $this->club->id]);

        $lesson = Lesson::create([
            'club_id' => $this->club->id,
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'course_type_id' => $courseType->id,
            'location_id' => $this->location->id,
            'start_time' => '2026-03-25 16:00:00',
            'end_time' => '2026-03-25 16:20:00',
            'status' => 'confirmed',
            'price' => 18,
        ]);

        $validator = new RecurringSlotValidator;
        $result = $validator->validateRecurringAvailabilityWithoutOpenSlot(
            $this->teacher->id,
            $this->student->id,
            '2026-03-25',
            Carbon::WEDNESDAY,
            '17:00:00',
            '17:20:00',
            1,
            null,
            (int) $this->club->id
        );

        $this->assertFalse($result['valid']);
        $mar25 = array_values(array_filter($result['conflicts'], fn ($c) => ($c['date'] ?? '') === '2026-03-25'));
        $this->assertCount(1, $mar25);
        $this->assertSame('lesson_overlap', $mar25[0]['type'] ?? '');
        $this->assertSame((int) $lesson->id, (int) ($mar25[0]['lesson_id'] ?? 0));
    }

    /**
     * Cours matérialisé sans lien élève en base (student_id null) + récurrence active :
     * enseignant voit le cours, élève voit la récurrence — doit fusionner en un seul recurring_duplicate.
     */
    #[Test]
    public function teacher_lesson_and_student_recurring_same_pair_merge_to_single_recurring_duplicate(): void
    {
        $courseType = CourseType::factory()->create(['club_id' => $this->club->id]);

        $subscription = Subscription::create([
            'club_id' => $this->club->id,
            'subscription_number' => 'MERGE-001',
        ]);

        $instance = SubscriptionInstance::create([
            'subscription_id' => $subscription->id,
            'lessons_used' => 0,
            'started_at' => '2026-01-01',
            'expires_at' => '2027-12-31',
            'status' => 'active',
        ]);
        $instance->students()->sync([$this->student->id]);

        $recurring = SubscriptionRecurringSlot::create([
            'subscription_instance_id' => $instance->id,
            'open_slot_id' => null,
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'day_of_week' => Carbon::WEDNESDAY,
            'start_time' => '17:00:00',
            'end_time' => '17:20:00',
            'recurring_interval' => 1,
            'start_date' => '2026-03-25',
            'end_date' => '2027-12-31',
            'status' => 'active',
        ]);

        // 17:00–17:20 Europe/Paris (CET) → 16:00–16:20 UTC le 25/03/2026
        $lesson = Lesson::create([
            'club_id' => $this->club->id,
            'student_id' => null,
            'teacher_id' => $this->teacher->id,
            'course_type_id' => $courseType->id,
            'location_id' => $this->location->id,
            'start_time' => '2026-03-25 16:00:00',
            'end_time' => '2026-03-25 16:20:00',
            'status' => 'confirmed',
            'price' => 18,
        ]);

        $validator = new RecurringSlotValidator;
        $result = $validator->validateRecurringAvailabilityWithoutOpenSlot(
            $this->teacher->id,
            $this->student->id,
            '2026-03-25',
            Carbon::WEDNESDAY,
            '17:00:00',
            '17:20:00',
            1,
            null,
            (int) $this->club->id
        );

        $this->assertFalse($result['valid']);
        $mar25 = array_values(array_filter($result['conflicts'], fn ($c) => ($c['date'] ?? '') === '2026-03-25'));
        $this->assertCount(1, $mar25, 'Un seul conflit le 25/03 (pas enseignant + élève séparés)');
        $this->assertSame('recurring_duplicate', $mar25[0]['type'] ?? '');
        $this->assertSame((int) $recurring->id, (int) ($mar25[0]['recurring_slot_id'] ?? 0));
        $this->assertSame((int) $lesson->id, (int) ($mar25[0]['lesson_id'] ?? 0));
    }
}
