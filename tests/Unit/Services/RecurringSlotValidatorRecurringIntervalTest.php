<?php

namespace Tests\Unit\Services;

use App\Models\Club;
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
use ReflectionClass;
use Tests\TestCase;

/**
 * Les conflits « déjà réservé (récurrence) » ne doivent compter que si le créneau récurrent
 * a réellement une occurrence à cette date (recurring_interval + start_date).
 */
class RecurringSlotValidatorRecurringIntervalTest extends TestCase
{
    use RefreshDatabase;

    private function invokeFiresOnDate(SubscriptionRecurringSlot $slot, Carbon $date): bool
    {
        $validator = new RecurringSlotValidator;
        $m = (new ReflectionClass($validator))->getMethod('subscriptionRecurringSlotFiresOnDate');
        $m->setAccessible(true);

        return (bool) $m->invoke($validator, $slot, $date);
    }

    #[Test]
    public function subscription_recurring_slot_fires_on_biweekly_pattern(): void
    {
        Config::set('app.timezone', 'Europe/Paris');

        $club = Club::create([
            'name' => 'Club Interval',
            'email' => 'interval@club.com',
            'phone' => '0123456789',
            'is_active' => true,
        ]);

        $u1 = User::create(['name' => 'S1', 'email' => 's1@test.com', 'password' => bcrypt('x'), 'role' => 'student']);
        $ut = User::create(['name' => 'T', 'email' => 't@test.com', 'password' => bcrypt('x'), 'role' => 'teacher']);

        $student = Student::create(['user_id' => $u1->id, 'club_id' => $club->id]);
        $teacher = Teacher::create(['user_id' => $ut->id, 'club_id' => $club->id, 'is_available' => true]);

        $sub = Subscription::create([
            'club_id' => $club->id,
            'name' => 'Sub',
            'total_lessons' => 20,
            'free_lessons' => 0,
            'price' => 100,
            'is_active' => true,
        ]);

        $instance = SubscriptionInstance::create([
            'subscription_id' => $sub->id,
            'lessons_used' => 0,
            'started_at' => Carbon::parse('2026-01-01'),
            'expires_at' => Carbon::parse('2027-12-31'),
            'status' => 'active',
        ]);

        $slot = SubscriptionRecurringSlot::create([
            'subscription_instance_id' => $instance->id,
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'day_of_week' => Carbon::WEDNESDAY,
            'start_time' => '16:40:00',
            'end_time' => '17:00:00',
            'recurring_interval' => 2,
            'start_date' => '2026-03-25',
            'end_date' => '2027-06-30',
            'status' => 'active',
        ]);

        $this->assertTrue($this->invokeFiresOnDate($slot, Carbon::parse('2026-03-25')));
        $this->assertFalse($this->invokeFiresOnDate($slot, Carbon::parse('2026-04-01')));
        $this->assertTrue($this->invokeFiresOnDate($slot, Carbon::parse('2026-04-08')));
    }
}
