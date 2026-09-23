<?php

namespace Tests\Feature\Console;

use App\Models\Club;
use App\Models\CourseType;
use App\Models\Discipline;
use App\Models\Lesson;
use App\Models\Location;
use App\Models\Student;
use App\Models\Subscription;
use App\Models\SubscriptionInstance;
use App\Models\SubscriptionTemplate;
use App\Models\Teacher;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * La commande de remédiation porte des identifiants de production en dur
 * (club 11, cours 1914 → carnet 111). Les fixtures reproduisent donc ces
 * identifiants exacts, sans quoi la commande ne verrait rien à réparer.
 */
class RemediateClosure20260923Test extends TestCase
{
    use RefreshDatabase;

    private const COMMAND = 'subscriptions:remediate-closure-2026-09-23';

    private const CLUB_ID = 11;

    private const LESSON_ID = 1914;

    private const INSTANCE_ID = 111;

    #[Test]
    public function simulation_ne_rattache_rien(): void
    {
        $this->seedDetachedLesson();

        $this->artisan(self::COMMAND)->assertSuccessful();

        $this->assertDatabaseMissing('subscription_lessons', [
            'subscription_instance_id' => self::INSTANCE_ID,
            'lesson_id' => self::LESSON_ID,
        ]);
        $this->assertEquals(0, SubscriptionInstance::find(self::INSTANCE_ID)->lessons_used);
    }

    #[Test]
    public function apply_rattache_le_cours_et_recalcule_le_carnet(): void
    {
        $this->seedDetachedLesson();

        $this->artisan(self::COMMAND, ['--apply' => true, '--force' => true])->assertSuccessful();

        $this->assertDatabaseHas('subscription_lessons', [
            'subscription_instance_id' => self::INSTANCE_ID,
            'lesson_id' => self::LESSON_ID,
        ]);
        $this->assertEquals(1, SubscriptionInstance::find(self::INSTANCE_ID)->lessons_used);
    }

    #[Test]
    public function un_second_passage_est_idempotent(): void
    {
        $this->seedDetachedLesson();

        $this->artisan(self::COMMAND, ['--apply' => true, '--force' => true])->assertSuccessful();
        $this->artisan(self::COMMAND, ['--apply' => true, '--force' => true])->assertSuccessful();

        $this->assertEquals(1, DB::table('subscription_lessons')
            ->where('lesson_id', self::LESSON_ID)
            ->where('subscription_instance_id', self::INSTANCE_ID)
            ->count());
        $this->assertEquals(1, SubscriptionInstance::find(self::INSTANCE_ID)->lessons_used);
    }

    #[Test]
    public function un_cours_d_un_autre_club_est_ignore(): void
    {
        $this->seedDetachedLesson(clubIdForLesson: 99);

        $this->artisan(self::COMMAND, ['--apply' => true, '--force' => true])->assertSuccessful();

        $this->assertDatabaseMissing('subscription_lessons', [
            'subscription_instance_id' => self::INSTANCE_ID,
            'lesson_id' => self::LESSON_ID,
        ]);
    }

    #[Test]
    public function un_cours_deja_rattache_ailleurs_est_refuse_sans_ecriture(): void
    {
        $context = $this->seedDetachedLesson();

        $otherInstance = $this->makeInstance($context['subscription']->id, 777);
        DB::table('subscription_lessons')->insert([
            'subscription_instance_id' => $otherInstance->id,
            'lesson_id' => self::LESSON_ID,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan(self::COMMAND, ['--apply' => true, '--force' => true])->assertFailed();

        $this->assertDatabaseMissing('subscription_lessons', [
            'subscription_instance_id' => self::INSTANCE_ID,
            'lesson_id' => self::LESSON_ID,
        ]);
        $this->assertDatabaseHas('subscription_lessons', [
            'subscription_instance_id' => $otherInstance->id,
            'lesson_id' => self::LESSON_ID,
        ]);
    }

    /**
     * @return array{club: Club, subscription: Subscription, instance: SubscriptionInstance, lesson: Lesson}
     */
    private function seedDetachedLesson(int $clubIdForLesson = self::CLUB_ID): array
    {
        $club = Club::factory()->create();
        DB::table('clubs')->where('id', $club->id)->update(['id' => self::CLUB_ID]);
        $club = Club::find(self::CLUB_ID);

        if ($clubIdForLesson !== self::CLUB_ID) {
            $other = Club::factory()->create();
            DB::table('clubs')->where('id', $other->id)->update(['id' => $clubIdForLesson]);
        }

        $teacher = Teacher::factory()->create(['club_id' => self::CLUB_ID]);
        $student = Student::factory()->create(['club_id' => self::CLUB_ID]);
        $discipline = Discipline::factory()->create();
        $courseType = CourseType::factory()->create(['discipline_id' => $discipline->id]);
        $location = Location::factory()->create();

        $template = SubscriptionTemplate::create([
            'club_id' => self::CLUB_ID,
            'model_number' => 'REMED-0923',
            'name' => 'Carnet remédiation',
            'total_lessons' => 10,
            'validity_months' => 6,
            'price' => 200.00,
            'is_active' => true,
        ]);
        $template->courseTypes()->attach($courseType->id);

        $subscription = Subscription::create([
            'club_id' => self::CLUB_ID,
            'subscription_template_id' => $template->id,
            'subscription_number' => 'REMED-001',
        ]);

        $instance = $this->makeInstance($subscription->id, self::INSTANCE_ID);
        $instance->students()->attach($student->id);

        $lesson = Lesson::factory()->create([
            'club_id' => $clubIdForLesson,
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'course_type_id' => $courseType->id,
            'location_id' => $location->id,
            'start_time' => Carbon::parse('2026-09-23 10:00:00'),
            'end_time' => Carbon::parse('2026-09-23 11:00:00'),
            'status' => 'confirmed',
        ]);
        DB::table('lessons')->where('id', $lesson->id)->update(['id' => self::LESSON_ID]);

        return [
            'club' => $club,
            'subscription' => $subscription,
            'instance' => $instance->fresh(),
            'lesson' => Lesson::find(self::LESSON_ID),
        ];
    }

    private function makeInstance(int $subscriptionId, int $forcedId): SubscriptionInstance
    {
        $instance = SubscriptionInstance::create([
            'subscription_id' => $subscriptionId,
            'lessons_used' => 0,
            'started_at' => Carbon::parse('2026-09-01'),
            'expires_at' => Carbon::parse('2027-03-01'),
            'status' => 'active',
        ]);
        DB::table('subscription_instances')->where('id', $instance->id)->update(['id' => $forcedId]);

        return SubscriptionInstance::find($forcedId);
    }
}
