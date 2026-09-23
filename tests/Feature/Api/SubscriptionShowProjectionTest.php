<?php

namespace Tests\Feature\Api;

use App\Models\Club;
use App\Models\CourseType;
use App\Models\Lesson;
use App\Models\Location;
use App\Models\Student;
use App\Models\Subscription;
use App\Models\SubscriptionInstance;
use App\Models\SubscriptionTemplate;
use App\Models\Teacher;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** La fiche d'un abonnement expose la date de fin théorique de chacune de ses instances. */
class SubscriptionShowProjectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_fiche_abonnement_expose_la_projection_de_fin(): void
    {
        $user = $this->actingAsClub();
        $club = Club::find($user->club_id);

        $teacher = Teacher::factory()->create();
        $teacher->clubs()->attach($club->id, ['is_active' => true, 'joined_at' => now()]);
        $student = Student::factory()->create(['club_id' => $club->id]);
        $courseType = CourseType::factory()->create();
        $location = Location::factory()->create();

        $template = SubscriptionTemplate::create([
            'club_id' => $club->id,
            'model_number' => 'SHOW-'.uniqid(),
            'total_lessons' => 3,
            'validity_months' => 12,
            'price' => 90.00,
            'is_active' => true,
        ]);
        $template->courseTypes()->attach($courseType->id);

        $subscription = Subscription::create([
            'club_id' => $club->id,
            'subscription_template_id' => $template->id,
            'subscription_number' => 'SUB-SHOW-'.uniqid(),
        ]);

        $instance = SubscriptionInstance::create([
            'subscription_id' => $subscription->id,
            'status' => 'active',
            'lessons_used' => 0,
            'manual_lessons_used' => 0,
            'started_at' => Carbon::now()->subMonth(),
            'expires_at' => Carbon::now()->addMonths(11),
        ]);
        $instance->students()->attach($student->id);

        $anchor = Carbon::now()->next(Carbon::MONDAY)->setTime(10, 0);
        for ($i = 0; $i < 3; $i++) {
            $lesson = Lesson::create([
                'club_id' => $club->id,
                'teacher_id' => $teacher->id,
                'student_id' => $student->id,
                'course_type_id' => $courseType->id,
                'location_id' => $location->id,
                'start_time' => $anchor->copy()->addWeeks($i),
                'end_time' => $anchor->copy()->addWeeks($i)->addHour(),
                'status' => 'confirmed',
                'price' => 30.00,
            ]);
            $instance->lessons()->attach($lesson->id);
        }

        $response = $this->getJson("/api/club/subscriptions/{$subscription->id}");

        $response->assertStatus(200);
        $projection = $response->json("meta.projections.{$instance->id}");

        $this->assertNotNull($projection, 'la fiche doit porter la projection de chaque instance');
        $this->assertSame('planned', $projection['status']);
        $this->assertSame($anchor->copy()->addWeeks(2)->toDateString(), $projection['projected_end_date']);
        $this->assertSame(3, $projection['remaining_consumed']);
        $this->assertFalse($projection['expires_before_exhaustion']);
    }
}
