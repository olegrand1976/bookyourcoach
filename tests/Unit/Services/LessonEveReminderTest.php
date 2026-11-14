<?php

namespace Tests\Unit\Services;

use App\Jobs\ProcessLessonPostCreationJob;
use App\Jobs\SendLessonReminderJob;
use App\Mail\StudentEveReminderMail;
use App\Mail\TeacherEveReminderMail;
use App\Models\Club;
use App\Models\ClubClosureDay;
use App\Models\CourseType;
use App\Models\Lesson;
use App\Models\Location;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Services\LessonEveReminderService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Rappels de la veille au soir (21h30) : moniteurs (alerte « ouverture » si seul au premier horaire)
 * et élèves (heure et moniteur).
 */
class LessonEveReminderTest extends TestCase
{
    use RefreshDatabase;

    private Club $club;

    private CourseType $courseType;

    private Location $location;

    private Teacher $alice;

    private Teacher $bruno;

    /** Vendredi soir : les cours rappelés sont ceux du samedi 14/11/2026. */
    private const NOW = '2026-11-13 21:30:00';

    private const DAY = '2026-11-14';

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse(self::NOW));

        $this->club = Club::create(['name' => 'Club Test', 'email' => 'club@test.com', 'phone' => '0123456789', 'is_active' => true]);
        $this->courseType = CourseType::create(['name' => 'Cours', 'duration_minutes' => 20, 'price' => 18.00]);
        $this->location = Location::create(['name' => 'Manège', 'address' => '1 rue', 'city' => 'Ville', 'postal_code' => '7000', 'country' => 'Belgique']);
        $this->alice = $this->makeTeacher('Alice Moniteur', 'alice@test.com');
        $this->bruno = $this->makeTeacher('Bruno Moniteur', 'bruno@test.com');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    #[Test]
    public function un_seul_cours_au_premier_horaire_previent_son_moniteur_qu_il_ouvre_seul(): void
    {
        $this->lesson('09:00', $this->alice, $this->makeStudent('Léa', 'lea@test.com'));
        $this->lesson('09:20', $this->alice, $this->makeStudent('Tom', 'tom@test.com'));
        $this->lesson('09:20', $this->bruno, $this->makeStudent('Zoé', 'zoe@test.com'));

        $plan = $this->plan();

        $this->assertSame('09:00', $plan['first_time']);
        $byName = collect($plan['teachers'])->keyBy('name');
        $this->assertTrue($byName['Alice Moniteur']['opens_alone']);
        $this->assertFalse($byName['Bruno Moniteur']['opens_alone']);
        $this->assertCount(2, $byName['Alice Moniteur']['lessons']);
    }

    #[Test]
    public function plusieurs_cours_au_premier_horaire_personne_n_est_seul(): void
    {
        $this->lesson('09:00', $this->alice, $this->makeStudent('Léa', 'lea@test.com'));
        $this->lesson('09:00', $this->bruno, $this->makeStudent('Tom', 'tom@test.com'));

        $this->assertFalse(collect($this->plan()['teachers'])->contains('opens_alone', true));
    }

    #[Test]
    public function l_eleve_recoit_l_heure_et_son_moniteur_et_le_foyer_un_seul_mail(): void
    {
        $parent = $this->makeStudent('Léa', 'famille@test.com');
        $sibling = Student::create(['user_id' => $parent->user_id, 'club_id' => $this->club->id, 'first_name' => 'Hugo', 'last_name' => 'Martin']);
        $this->lesson('09:00', $this->alice, $parent);
        $this->lesson('09:20', $this->bruno, $sibling);

        $students = $this->plan()['students'];

        $this->assertCount(1, $students);
        $this->assertSame([['09:00', 'Léa Martin', 'Alice Moniteur'], ['09:20', 'Hugo Martin', 'Bruno Moniteur']],
            array_map(fn ($l) => [$l['start'], $l['student'], $l['teacher']], $students[0]['lessons']));
    }

    #[Test]
    public function rien_pour_les_cours_annules_les_eleves_sans_notification_ni_un_jour_de_conge(): void
    {
        $this->lesson('09:00', $this->alice, $this->makeStudent('Léa', 'lea@test.com'))->update(['status' => 'cancelled']);
        $muted = $this->makeStudent('Tom', 'tom@test.com');
        $muted->update(['notifications_enabled' => false]);
        $this->lesson('09:20', $this->alice, $muted);

        $plan = $this->plan();
        $this->assertSame(1, $plan['lessons_count']);
        $this->assertSame([], $plan['students']);
        $this->assertSame('09:20', $plan['teachers'][0]['lessons'][0]['start']);

        ClubClosureDay::create(['club_id' => $this->club->id, 'closed_on' => self::DAY]);
        $closed = $this->plan();
        $this->assertTrue($closed['closed']);
        $this->assertSame([], $closed['teachers']);
    }

    #[Test]
    public function un_moniteur_inactif_dans_le_club_ne_recoit_rien(): void
    {
        DB::table('club_teachers')->where('teacher_id', $this->bruno->id)->update(['is_active' => false]);
        $this->lesson('09:00', $this->bruno, $this->makeStudent('Léa', 'lea@test.com'));

        $plan = $this->plan();
        $this->assertSame([], $plan['teachers']);
        $this->assertNotEmpty($plan['skipped']);
    }

    #[Test]
    public function la_commande_envoie_une_fois_puis_ne_relance_pas(): void
    {
        Mail::fake();
        $this->lesson('09:00', $this->alice, $this->makeStudent('Léa', 'lea@test.com'));

        $this->artisan('lessons:send-eve-reminders')->assertSuccessful();
        $this->artisan('lessons:send-eve-reminders')->assertSuccessful();

        Mail::assertSent(TeacherEveReminderMail::class, 1);
        Mail::assertSent(TeacherEveReminderMail::class, fn (TeacherEveReminderMail $m) => $m->hasTo('alice@test.com')
            && $m->teacher['opens_alone']
            && str_contains($m->render(), 'arriver un peu plus tôt'));
        Mail::assertSent(StudentEveReminderMail::class, 1);
        Mail::assertSent(StudentEveReminderMail::class, fn (StudentEveReminderMail $m) => $m->hasTo('lea@test.com')
            && str_contains($m->render(), '09:00')
            && str_contains($m->render(), 'Alice Moniteur'));
    }

    #[Test]
    public function la_simulation_n_envoie_rien(): void
    {
        Mail::fake();
        $this->lesson('09:00', $this->alice, $this->makeStudent('Léa', 'lea@test.com'));

        $this->artisan('lessons:send-eve-reminders', ['--dry-run' => true])
            ->expectsOutputToContain('SEUL À L’OUVERTURE')
            ->assertSuccessful();

        Mail::assertNothingSent();
    }

    #[Test]
    public function la_creation_d_un_cours_ne_programme_plus_de_rappel_immediat(): void
    {
        Queue::fake([SendLessonReminderJob::class]);
        $lesson = $this->lesson('09:00', $this->alice, $this->makeStudent('Léa', 'lea@test.com'));

        (new ProcessLessonPostCreationJob($lesson, 0, false))->handle();

        Queue::assertNotPushed(SendLessonReminderJob::class);
    }

    /**
     * @return array<string, mixed>
     */
    private function plan(): array
    {
        return app(LessonEveReminderService::class)->buildDay($this->club, Carbon::parse(self::DAY));
    }

    private function makeTeacher(string $name, string $email): Teacher
    {
        $user = User::create(['name' => $name, 'email' => $email, 'password' => bcrypt('password'), 'role' => 'teacher']);
        $teacher = Teacher::create(['user_id' => $user->id, 'club_id' => $this->club->id, 'is_available' => true]);
        DB::table('club_teachers')->insert(['club_id' => $this->club->id, 'teacher_id' => $teacher->id, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);

        return $teacher;
    }

    private function makeStudent(string $firstName, string $email): Student
    {
        $user = User::create(['name' => $firstName.' Martin', 'email' => $email, 'password' => bcrypt('password'), 'role' => 'student']);

        return Student::create(['user_id' => $user->id, 'club_id' => $this->club->id, 'first_name' => $firstName, 'last_name' => 'Martin']);
    }

    private function lesson(string $time, Teacher $teacher, Student $student): Lesson
    {
        $begin = Carbon::parse(self::DAY.' '.$time);

        return Lesson::create([
            'club_id' => $this->club->id,
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'course_type_id' => $this->courseType->id,
            'location_id' => $this->location->id,
            'start_time' => $begin,
            'end_time' => $begin->copy()->addMinutes(20),
            'status' => 'confirmed',
            'price' => 18.00,
        ]);
    }
}
