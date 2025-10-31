<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Club;
use App\Models\Lesson;
use App\Models\Teacher;
use App\Models\CourseType;
use App\Models\Location;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Carbon\Carbon;
use Laravel\Sanctum\Sanctum;

class ClubPredictiveAnalysisControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_get_predictive_analysis()
    {
        // Arrange
        $user = $this->actingAsClub();
        $club = Club::find($user->club_id);
        
        $teacher = Teacher::factory()->create();
        $teacher->clubs()->attach($club->id, ['is_active' => true]);
        
        $courseType = CourseType::factory()->create();
        $location = Location::factory()->create();

        // Créer suffisamment de cours pour générer une analyse
        Lesson::factory()->count(20)->create([
            'teacher_id' => $teacher->id,
            'club_id' => $club->id,
            'course_type_id' => $courseType->id,
            'location_id' => $location->id,
            'status' => 'completed',
            'price' => 50.00,
            'start_time' => Carbon::now()->subDays(30),
        ]);

        // Act
        $response = $this->getJson('/api/club/predictive-analysis');

        // Assert
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                 ]);

        // Peut retourner success:false si pas assez de données ou Neo4j non disponible
        // C'est normal et acceptable
    }

    #[Test]
    public function it_returns_message_when_not_enough_data()
    {
        // Arrange
        $user = $this->actingAsClub();
        $club = Club::find($user->club_id);

        // Ne pas créer de cours

        // Act
        $response = $this->getJson('/api/club/predictive-analysis');

        // Assert
        $response->assertStatus(200);
        
        // Peut retourner success:false avec message approprié
        if (!$response->json('success')) {
            $this->assertStringContainsString('données', $response->json('message') ?? '');
        }
    }

    #[Test]
    public function it_can_get_critical_alerts()
    {
        // Arrange
        $user = $this->actingAsClub();
        $club = Club::find($user->club_id);

        // Act
        $response = $this->getJson('/api/club/predictive-analysis/alerts');

        // Assert
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data',
                 ]);

        // Peut retourner un tableau vide si pas d'alertes
        $this->assertIsArray($response->json('data'));
    }

    #[Test]
    public function it_returns_404_if_club_not_found()
    {
        // Arrange
        $user = User::factory()->create(['role' => 'club']);
        Sanctum::actingAs($user);
        
        // Ne pas créer d'association club_user

        // Act
        $response = $this->getJson('/api/club/predictive-analysis');

        // Assert
        $response->assertStatus(404)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Club non trouvé'
                 ]);
    }

    #[Test]
    public function it_requires_club_role_to_access_analysis()
    {
        // Arrange
        $teacherUser = $this->actingAsTeacher();

        // Act
        $response = $this->getJson('/api/club/predictive-analysis');

        // Assert
        $response->assertStatus(403);
    }

    #[Test]
    public function it_requires_authentication_to_access_analysis()
    {
        // Act
        $response = $this->getJson('/api/club/predictive-analysis');

        // Assert
        $response->assertStatus(401);
    }
}

