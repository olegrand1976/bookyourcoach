<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Club;
use App\Models\Teacher;
use App\Models\Student;
use App\Models\Lesson;
use App\Models\CourseType;
use App\Models\Location;
use App\Models\SubscriptionTemplate;
use App\Models\Subscription;
use App\Models\SubscriptionInstance;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

class ClubDashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_get_club_dashboard()
    {
        // Arrange
        $user = $this->actingAsClub();
        $club = Club::find($user->club_id);

        $teacher = Teacher::factory()->create();
        $teacher->clubs()->attach($club->id, ['is_active' => true, 'joined_at' => now()]);

        $student = Student::factory()->create(['club_id' => $club->id]);
        // Créer l'entrée dans club_students (table pivot)
        DB::table('club_students')->insert([
            'club_id' => $club->id,
            'student_id' => $student->id,
            'is_active' => true,
            'joined_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $courseType = CourseType::factory()->create();
        $location = Location::factory()->create();

        Lesson::factory()->count(5)->create([
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'club_id' => $club->id,
            'course_type_id' => $courseType->id,
            'location_id' => $location->id,
            'status' => 'completed',
            'price' => 50.00,
        ]);

        // Act
        $response = $this->getJson('/api/club/dashboard');

        // Assert
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'club',
                         'stats' => [
                             'total_teachers',
                             'total_students',
                             'total_lessons',
                             'completed_lessons',
                             'total_revenue',
                             'monthly_revenue',
                         ],
                         'recentTeachers',
                         'recentStudents',
                         'recentLessons',
                         'incompleteStudents',
                         'subscriptionsNearingEnd',
                     ]
                 ]);

        $stats = $response->json('data.stats');
        $this->assertGreaterThanOrEqual(1, $stats['total_teachers']);
        $this->assertGreaterThanOrEqual(1, $stats['total_students']);
        $this->assertGreaterThanOrEqual(5, $stats['total_lessons']);
    }

    #[Test]
    public function it_returns_404_if_no_club_associated()
    {
        // Arrange
        $user = User::factory()->create(['role' => 'club']);
        Sanctum::actingAs($user);
        
        // Ne pas créer d'association club_user
        
        // Act
        $response = $this->getJson('/api/club/dashboard');

        // Assert
        $response->assertStatus(404)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Aucun club associé à cet utilisateur'
                 ]);
    }

    #[Test]
    public function it_requires_club_role_to_access_dashboard()
    {
        // Arrange
        $teacherUser = $this->actingAsTeacher();

        // Act
        $response = $this->getJson('/api/club/dashboard');

        // Assert
        $response->assertStatus(403)
                 ->assertJson([
                     'message' => 'Unauthorized',
                     'error' => 'Accès non autorisé. Rôle club requis.'
                 ]);
    }

    #[Test]
    public function it_requires_authentication_to_access_dashboard()
    {
        // Act
        $response = $this->getJson('/api/club/dashboard');

        // Assert
        $response->assertStatus(401);
    }

    #[Test]
    public function it_includes_recent_teachers_in_dashboard()
    {
        // Arrange
        $user = $this->actingAsClub();
        $club = Club::find($user->club_id);

        $teacher1 = Teacher::factory()->create();
        $teacher2 = Teacher::factory()->create();
        
        $teacher1->clubs()->attach($club->id, [
            'is_active' => true,
            'joined_at' => now()->subDays(1),
        ]);
        $teacher2->clubs()->attach($club->id, [
            'is_active' => true,
            'joined_at' => now()->subDays(2),
        ]);

        // Act
        $response = $this->getJson('/api/club/dashboard');

        // Assert
        $response->assertStatus(200);
        $recentTeachers = $response->json('data.recentTeachers');
        $this->assertGreaterThanOrEqual(2, count($recentTeachers));
    }

    #[Test]
    public function it_includes_recent_students_in_dashboard()
    {
        // Arrange
        $user = $this->actingAsClub();
        $club = Club::find($user->club_id);

        $student1 = Student::factory()->create(['club_id' => $club->id]);
        $student2 = Student::factory()->create(['club_id' => $club->id]);

        DB::table('club_students')->insert([
            [
                'club_id' => $club->id,
                'student_id' => $student1->id,
                'is_active' => true,
                'joined_at' => now()->subDays(1),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'club_id' => $club->id,
                'student_id' => $student2->id,
                'is_active' => true,
                'joined_at' => now()->subDays(2),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Act
        $response = $this->getJson('/api/club/dashboard');

        // Assert
        $response->assertStatus(200);
        $recentStudents = $response->json('data.recentStudents');
        $this->assertGreaterThanOrEqual(2, count($recentStudents));
    }

    #[Test]
    public function it_includes_recent_lessons_in_dashboard()
    {
        // Arrange
        $user = $this->actingAsClub();
        $club = Club::find($user->club_id);

        $teacher = Teacher::factory()->create();
        $teacher->clubs()->attach($club->id, ['is_active' => true]);
        
        $student = Student::factory()->create(['club_id' => $club->id]);
        $courseType = CourseType::factory()->create();
        $location = Location::factory()->create();

        Lesson::factory()->count(3)->create([
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'club_id' => $club->id,
            'course_type_id' => $courseType->id,
            'location_id' => $location->id,
            'start_time' => Carbon::now()->subDays(1),
        ]);

        // Act
        $response = $this->getJson('/api/club/dashboard');

        // Assert
        $response->assertStatus(200);
        $recentLessons = $response->json('data.recentLessons');
        $this->assertGreaterThanOrEqual(3, count($recentLessons));
    }

    #[Test]
    public function it_includes_subscriptions_nearing_end_in_dashboard()
    {
        // Arrange: club + template (10 cours, seuil par défaut 8) + subscription + instance à 8 séances
        $user = $this->actingAsClub();
        $club = Club::find($user->club_id);

        $template = SubscriptionTemplate::factory()->create([
            'club_id' => $club->id,
            'model_number' => 'PACK10',
            'total_lessons' => 10,
            'free_lessons' => 0,
            'warning_at_session' => 8,
        ]);

        $subscription = Subscription::create([
            'club_id' => $club->id,
            'subscription_template_id' => $template->id,
        ]);

        $student = Student::factory()->create(['club_id' => $club->id]);
        DB::table('club_students')->insert([
            'club_id' => $club->id,
            'student_id' => $student->id,
            'is_active' => true,
            'joined_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $instanceNearing = SubscriptionInstance::create([
            'subscription_id' => $subscription->id,
            'lessons_used' => 8,
            'started_at' => now()->subMonths(1),
            'expires_at' => now()->addMonths(2),
            'status' => 'active',
        ]);
        $instanceNearing->students()->attach($student->id);

        // Une autre instance en dessous du seuil ne doit pas apparaître
        $subscription2 = Subscription::create([
            'club_id' => $club->id,
            'subscription_template_id' => $template->id,
        ]);
        $instanceBelow = SubscriptionInstance::create([
            'subscription_id' => $subscription2->id,
            'lessons_used' => 3,
            'started_at' => now()->subDays(10),
            'expires_at' => now()->addMonths(2),
            'status' => 'active',
        ]);
        $instanceBelow->students()->attach($student->id);

        // Act
        $response = $this->getJson('/api/club/dashboard');

        // Assert
        $response->assertStatus(200);
        $nearing = $response->json('data.subscriptionsNearingEnd');
        $this->assertIsArray($nearing);
        $this->assertGreaterThanOrEqual(1, count($nearing));
        $ids = array_column($nearing, 'id');
        $this->assertContains($instanceNearing->id, $ids);
        $this->assertNotContains($instanceBelow->id, $ids);

        $first = collect($nearing)->firstWhere('id', $instanceNearing->id);
        $this->assertNotNull($first);
        $this->assertSame(8, $first['lessons_used']);
        $this->assertSame(8, $first['threshold']);
        $this->assertSame('PACK10', $first['template']['model_number']);
        $this->assertSame(10, $first['template']['total_available_lessons']);
    }
}

