<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\User;
use App\Models\Club;
use App\Models\Teacher;
use App\Models\Student;
use App\Models\Lesson;
use App\Models\Payment;
use App\Models\CourseType;
use App\Models\Location;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;


class DashboardMetricsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Créer les données de test nécessaires
        $this->user = User::factory()->create([
            'email' => 'club@bookyourcoach.com',
            'role' => 'club'
        ]);
        
        $this->club = Club::factory()->create();
        $this->user->clubs()->attach($this->club->id, [
            'role' => 'admin',
            'is_admin' => true,
            'joined_at' => now()
        ]);
        
        $this->teacher = Teacher::factory()->create(['club_id' => $this->club->id]);
        $this->student = Student::factory()->create(['club_id' => $this->club->id]);
        $this->courseType = CourseType::factory()->create();
        $this->location = Location::factory()->create();
    }

    #[Test]
    public function it_calculates_average_lesson_price_correctly()
    {
        // Créer des cours avec des prix différents
        $lesson1 = Lesson::factory()->create([
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'price' => 50.00,
            'status' => 'completed'
        ]);
        
        $lesson2 = Lesson::factory()->create([
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'price' => 60.00,
            'status' => 'completed'
        ]);
        
        $lesson3 = Lesson::factory()->create([
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'price' => 40.00,
            'status' => 'pending'
        ]);
        
        // Créer des paiements pour les cours terminés
        Payment::factory()->create([
            'lesson_id' => $lesson1->id,
            'amount' => 50.00,
            'status' => 'succeeded'
        ]);
        
        Payment::factory()->create([
            'lesson_id' => $lesson2->id,
            'amount' => 60.00,
            'status' => 'succeeded'
        ]);
        
        $response = $this->getJson('/api/club/dashboard-test');
        
        $response->assertStatus(200);
        
        $stats = $response->json('data.stats');
        
        // Le prix moyen devrait être basé sur les prix des cours, pas sur les revenus
        $expectedAveragePrice = (50.00 + 60.00 + 40.00) / 3; // 50.00
        
        $this->assertEquals($expectedAveragePrice, $stats['average_lesson_price']);
    }

    #[Test]
    public function it_calculates_occupancy_rate_correctly()
    {
        // Créer des cours avec différents statuts
        Lesson::factory()->count(3)->create([
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'status' => 'completed'
        ]);
        
        Lesson::factory()->count(2)->create([
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'status' => 'confirmed'
        ]);
        
        Lesson::factory()->count(2)->create([
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'status' => 'pending'
        ]);
        
        $response = $this->getJson('/api/club/dashboard-test');
        
        $response->assertStatus(200);
        
        $stats = $response->json('data.stats');
        
        // Taux d'occupation = (cours terminés + cours confirmés) / total cours
        $totalLessons = 7;
        $occupiedLessons = 3 + 2; // completed + confirmed
        $expectedOccupancyRate = round(($occupiedLessons / $totalLessons) * 100, 1);
        
        $this->assertEquals($expectedOccupancyRate, $stats['occupancy_rate']);
    }

    #[Test]
    public function it_calculates_revenue_correctly()
    {
        // Créer des cours
        $lesson1 = Lesson::factory()->create([
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'price' => 50.00,
            'status' => 'completed'
        ]);
        
        $lesson2 = Lesson::factory()->create([
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'price' => 60.00,
            'status' => 'completed'
        ]);
        
        // Créer des paiements réussis
        Payment::factory()->create([
            'lesson_id' => $lesson1->id,
            'amount' => 50.00,
            'status' => 'succeeded',
            'created_at' => now()
        ]);
        
        Payment::factory()->create([
            'lesson_id' => $lesson2->id,
            'amount' => 60.00,
            'status' => 'succeeded',
            'created_at' => now()
        ]);
        
        // Créer un paiement échoué (ne devrait pas compter)
        Payment::factory()->create([
            'lesson_id' => $lesson1->id,
            'amount' => 50.00,
            'status' => 'failed',
            'created_at' => now()
        ]);
        
        $response = $this->getJson('/api/club/dashboard-test');
        
        $response->assertStatus(200);
        
        $stats = $response->json('data.stats');
        
        // Le revenu total devrait être la somme des paiements réussis
        $expectedTotalRevenue = 50.00 + 60.00; // 110.00
        
        $this->assertEquals($expectedTotalRevenue, $stats['total_revenue']);
    }

    #[Test]
    public function it_calculates_monthly_revenue_correctly()
    {
        // Créer des paiements pour ce mois
        $lesson1 = Lesson::factory()->create([
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id
        ]);
        
        Payment::factory()->create([
            'lesson_id' => $lesson1->id,
            'amount' => 50.00,
            'status' => 'succeeded',
            'created_at' => now() // Ce mois
        ]);
        
        // Créer un paiement du mois dernier (ne devrait pas compter)
        Payment::factory()->create([
            'lesson_id' => $lesson1->id,
            'amount' => 60.00,
            'status' => 'succeeded',
            'created_at' => now()->subMonth() // Mois dernier
        ]);
        
        $response = $this->getJson('/api/club/dashboard-test');
        
        $response->assertStatus(200);
        
        $stats = $response->json('data.stats');
        
        // Le revenu mensuel devrait être seulement le paiement de ce mois
        $this->assertEquals(50.00, $stats['monthly_revenue']);
    }

    #[Test]
    public function it_handles_zero_lessons_gracefully()
    {
        // Ne créer aucun cours
        
        $response = $this->getJson('/api/club/dashboard-test');
        
        $response->assertStatus(200);
        
        $stats = $response->json('data.stats');
        
        // Toutes les métriques devraient être 0
        $this->assertEquals(0, $stats['total_lessons']);
        $this->assertEquals(0, $stats['completed_lessons']);
        $this->assertEquals(0, $stats['pending_lessons']);
        $this->assertEquals(0, $stats['confirmed_lessons']);
        $this->assertEquals(0, $stats['total_revenue']);
        $this->assertEquals(0, $stats['monthly_revenue']);
        $this->assertEquals(0, $stats['average_lesson_price']);
        $this->assertEquals(0, $stats['occupancy_rate']);
    }

    #[Test]
    public function it_calculates_lesson_counts_correctly()
    {
        // Créer des cours avec différents statuts
        Lesson::factory()->count(3)->create([
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'status' => 'completed'
        ]);
        
        Lesson::factory()->count(2)->create([
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'status' => 'confirmed'
        ]);
        
        Lesson::factory()->count(1)->create([
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'status' => 'pending'
        ]);
        
        Lesson::factory()->count(1)->create([
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'status' => 'cancelled'
        ]);
        
        $response = $this->getJson('/api/club/dashboard-test');
        
        $response->assertStatus(200);
        
        $stats = $response->json('data.stats');
        
        $this->assertEquals(7, $stats['total_lessons']);
        $this->assertEquals(3, $stats['completed_lessons']);
        $this->assertEquals(2, $stats['confirmed_lessons']);
        $this->assertEquals(1, $stats['pending_lessons']);
        $this->assertEquals(1, $stats['cancelled_lessons']);
    }

    #[Test]
    public function it_only_includes_club_teachers_in_calculations()
    {
        // Créer un enseignant d'un autre club
        $otherClub = Club::factory()->create();
        $otherTeacher = Teacher::factory()->create(['club_id' => $otherClub->id]);
        
        // Créer des cours pour chaque enseignant
        Lesson::factory()->create([
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'price' => 50.00
        ]);
        
        Lesson::factory()->create([
            'teacher_id' => $otherTeacher->id,
            'student_id' => $this->student->id,
            'price' => 60.00
        ]);
        
        $response = $this->getJson('/api/club/dashboard-test');
        
        $response->assertStatus(200);
        
        $stats = $response->json('data.stats');
        
        // Seul le cours de l'enseignant du club devrait être compté
        $this->assertEquals(1, $stats['total_lessons']);
        $this->assertEquals(50.00, $stats['average_lesson_price']);
    }
}
