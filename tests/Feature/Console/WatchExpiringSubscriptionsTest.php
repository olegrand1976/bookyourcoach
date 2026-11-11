<?php

namespace Tests\Feature\Console;

use App\Mail\ClubSubscriptionExpiryWatchMail;
use App\Models\Club;
use App\Models\ClubClosureDay;
use App\Models\CourseType;
use App\Models\Lesson;
use App\Models\Location;
use App\Models\Student;
use App\Models\Subscription;
use App\Models\SubscriptionInstance;
use App\Models\SubscriptionTemplate;
use App\Models\Teacher;
use App\Services\SubscriptionExpiryWatchService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Veille la veille d'un jour de cours : abonnements en fin de vie classés par urgence
 * (critique ≤ 1 cours / ≤ 3 j, urgent ≤ 2 / ≤ 7 j, à surveiller ≤ 3 / ≤ 14 j).
 */
class WatchExpiringSubscriptionsTest extends TestCase
{
    use RefreshDatabase;

    private Club $club;

    private Teacher $teacher;

    private CourseType $courseType;

    private Location $location;

    private Carbon $tomorrow;

    protected function setUp(): void
    {
        parent::setUp();

        $user = $this->actingAsClub();
        $this->club = Club::find($user->club_id);
        // La factory crée un club inactif 1 fois sur 5 ; la veille ne traite que les clubs actifs.
        $this->club->update(['is_active' => true]);
        $this->teacher = Teacher::factory()->create();
        $this->teacher->clubs()->attach($this->club->id, ['is_active' => true, 'joined_at' => now()]);
        $this->courseType = CourseType::factory()->create();
        $this->location = Location::factory()->create();
        // Jour de cours ciblé par défaut : aujourd'hui + lead_days (2 jours avant les cours).
        $this->tomorrow = now('Europe/Brussels')->addDays(\App\Console\Commands\WatchExpiringSubscriptionsCommand::leadDays())->startOfDay();
    }

    private function studentWithSubscription(string $lastName, int $totalLessons, int $used, ?Carbon $expiresAt, string $status = 'active'): Student
    {
        $student = Student::factory()->create([
            'club_id' => $this->club->id,
            'first_name' => 'Enfant',
            'last_name' => $lastName,
        ]);

        $template = SubscriptionTemplate::create([
            'club_id' => $this->club->id,
            'model_number' => 'WATCH-'.uniqid(),
            'total_lessons' => $totalLessons,
            'free_lessons' => 0,
            'validity_months' => 12,
            'price' => 100.00,
            'is_active' => true,
        ]);
        $subscription = Subscription::create([
            'club_id' => $this->club->id,
            'subscription_template_id' => $template->id,
            'subscription_number' => 'SUB-WATCH-'.uniqid(),
        ]);
        $instance = SubscriptionInstance::create([
            'subscription_id' => $subscription->id,
            'status' => $status,
            'lessons_used' => $used,
            'manual_lessons_used' => $used,
            'started_at' => now()->subMonths(2),
            'expires_at' => $expiresAt ?? now()->addMonths(6),
        ]);
        $instance->students()->attach($student->id);
        // Les observers peuvent recalculer : on fige le compteur voulu pour le test.
        SubscriptionInstance::whereKey($instance->id)->update(['lessons_used' => $used, 'status' => $status]);

        return $student;
    }

    private function lessonTomorrow(Student $student, int $hour = 10, int $minute = 0): Lesson
    {
        // Heure locale du club, stockée dans le fuseau de l'application (comme en prod)
        $start = $this->tomorrow->copy()->setTime($hour, $minute)->timezone(config('app.timezone'));

        return Lesson::create([
            'club_id' => $this->club->id,
            'teacher_id' => $this->teacher->id,
            'student_id' => $student->id,
            'course_type_id' => $this->courseType->id,
            'location_id' => $this->location->id,
            'start_time' => $start,
            'end_time' => $start->copy()->addHour(),
            'status' => 'confirmed',
            'price' => 25.00,
            'deduct_from_subscription' => false,
        ]);
    }

    private function report(): array
    {
        return app(SubscriptionExpiryWatchService::class)
            ->buildReport($this->club, $this->tomorrow, $this->tomorrow, 'Europe/Brussels');
    }

    private function namesIn(array $report, string $level): array
    {
        return collect($report['groups'][$level])->pluck('student_name')->sort()->values()->all();
    }

