<?php

namespace Tests\Feature;

use App\Models\Club;
use App\Models\ClubClosureDay;
use App\Models\ClubOpenSlot;
use App\Models\Lesson;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OpeningHoursReportTest extends TestCase
{
    use RefreshDatabase;

    protected User $clubUser;

    protected Club $club;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clubUser = User::factory()->create(['role' => 'club']);
        $this->club = Club::factory()->create();
        $this->club->users()->attach($this->clubUser->id, [
            'is_admin' => true,
            'role' => 'admin',
        ]);
    }

    private function createLesson(Carbon $start, string $status = 'confirmed'): Lesson
    {
        return Lesson::factory()->create([
            'club_id' => $this->club->id,
            'start_time' => $start,
            'end_time' => $start->copy()->addHour(),
            'status' => $status,
        ]);
    }

    private function report(string $from, string $to)
    {
        return $this->actingAs($this->clubUser, 'sanctum')
            ->getJson("/api/club/planning/opening-hours?date_from={$from}&date_to={$to}");
    }

    public function test_cours_un_jour_de_fermeture_est_exclu_du_rapport(): void
    {
        // 2026-07-06 = lundi (ouvert), 2026-07-13 = lundi déclaré fermé (vacances)
        $open = Carbon::create(2026, 7, 6, 10, 0);
        $closed = Carbon::create(2026, 7, 13, 10, 0);

        ClubOpenSlot::factory()->create([
            'club_id' => $this->club->id,
            'day_of_week' => $open->dayOfWeek,
            'start_time' => '08:00:00',
            'end_time' => '20:00:00',
            'is_active' => true,
        ]);

        $this->createLesson($open);
        $this->createLesson($closed);

        ClubClosureDay::create([
            'club_id' => $this->club->id,
            'closed_on' => '2026-07-13',
        ]);

        $response = $this->report('2026-07-01', '2026-07-31');

        $response->assertStatus(200);
        $data = $response->json('data');

        // Seul le jour ouvert est compté
        $this->assertSame(1, $data['days_count']);
        $dates = array_column($data['days'], 'date');
        $this->assertContains('2026-07-06', $dates);
        $this->assertNotContains('2026-07-13', $dates);
        $this->assertEqualsWithDelta(1.0, $data['total_hours'], 0.001);
    }

    public function test_sans_fermeture_tous_les_jours_sont_comptes(): void
    {
        $day1 = Carbon::create(2026, 7, 6, 10, 0);
        $day2 = Carbon::create(2026, 7, 13, 10, 0);

        ClubOpenSlot::factory()->create([
            'club_id' => $this->club->id,
            'day_of_week' => $day1->dayOfWeek,
            'start_time' => '08:00:00',
            'end_time' => '20:00:00',
            'is_active' => true,
        ]);

        $this->createLesson($day1);
        $this->createLesson($day2);

        $response = $this->report('2026-07-01', '2026-07-31');

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertSame(2, $data['days_count']);
        $this->assertEqualsWithDelta(2.0, $data['total_hours'], 0.001);
    }
}
