<?php

namespace Tests\Feature\Api;

use App\Models\Club;
use App\Models\Student;
use App\Models\Subscription;
use App\Models\SubscriptionInstance;
use App\Models\SubscriptionTemplate;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * « Mes Abonnements » (élève) ne liste que les instances au statut `active` :
 * l'API doit donc renvoyer un statut rafraîchi (expiration, épuisement).
 */
class StudentMySubscriptionsStatusTest extends TestCase
{
    use RefreshDatabase;

    private function makeInstance(Student $student, array $attributes, int $totalLessons = 5): SubscriptionInstance
    {
        $club = Club::factory()->create();

        $template = SubscriptionTemplate::create([
            'club_id' => $club->id,
            'model_number' => 'MY-'.uniqid(),
            'total_lessons' => $totalLessons,
            'validity_months' => 12,
            'price' => 100.00,
            'is_active' => true,
        ]);

        $subscription = Subscription::create([
            'club_id' => $club->id,
            'subscription_template_id' => $template->id,
            'subscription_number' => 'SUB-MY-'.uniqid(),
        ]);

        $instance = SubscriptionInstance::create(array_merge([
            'subscription_id' => $subscription->id,
            'status' => 'active',
            'lessons_used' => 0,
            'manual_lessons_used' => 0,
            'started_at' => Carbon::now()->subMonth(),
            'expires_at' => Carbon::now()->addMonths(11),
        ], $attributes));
        $instance->students()->attach($student->id);

        return $instance;
    }

    private function statusesById(): array
    {
        $response = $this->getJson('/api/student/subscriptions?active_student_id=all');
        $response->assertStatus(200)->assertJson(['success' => true]);

        return collect($response->json('data'))->pluck('status', 'id')->all();
    }

    public function test_les_statuts_renvoyes_sont_rafraichis(): void
    {
        $user = $this->actingAsStudent();
        $student = Student::where('user_id', $user->id)->firstOrFail();

        $active = $this->makeInstance($student, []);
        $expired = $this->makeInstance($student, [
            'started_at' => Carbon::now()->subYear(),
            'expires_at' => Carbon::now()->subDay(),
        ]);
        $exhausted = $this->makeInstance($student, ['manual_lessons_used' => 5]);

        $statuses = $this->statusesById();

        $this->assertSame('active', $statuses[$active->id]);
        $this->assertSame('expired', $statuses[$expired->id], 'un abonnement échu ne doit plus être « active »');
        $this->assertSame('completed', $statuses[$exhausted->id], 'un abonnement épuisé ne doit plus être « active »');
        $this->assertSame('expired', $expired->fresh()->status);
    }

    public function test_un_abonnement_d_un_autre_eleve_n_est_pas_renvoye(): void
    {
        $user = $this->actingAsStudent();
        $student = Student::where('user_id', $user->id)->firstOrFail();
        $mine = $this->makeInstance($student, []);

        $other = $this->makeInstance(Student::factory()->create(), []);

        $statuses = $this->statusesById();

        $this->assertArrayHasKey($mine->id, $statuses);
        $this->assertArrayNotHasKey($other->id, $statuses);
    }
}
