<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\User;
use App\Models\Club;
use App\Models\Teacher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;


class TeacherApiTest extends TestCase
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
    }

    #[Test]
    public function it_can_create_a_teacher_via_api()
    {
        $teacherData = [
            'name' => 'Test Teacher',
            'email' => 'teacher@example.com',
            'phone' => '0123456789',
            'specializations' => ['dressage', 'obstacle'],
            'experience_years' => 5,
            'hourly_rate' => 60.00,
            'bio' => 'Enseignant expérimenté'
        ];
        
        $response = $this->postJson('/api/club/teachers-test', $teacherData);
        
        $response->assertStatus(201)
                ->assertJsonStructure([
                    'message',
                    'teacher' => [
                        'id',
                        'name',
                        'email',
                        'phone',
                        'role'
                    ],
                    'teacherProfile' => [
                        'id',
                        'user_id',
                        'specializations',
                        'experience_years',
                        'hourly_rate',
                        'bio'
                    ]
                ]);
        
        $this->assertDatabaseHas('users', [
            'name' => 'Test Teacher',
            'email' => 'teacher@example.com',
            'role' => 'teacher'
        ]);
        
        $this->assertDatabaseHas('teachers', [
            'specializations' => json_encode(['dressage', 'obstacle']),
            'experience_years' => 5,
            'hourly_rate' => 60.00
        ]);
    }

    #[Test]
    public function it_validates_required_fields()
    {
        $response = $this->postJson('/api/club/teachers-test', []);
        
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['name', 'email']);
    }

    #[Test]
    public function it_validates_email_uniqueness()
    {
        User::factory()->create(['email' => 'existing@example.com']);
        
        $teacherData = [
            'name' => 'Test Teacher',
            'email' => 'existing@example.com'
        ];
        
        $response = $this->postJson('/api/club/teachers-test', $teacherData);
        
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);
    }

    #[Test]
    public function it_can_create_teacher_with_optional_fields()
    {
        $teacherData = [
            'name' => 'Test Teacher',
            'email' => 'teacher@example.com',
            'phone' => '0123456789',
            'specializations' => ['cross', 'complet'],
            'experience_years' => 10,
            'hourly_rate' => 80.00,
            'bio' => 'Enseignant spécialisé en cross'
        ];
        
        $response = $this->postJson('/api/club/teachers-test', $teacherData);
        
        $response->assertStatus(201);
        
        $this->assertDatabaseHas('teachers', [
            'specializations' => json_encode(['cross', 'complet']),
            'experience_years' => 10,
            'hourly_rate' => 80.00,
            'bio' => 'Enseignant spécialisé en cross'
        ]);
    }

    #[Test]
    public function it_can_create_teacher_with_default_values()
    {
        $teacherData = [
            'name' => 'Test Teacher',
            'email' => 'teacher@example.com'
        ];
        
        $response = $this->postJson('/api/club/teachers-test', $teacherData);
        
        $response->assertStatus(201);
        
        $this->assertDatabaseHas('teachers', [
            'specializations' => json_encode(['dressage']),
            'experience_years' => 0,
            'hourly_rate' => 50.00,
            'bio' => '',
            'is_available' => true
        ]);
    }

    #[Test]
    public function it_associates_teacher_with_club()
    {
        $teacherData = [
            'name' => 'Test Teacher',
            'email' => 'teacher@example.com'
        ];
        
        $response = $this->postJson('/api/club/teachers-test', $teacherData);
        
        $response->assertStatus(201);
        
        // Vérifier que l'enseignant est associé au club
        $teacher = Teacher::where('club_id', $this->club->id)->first();
        $this->assertNotNull($teacher);
        
        // Vérifier que l'utilisateur est associé au club via la table pivot
        $this->assertTrue($this->club->users->contains($teacher->user));
    }

    #[Test]
    public function it_can_list_teachers()
    {
        // Créer quelques enseignants
        $teacher1 = Teacher::factory()->create(['club_id' => $this->club->id]);
        $teacher2 = Teacher::factory()->create(['club_id' => $this->club->id]);
        
        $response = $this->getJson('/api/club/dashboard-test');
        
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'recentTeachers' => [
                            '*' => [
                                'id',
                                'name',
                                'email',
                                'phone',
                                'role'
                            ]
                        ]
                    ]
                ]);
        
        $this->assertCount(2, $response->json('data.recentTeachers'));
    }

    #[Test]
    public function it_returns_error_when_user_not_found()
    {
        // Supprimer l'utilisateur club
        $this->user->delete();
        
        $teacherData = [
            'name' => 'Test Teacher',
            'email' => 'teacher@example.com'
        ];
        
        $response = $this->postJson('/api/club/teachers-test', $teacherData);
        
        $response->assertStatus(404)
                ->assertJson(['error' => 'User not found']);
    }

    #[Test]
    public function it_returns_error_when_club_not_found()
    {
        // Supprimer le club
        $this->club->delete();
        
        $teacherData = [
            'name' => 'Test Teacher',
            'email' => 'teacher@example.com'
        ];
        
        $response = $this->postJson('/api/club/teachers-test', $teacherData);
        
        $response->assertStatus(404)
                ->assertJson(['error' => 'Club not found']);
    }

    #[Test]
    public function it_handles_database_errors_gracefully()
    {
        // Mock une erreur de base de données
        $this->mock(\Illuminate\Database\DatabaseManager::class, function ($mock) {
            $mock->shouldReceive('connection')->andThrow(new \Exception('Database error'));
        });
        
        $teacherData = [
            'name' => 'Test Teacher',
            'email' => 'teacher@example.com'
        ];
        
        $response = $this->postJson('/api/club/teachers-test', $teacherData);
        
        $response->assertStatus(500)
                ->assertJsonStructure([
                    'error',
                    'message',
                    'trace'
                ]);
    }

    #[Test]
    public function it_can_create_teacher_with_specializations_array()
    {
        $teacherData = [
            'name' => 'Test Teacher',
            'email' => 'teacher@example.com',
            'specializations' => ['dressage', 'obstacle', 'cross']
        ];
        
        $response = $this->postJson('/api/club/teachers-test', $teacherData);
        
        $response->assertStatus(201);
        
        $teacher = Teacher::where('user_id', $response->json('teacher.id'))->first();
        $this->assertEquals(['dressage', 'obstacle', 'cross'], $teacher->specializations);
    }

    #[Test]
    public function it_can_create_teacher_with_numeric_values()
    {
        $teacherData = [
            'name' => 'Test Teacher',
            'email' => 'teacher@example.com',
            'experience_years' => '5', // String numérique
            'hourly_rate' => '60.50' // String numérique avec décimales
        ];
        
        $response = $this->postJson('/api/club/teachers-test', $teacherData);
        
        $response->assertStatus(201);
        
        $teacher = Teacher::where('user_id', $response->json('teacher.id'))->first();
        $this->assertEquals(5, $teacher->experience_years);
        $this->assertEquals(60.50, $teacher->hourly_rate);
    }
}