    public function test_it_classifies_subscriptions_by_urgency(): void
    {
        $last = $this->studentWithSubscription('Dernier', 10, 9, null);           // 1 cours restant → critique
        $two = $this->studentWithSubscription('Deux', 10, 8, null);               // 2 restants → urgent
        $three = $this->studentWithSubscription('Trois', 10, 7, null);           // 3 restants → à surveiller
        $soon = $this->studentWithSubscription('Expire', 10, 0, $this->tomorrow->copy()->addDays(5)); // 5 j → urgent
        $fine = $this->studentWithSubscription('Large', 10, 2, null);            // RAS
        foreach ([$last, $two, $three, $soon, $fine] as $s) {
            $this->lessonTomorrow($s);
        }

        $report = $this->report();

        $this->assertSame(['Enfant Dernier'], $this->namesIn($report, 'critical'));
        $this->assertSame(['Enfant Deux', 'Enfant Expire'], $this->namesIn($report, 'urgent'));
        $this->assertSame(['Enfant Trois'], $this->namesIn($report, 'watch'));
        $this->assertSame(5, $report['students_with_lessons']);
    }

    public function test_student_without_active_subscription_anymore_is_critical(): void
    {
        $done = $this->studentWithSubscription('Termine', 10, 10, null, 'completed');
        $this->lessonTomorrow($done);

        $report = $this->report();

        $this->assertSame(['Enfant Termine'], $this->namesIn($report, 'critical'));
        $this->assertSame('no_active_subscription', $report['groups']['critical'][0]['reason']);
    }

    public function test_students_without_lesson_tomorrow_or_on_closure_day_are_ignored(): void
    {
        $this->studentWithSubscription('SansCours', 10, 9, null);

        $closed = $this->studentWithSubscription('Conge', 10, 9, null);
        $this->lessonTomorrow($closed);
        ClubClosureDay::create(['club_id' => $this->club->id, 'closed_on' => $this->tomorrow->toDateString()]);

        $report = $this->report();

        $this->assertSame(0, array_sum($report['counts']));
        $this->assertSame([$this->tomorrow->toDateString()], $report['skipped_closure_days']);
    }

    public function test_other_club_subscriptions_are_never_reported(): void
    {
        $student = $this->studentWithSubscription('Moi', 10, 2, null);
        $this->lessonTomorrow($student);

        // Abonnement presque épuisé, mais dans un autre club
        $otherClub = Club::factory()->create();
        $template = SubscriptionTemplate::create([
            'club_id' => $otherClub->id, 'model_number' => 'OTHER-'.uniqid(), 'total_lessons' => 10,
            'free_lessons' => 0, 'validity_months' => 12, 'price' => 100, 'is_active' => true,
        ]);
        $sub = Subscription::create(['club_id' => $otherClub->id, 'subscription_template_id' => $template->id, 'subscription_number' => 'SUB-O-'.uniqid()]);
        $instance = SubscriptionInstance::create([
            'subscription_id' => $sub->id, 'status' => 'active', 'lessons_used' => 9, 'manual_lessons_used' => 9,
            'started_at' => now()->subMonth(), 'expires_at' => now()->addMonths(6),
        ]);
        $instance->students()->attach($student->id);

        $this->assertSame(0, array_sum($this->report()['counts']));
    }

    public function test_late_evening_lesson_counts_for_the_right_day(): void
    {
        $late = $this->studentWithSubscription('Tardif', 10, 9, null);
        $this->lessonTomorrow($late, 23, 30);

        $this->assertSame(['Enfant Tardif'], $this->namesIn($this->report(), 'critical'));
    }

    public function test_already_renewed_student_is_not_reported(): void
    {
        $student = $this->studentWithSubscription('Renouvele', 10, 9, null);
        $old = SubscriptionInstance::whereHas('students', fn ($q) => $q->whereKey($student->id))->firstOrFail();

        // Nouveau carnet du même modèle, déjà actif
        $fresh = SubscriptionInstance::create([
            'subscription_id' => $old->subscription_id, 'status' => 'active', 'lessons_used' => 0,
            'manual_lessons_used' => 0, 'started_at' => now(), 'expires_at' => now()->addYear(),
        ]);
        $fresh->students()->attach($student->id);
        SubscriptionInstance::whereKey($fresh->id)->update(['lessons_used' => 0, 'status' => 'active']);
        $this->lessonTomorrow($student);

        $this->assertSame(0, array_sum($this->report()['counts']));
    }

    public function test_long_expired_subscription_is_not_an_alert(): void
    {
        $student = $this->studentWithSubscription('Ancien', 10, 10, now()->subYear(), 'expired');
        $this->lessonTomorrow($student);

        $this->assertSame(0, array_sum($this->report()['counts']));
    }

    public function test_student_who_left_the_lesson_is_ignored(): void
    {
        $owner = $this->studentWithSubscription('Titulaire', 10, 1, null);
        $left = $this->studentWithSubscription('Desinscrit', 10, 9, null);
        $lesson = $this->lessonTomorrow($owner);
        $lesson->students()->attach($left->id, ['status' => 'cancelled']);

        $this->assertSame(0, array_sum($this->report()['counts']));
    }

    public function test_command_sends_one_mail_grouped_by_urgency_and_is_idempotent(): void
    {
        Mail::fake();
        // Le cache d'idempotence peut être persistant (store fichier) : repartir d'un état propre.
        Cache::forget('subscription_expiry_watch_sent:'.$this->club->id.':'.$this->tomorrow->toDateString());
        $s = $this->studentWithSubscription('Dernier', 10, 9, null);
        $this->lessonTomorrow($s);

        $this->artisan('subscriptions:watch-expiring', ['--club' => $this->club->id])->assertSuccessful();
        $this->artisan('subscriptions:watch-expiring', ['--club' => $this->club->id])->assertSuccessful();

        Mail::assertSent(ClubSubscriptionExpiryWatchMail::class, 1);
        Mail::assertSent(ClubSubscriptionExpiryWatchMail::class, function (ClubSubscriptionExpiryWatchMail $mail) {
            $html = $mail->render();

            return $mail->report['counts']['critical'] === 1
                && str_contains($mail->envelope()->subject, 'critique')
                && str_contains($html, 'Enfant Dernier');
        });
    }

    public function test_last_subscription_is_a_planned_ending_not_an_alert(): void
    {
        Mail::fake();
        $leaving = $this->studentWithSubscription('Depart', 10, 9, null); // 1 cours restant
        SubscriptionInstance::whereHas('students', fn ($q) => $q->where('students.id', $leaving->id))
            ->update(['is_last_subscription' => true]);
        $this->lessonTomorrow($leaving);

        $report = $this->report();

        $this->assertSame(['critical' => 0, 'urgent' => 0, 'watch' => 0], $report['counts']);
        $this->assertSame(['Enfant Depart'], collect($report['planned_endings'])->pluck('student_name')->all());
        $this->assertTrue($report['planned_endings'][0]['is_last_subscription']);

        // Fin prévue seule : pas d'e-mail de relance.
        $this->artisan('subscriptions:watch-expiring', ['--club' => $this->club->id])->assertSuccessful();
        Mail::assertNothingSent();
    }

    public function test_end_tracking_lists_all_endings_of_the_club_without_lesson_condition(): void
    {
        $this->studentWithSubscription('Critique', 10, 9, null);            // 1 restant, pas de cours prévu
        $this->studentWithSubscription('Large', 10, 1, null);               // RAS
        $leaving = $this->studentWithSubscription('Depart', 10, 8, null);  // dernier abonnement
        SubscriptionInstance::whereHas('students', fn ($q) => $q->where('students.id', $leaving->id))
            ->update(['is_last_subscription' => true]);
        $this->studentWithSubscription('Fini', 10, 10, now()->subDays(5), 'completed'); // terminé, non renouvelé
        $this->studentWithSubscription('Ancien', 10, 10, now()->subDays(200), 'completed'); // trop ancien

        // Autre club : jamais listé
        $otherClub = Club::factory()->create();
        $foreignTpl = SubscriptionTemplate::create([
            'club_id' => $otherClub->id, 'model_number' => 'X', 'total_lessons' => 10, 'free_lessons' => 0,
            'validity_months' => 12, 'price' => 100, 'is_active' => true,
        ]);
        $foreignSub = Subscription::create(['club_id' => $otherClub->id, 'subscription_template_id' => $foreignTpl->id, 'subscription_number' => 'SUB-F']);
        $foreign = SubscriptionInstance::create([
            'subscription_id' => $foreignSub->id, 'status' => 'active', 'lessons_used' => 9, 'manual_lessons_used' => 9,
            'started_at' => now()->subMonth(), 'expires_at' => now()->addMonths(3),
        ]);
        $foreign->students()->attach(Student::factory()->create(['last_name' => 'Etranger'])->id);

        $response = $this->getJson('/api/club/subscriptions/end-tracking')->assertOk();

        $byCategory = collect($response->json('data.items'))->groupBy('category')
            ->map(fn ($items) => $items->pluck('student_name')->all())->all();
        $this->assertSame(['Enfant Critique'], $byCategory['critical'] ?? []);
        $this->assertSame(['Enfant Depart'], $byCategory['last'] ?? []);
        $this->assertSame(['Enfant Fini'], $byCategory['ended'] ?? []);
        $this->assertSame(1, $response->json('data.counts.last'));
        $this->assertStringNotContainsString('Etranger', json_encode($response->json('data.items')));
    }

    public function test_no_mail_when_nothing_to_report(): void
    {
        Mail::fake();
        $s = $this->studentWithSubscription('Large', 10, 1, null);
        $this->lessonTomorrow($s);

        $this->artisan('subscriptions:watch-expiring', ['--club' => $this->club->id])->assertSuccessful();

        Mail::assertNothingSent();
    }

    public function test_dashboard_endpoint_lists_items_by_urgency(): void
    {
        $s = $this->studentWithSubscription('Dernier', 10, 9, null);
        $this->lessonTomorrow($s);

        $this->getJson('/api/club/subscriptions/expiring?days=7')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.counts.critical', 1)
            ->assertJsonPath('data.items.0.level', 'critical')
            ->assertJsonPath('data.items.0.student_id', $s->id);
    }
}
