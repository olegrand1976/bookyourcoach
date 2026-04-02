<?php

namespace Tests\Feature\Api;

use App\Mail\TeacherLessonReplacementInvitationMail;
use App\Mail\TeacherLessonReplacementOutcomeMail;
use App\Models\Club;
use App\Models\CourseType;
use App\Models\Lesson;
use App\Models\LessonReplacement;
use App\Models\Location;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TeacherLessonReplacementControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_list_replacements_for_teacher()
    {
        // Arrange
        $user = $this->actingAsTeacher();
        $teacher = $user->teacher;
        
        $otherTeacher = Teacher::factory()->create();
        $courseType = CourseType::factory()->create();
        $location = Location::factory()->create();
        
        $lesson1 = Lesson::factory()->create([
            'teacher_id' => $teacher->id,
            'course_type_id' => $courseType->id,
            'location_id' => $location->id,
        ]);
        
        $lesson2 = Lesson::factory()->create([
            'teacher_id' => $otherTeacher->id,
            'course_type_id' => $courseType->id,
            'location_id' => $location->id,
        ]);

        // Créer des remplacements où on est le prof d'origine
        $replacement1 = LessonReplacement::factory()->create([
            'lesson_id' => $lesson1->id,
            'original_teacher_id' => $teacher->id,
            'replacement_teacher_id' => $otherTeacher->id,
            'status' => 'pending',
        ]);

        // Créer des remplacements où on est le remplaçant
        $replacement2 = LessonReplacement::factory()->create([
            'lesson_id' => $lesson2->id,
            'original_teacher_id' => $otherTeacher->id,
            'replacement_teacher_id' => $teacher->id,
            'status' => 'pending',
        ]);

        // Act
        $response = $this->getJson('/api/teacher/lesson-replacements');

        // Assert
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         '*' => [
                             'id',
                             'lesson_id',
                             'original_teacher_id',
                             'replacement_teacher_id',
                             'status',
                             'lesson',
                             'original_teacher',
                             'replacement_teacher',
                         ]
                     ]
                 ]);

        $replacements = $response->json('data');
        $replacementIds = collect($replacements)->pluck('id')->toArray();
        
        $this->assertContains($replacement1->id, $replacementIds);
        $this->assertContains($replacement2->id, $replacementIds);
    }

    #[Test]
    public function it_returns_404_if_teacher_profile_not_found()
    {
        // Arrange
        $user = User::factory()->create([
            'role' => 'teacher',
            'status' => 'active',
        ]);

        Sanctum::actingAs($user);

        // Act
        $response = $this->getJson('/api/teacher/lesson-replacements');

        // Assert
        $response->assertStatus(404)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Profil enseignant introuvable'
                 ]);
    }

    #[Test]
    public function it_can_create_replacement_request()
    {
        // Arrange
        $user = $this->actingAsTeacher();
        $teacher = $user->teacher;
        
        $otherTeacher = Teacher::factory()->create();
        $courseType = CourseType::factory()->create();
        $location = Location::factory()->create();
        
        $lesson = Lesson::factory()->create([
            'teacher_id' => $teacher->id,
            'course_type_id' => $courseType->id,
            'location_id' => $location->id,
            'start_time' => Carbon::now()->addDays(2)->setTime(10, 0),
            'end_time' => Carbon::now()->addDays(2)->setTime(11, 0),
        ]);

        $replacementData = [
            'lesson_id' => $lesson->id,
            'replacement_teacher_id' => $otherTeacher->id,
            'reason' => 'Indisponibilité pour cause de maladie',
            'notes' => 'Cours à reporter si possible',
        ];

        // Act
        $response = $this->postJson('/api/teacher/lesson-replacements', $replacementData);

        // Assert
        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         'id',
                         'lesson_id',
                         'original_teacher_id',
                         'replacement_teacher_id',
                         'status',
                     ]
                 ]);

        $this->assertDatabaseHas('lesson_replacements', [
            'lesson_id' => $lesson->id,
            'original_teacher_id' => $teacher->id,
            'replacement_teacher_id' => $otherTeacher->id,
            'status' => 'pending',
            'reason' => 'Indisponibilité pour cause de maladie',
        ]);
    }

    #[Test]
    public function it_validates_replacement_request_data()
    {
        // Arrange
        $user = $this->actingAsTeacher();

        // Act
        $response = $this->postJson('/api/teacher/lesson-replacements', []);

        // Assert
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['lesson_id', 'replacement_teacher_id', 'reason']);
    }

    #[Test]
    public function it_cannot_create_replacement_for_past_lesson()
    {
        // Arrange
        $user = $this->actingAsTeacher();
        $teacher = $user->teacher;
        
        $otherTeacher = Teacher::factory()->create();
        $courseType = CourseType::factory()->create();
        $location = Location::factory()->create();
        
        $lesson = Lesson::factory()->create([
            'teacher_id' => $teacher->id,
            'course_type_id' => $courseType->id,
            'location_id' => $location->id,
            'start_time' => Carbon::now()->subDays(2), // Passé
            'end_time' => Carbon::now()->subDays(2)->addHour(),
        ]);

        $replacementData = [
            'lesson_id' => $lesson->id,
            'replacement_teacher_id' => $otherTeacher->id,
            'reason' => 'Raison de test',
        ];

        // Act
        $response = $this->postJson('/api/teacher/lesson-replacements', $replacementData);

        // Assert
        $response->assertStatus(400)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Impossible de demander un remplacement pour un cours passé'
                 ]);
    }

    #[Test]
    public function it_cannot_select_self_as_replacement()
    {
        // Arrange
        $user = $this->actingAsTeacher();
        $teacher = $user->teacher;
        
        $courseType = CourseType::factory()->create();
        $location = Location::factory()->create();
        
        $lesson = Lesson::factory()->create([
            'teacher_id' => $teacher->id,
            'course_type_id' => $courseType->id,
            'location_id' => $location->id,
            'start_time' => Carbon::now()->addDays(2),
        ]);

        $replacementData = [
            'lesson_id' => $lesson->id,
            'replacement_teacher_id' => $teacher->id, // Soi-même
            'reason' => 'Raison de test',
        ];

        // Act
        $response = $this->postJson('/api/teacher/lesson-replacements', $replacementData);

        // Assert
        $response->assertStatus(400)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Vous ne pouvez pas vous sélectionner comme remplaçant'
                 ]);
    }

    #[Test]
    public function it_cannot_create_replacement_for_others_lessons()
    {
        // Arrange
        $user = $this->actingAsTeacher();
        $teacher = $user->teacher;
        
        $otherTeacher = Teacher::factory()->create();
        $courseType = CourseType::factory()->create();
        $location = Location::factory()->create();
        
        $lesson = Lesson::factory()->create([
            'teacher_id' => $otherTeacher->id, // Cours d'un autre enseignant
            'course_type_id' => $courseType->id,
            'location_id' => $location->id,
            'start_time' => Carbon::now()->addDays(2),
        ]);

        $replacementData = [
            'lesson_id' => $lesson->id,
            'replacement_teacher_id' => Teacher::factory()->create()->id,
            'reason' => 'Raison de test',
        ];

        // Act
        $response = $this->postJson('/api/teacher/lesson-replacements', $replacementData);

        // Assert
        $response->assertStatus(403)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Ce cours ne vous appartient pas'
                 ]);
    }

    #[Test]
    public function it_cannot_create_duplicate_pending_replacement()
    {
        // Arrange
        $user = $this->actingAsTeacher();
        $teacher = $user->teacher;
        
        $otherTeacher = Teacher::factory()->create();
        $courseType = CourseType::factory()->create();
        $location = Location::factory()->create();
        
        $lesson = Lesson::factory()->create([
            'teacher_id' => $teacher->id,
            'course_type_id' => $courseType->id,
            'location_id' => $location->id,
            'start_time' => Carbon::now()->addDays(2),
        ]);

        // Créer une demande existante
        LessonReplacement::factory()->create([
            'lesson_id' => $lesson->id,
            'original_teacher_id' => $teacher->id,
            'replacement_teacher_id' => $otherTeacher->id,
            'status' => 'pending',
        ]);

        $replacementData = [
            'lesson_id' => $lesson->id,
            'replacement_teacher_id' => $otherTeacher->id,
            'reason' => 'Nouvelle demande',
        ];

        // Act
        $response = $this->postJson('/api/teacher/lesson-replacements', $replacementData);

        // Assert
        $response->assertStatus(400)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Une demande de remplacement est déjà en attente pour ce cours'
                 ]);
    }

    #[Test]
    public function it_cannot_create_replacement_if_replacement_teacher_has_conflict()
    {
        // Arrange
        $user = $this->actingAsTeacher();
        $teacher = $user->teacher;
        
        $replacementTeacher = Teacher::factory()->create();
        $courseType = CourseType::factory()->create();
        $location = Location::factory()->create();
        
        $lesson = Lesson::factory()->create([
            'teacher_id' => $teacher->id,
            'course_type_id' => $courseType->id,
            'location_id' => $location->id,
            'start_time' => Carbon::now()->addDays(2)->setTime(10, 0),
            'end_time' => Carbon::now()->addDays(2)->setTime(11, 0),
        ]);

        // Créer un cours qui entre en conflit avec le remplaçant
        Lesson::factory()->create([
            'teacher_id' => $replacementTeacher->id,
            'course_type_id' => $courseType->id,
            'location_id' => $location->id,
            'start_time' => Carbon::now()->addDays(2)->setTime(10, 30), // Conflit
            'end_time' => Carbon::now()->addDays(2)->setTime(11, 30),
            'status' => 'confirmed',
        ]);

        $replacementData = [
            'lesson_id' => $lesson->id,
            'replacement_teacher_id' => $replacementTeacher->id,
            'reason' => 'Raison de test',
        ];

        // Act
        $response = $this->postJson('/api/teacher/lesson-replacements', $replacementData);

        // Assert
        $response->assertStatus(400)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Le professeur de remplacement n\'est pas disponible à cet horaire'
                 ]);
    }

    #[Test]
    public function it_allows_replacement_when_substitute_lesson_ends_when_requested_lesson_starts()
    {
        $user = $this->actingAsTeacher();
        $teacher = $user->teacher;

        $replacementTeacher = Teacher::factory()->create();
        $courseType = CourseType::factory()->create();
        $location = Location::factory()->create();

        $day = Carbon::now()->addDays(3)->setTime(0, 0);

        $lesson = Lesson::factory()->create([
            'teacher_id' => $teacher->id,
            'course_type_id' => $courseType->id,
            'location_id' => $location->id,
            'start_time' => $day->copy()->setTime(10, 0),
            'end_time' => $day->copy()->setTime(11, 0),
            'status' => 'confirmed',
        ]);

        // Cours du remplaçant : se termine exactement au début du cours à remplacer → pas de chevauchement
        Lesson::factory()->create([
            'teacher_id' => $replacementTeacher->id,
            'course_type_id' => $courseType->id,
            'location_id' => $location->id,
            'start_time' => $day->copy()->setTime(9, 0),
            'end_time' => $day->copy()->setTime(10, 0),
            'status' => 'confirmed',
        ]);

        $this->postJson('/api/teacher/lesson-replacements', [
            'lesson_id' => $lesson->id,
            'replacement_teacher_id' => $replacementTeacher->id,
            'reason' => 'Test créneaux adjacents',
        ])->assertStatus(201);
    }

    #[Test]
    public function it_can_accept_replacement_request()
    {
        // Arrange
        $originalTeacher = Teacher::factory()->create();
        $user = $this->actingAsTeacher();
        $replacementTeacher = $user->teacher;
        
        $courseType = CourseType::factory()->create();
        $location = Location::factory()->create();
        $student = Student::factory()->create();
        
        $lesson = Lesson::factory()->create([
            'teacher_id' => $originalTeacher->id,
            'student_id' => $student->id,
            'course_type_id' => $courseType->id,
            'location_id' => $location->id,
            'start_time' => Carbon::now()->addDays(2),
        ]);

        $replacement = LessonReplacement::factory()->create([
            'lesson_id' => $lesson->id,
            'original_teacher_id' => $originalTeacher->id,
            'replacement_teacher_id' => $replacementTeacher->id,
            'status' => 'pending',
        ]);

        // Act
        $response = $this->postJson("/api/teacher/lesson-replacements/{$replacement->id}/respond", [
            'action' => 'accept',
        ]);

        // Assert
        $response->assertStatus(200)
                 ->assertJson(['success' => true]);
        $this->assertStringContainsString(
            'Remplacement accepté',
            (string) $response->json('message')
        );

        $this->assertDatabaseHas('lesson_replacements', [
            'id' => $replacement->id,
            'status' => 'accepted',
        ]);

        $this->assertDatabaseHas('lessons', [
            'id' => $lesson->id,
            'teacher_id' => $replacementTeacher->id,
        ]);
    }

    #[Test]
    public function it_can_reject_replacement_request()
    {
        // Arrange
        $originalTeacher = Teacher::factory()->create();
        $user = $this->actingAsTeacher();
        $replacementTeacher = $user->teacher;
        
        $courseType = CourseType::factory()->create();
        $location = Location::factory()->create();
        
        $lesson = Lesson::factory()->create([
            'teacher_id' => $originalTeacher->id,
            'course_type_id' => $courseType->id,
            'location_id' => $location->id,
            'start_time' => Carbon::now()->addDays(2),
        ]);

        $replacement = LessonReplacement::factory()->create([
            'lesson_id' => $lesson->id,
            'original_teacher_id' => $originalTeacher->id,
            'replacement_teacher_id' => $replacementTeacher->id,
            'status' => 'pending',
        ]);

        // Act
        $response = $this->postJson("/api/teacher/lesson-replacements/{$replacement->id}/respond", [
            'action' => 'reject',
        ]);

        // Assert
        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Remplacement refusé'
                 ]);

        $this->assertDatabaseHas('lesson_replacements', [
            'id' => $replacement->id,
            'status' => 'rejected',
        ]);

        // Le cours ne doit pas changer de professeur
        $this->assertDatabaseHas('lessons', [
            'id' => $lesson->id,
            'teacher_id' => $originalTeacher->id,
        ]);
    }

    #[Test]
    public function it_cannot_respond_to_replacement_if_not_replacement_teacher()
    {
        // Arrange
        $user = $this->actingAsTeacher();
        $teacher = $user->teacher;
        
        $originalTeacher = Teacher::factory()->create();
        $replacementTeacher = Teacher::factory()->create();
        
        $lesson = Lesson::factory()->create([
            'teacher_id' => $originalTeacher->id,
        ]);

        $replacement = LessonReplacement::factory()->create([
            'lesson_id' => $lesson->id,
            'original_teacher_id' => $originalTeacher->id,
            'replacement_teacher_id' => $replacementTeacher->id,
            'status' => 'pending',
        ]);

        // Act - Tentative de réponse par un autre enseignant
        $response = $this->postJson("/api/teacher/lesson-replacements/{$replacement->id}/respond", [
            'action' => 'accept',
        ]);

        // Assert
        $response->assertStatus(403)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Vous n\'êtes pas autorisé à répondre à cette demande'
                 ]);
    }

    #[Test]
    public function it_cannot_respond_to_already_processed_replacement()
    {
        // Arrange
        $originalTeacher = Teacher::factory()->create();
        $user = $this->actingAsTeacher();
        $replacementTeacher = $user->teacher;
        
        $lesson = Lesson::factory()->create([
            'teacher_id' => $originalTeacher->id,
        ]);

        $replacement = LessonReplacement::factory()->create([
            'lesson_id' => $lesson->id,
            'original_teacher_id' => $originalTeacher->id,
            'replacement_teacher_id' => $replacementTeacher->id,
            'status' => 'accepted', // Déjà traité
        ]);

        // Act
        $response = $this->postJson("/api/teacher/lesson-replacements/{$replacement->id}/respond", [
            'action' => 'accept',
        ]);

        // Assert
        $response->assertStatus(400)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Cette demande a déjà été traitée'
                 ]);
    }

    #[Test]
    public function it_can_cancel_replacement_request()
    {
        // Arrange
        $user = $this->actingAsTeacher();
        $teacher = $user->teacher;
        
        $otherTeacher = Teacher::factory()->create();
        $lesson = Lesson::factory()->create([
            'teacher_id' => $teacher->id,
        ]);

        $replacement = LessonReplacement::factory()->create([
            'lesson_id' => $lesson->id,
            'original_teacher_id' => $teacher->id,
            'replacement_teacher_id' => $otherTeacher->id,
            'status' => 'pending',
        ]);

        // Act
        $response = $this->deleteJson("/api/teacher/lesson-replacements/{$replacement->id}");

        // Assert
        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Demande de remplacement annulée'
                 ]);

        $this->assertDatabaseHas('lesson_replacements', [
            'id' => $replacement->id,
            'status' => 'cancelled',
        ]);
    }

    #[Test]
    public function it_cannot_cancel_replacement_if_not_original_teacher()
    {
        // Arrange
        $user = $this->actingAsTeacher();
        $teacher = $user->teacher;
        
        $originalTeacher = Teacher::factory()->create();
        $replacementTeacher = Teacher::factory()->create();
        
        $lesson = Lesson::factory()->create([
            'teacher_id' => $originalTeacher->id,
        ]);

        $replacement = LessonReplacement::factory()->create([
            'lesson_id' => $lesson->id,
            'original_teacher_id' => $originalTeacher->id,
            'replacement_teacher_id' => $replacementTeacher->id,
            'status' => 'pending',
        ]);

        // Act - Tentative d'annulation par un autre enseignant
        $response = $this->deleteJson("/api/teacher/lesson-replacements/{$replacement->id}");

        // Assert
        $response->assertStatus(403)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Vous n\'êtes pas autorisé à annuler cette demande'
                 ]);
    }

    #[Test]
    public function it_cannot_cancel_already_processed_replacement()
    {
        // Arrange
        $user = $this->actingAsTeacher();
        $teacher = $user->teacher;
        
        $otherTeacher = Teacher::factory()->create();
        $lesson = Lesson::factory()->create([
            'teacher_id' => $teacher->id,
        ]);

        $replacement = LessonReplacement::factory()->create([
            'lesson_id' => $lesson->id,
            'original_teacher_id' => $teacher->id,
            'replacement_teacher_id' => $otherTeacher->id,
            'status' => 'accepted', // Déjà traité
        ]);

        // Act
        $response = $this->deleteJson("/api/teacher/lesson-replacements/{$replacement->id}");

        // Assert
        $response->assertStatus(400)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Impossible d\'annuler une demande déjà traitée'
                 ]);
    }

    #[Test]
    public function it_requires_authentication_to_access_replacements()
    {
        // Act
        $response = $this->getJson('/api/teacher/lesson-replacements');

        // Assert
        $response->assertStatus(401);
    }

    #[Test]
    public function it_requires_teacher_role_to_access_replacements()
    {
        // Arrange
        $clubUser = $this->actingAsClub();

        // Act
        $response = $this->getJson('/api/teacher/lesson-replacements');

        // Assert
        $response->assertStatus(403);
    }

    #[Test]
    public function it_validates_bulk_replacement_request_data()
    {
        $user = $this->actingAsTeacher();

        $response = $this->postJson('/api/teacher/lesson-replacements/bulk', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['lesson_ids', 'replacement_teacher_id', 'reason']);
    }

    #[Test]
    public function it_can_create_bulk_replacement_requests()
    {
        Mail::fake();

        $user = $this->actingAsTeacher();
        $teacher = $user->teacher;
        $club = Club::factory()->create();
        $teacher->clubs()->attach($club->id, ['is_active' => true, 'joined_at' => now()]);

        $otherTeacher = Teacher::factory()->create();
        $otherTeacher->clubs()->attach($club->id, ['is_active' => true, 'joined_at' => now()]);

        $admin = User::factory()->create([
            'role' => 'club',
            'status' => 'active',
            'is_active' => true,
        ]);
        DB::table('club_user')->insert([
            'club_id' => $club->id,
            'user_id' => $admin->id,
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $courseType = CourseType::factory()->create();
        $location = Location::factory()->create();
        $base = Carbon::now()->addDays(5);

        $lesson1 = Lesson::factory()->create([
            'teacher_id' => $teacher->id,
            'club_id' => $club->id,
            'course_type_id' => $courseType->id,
            'location_id' => $location->id,
            'start_time' => $base->copy()->setTime(10, 0),
            'end_time' => $base->copy()->setTime(11, 0),
            'status' => 'confirmed',
        ]);

        $lesson2 = Lesson::factory()->create([
            'teacher_id' => $teacher->id,
            'club_id' => $club->id,
            'course_type_id' => $courseType->id,
            'location_id' => $location->id,
            'start_time' => $base->copy()->addDay()->setTime(14, 0),
            'end_time' => $base->copy()->addDay()->setTime(15, 0),
            'status' => 'confirmed',
        ]);

        $response = $this->postJson('/api/teacher/lesson-replacements/bulk', [
            'lesson_ids' => [$lesson1->id, $lesson2->id],
            'replacement_teacher_id' => $otherTeacher->id,
            'reason' => 'Congés',
            'notes' => 'Merci',
        ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('lesson_replacements', [
            'lesson_id' => $lesson1->id,
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('lesson_replacements', [
            'lesson_id' => $lesson2->id,
            'status' => 'pending',
        ]);

        Mail::assertSent(TeacherLessonReplacementInvitationMail::class, function (TeacherLessonReplacementInvitationMail $mail) use ($otherTeacher, $admin) {
            return $mail->lessons->count() === 2
                && $mail->hasTo($otherTeacher->user->email)
                && $mail->hasCc($admin->email);
        });
    }

    #[Test]
    public function it_rejects_bulk_when_lessons_from_different_clubs()
    {
        $user = $this->actingAsTeacher();
        $teacher = $user->teacher;
        $clubA = Club::factory()->create();
        $clubB = Club::factory()->create();
        $teacher->clubs()->attach($clubA->id, ['is_active' => true, 'joined_at' => now()]);
        $teacher->clubs()->attach($clubB->id, ['is_active' => true, 'joined_at' => now()]);

        $otherTeacher = Teacher::factory()->create();
        $otherTeacher->clubs()->attach($clubA->id, ['is_active' => true, 'joined_at' => now()]);
        $otherTeacher->clubs()->attach($clubB->id, ['is_active' => true, 'joined_at' => now()]);

        $courseType = CourseType::factory()->create();
        $location = Location::factory()->create();
        $base = Carbon::now()->addDays(4);

        $lessonA = Lesson::factory()->create([
            'teacher_id' => $teacher->id,
            'club_id' => $clubA->id,
            'course_type_id' => $courseType->id,
            'location_id' => $location->id,
            'start_time' => $base->copy()->setTime(10, 0),
            'end_time' => $base->copy()->setTime(11, 0),
            'status' => 'confirmed',
        ]);

        $lessonB = Lesson::factory()->create([
            'teacher_id' => $teacher->id,
            'club_id' => $clubB->id,
            'course_type_id' => $courseType->id,
            'location_id' => $location->id,
            'start_time' => $base->copy()->addDay()->setTime(10, 0),
            'end_time' => $base->copy()->addDay()->setTime(11, 0),
            'status' => 'confirmed',
        ]);

        $response = $this->postJson('/api/teacher/lesson-replacements/bulk', [
            'lesson_ids' => [$lessonA->id, $lessonB->id],
            'replacement_teacher_id' => $otherTeacher->id,
            'reason' => 'Test',
        ]);

        $response->assertStatus(400)
            ->assertJsonFragment(['success' => false]);
    }

    #[Test]
    public function it_rejects_bulk_when_replacement_teacher_not_in_club()
    {
        $user = $this->actingAsTeacher();
        $teacher = $user->teacher;
        $club = Club::factory()->create();
        $teacher->clubs()->attach($club->id, ['is_active' => true, 'joined_at' => now()]);

        $outsider = Teacher::factory()->create();

        $courseType = CourseType::factory()->create();
        $location = Location::factory()->create();
        $base = Carbon::now()->addDays(4);

        $lesson = Lesson::factory()->create([
            'teacher_id' => $teacher->id,
            'club_id' => $club->id,
            'course_type_id' => $courseType->id,
            'location_id' => $location->id,
            'start_time' => $base->copy()->setTime(10, 0),
            'end_time' => $base->copy()->setTime(11, 0),
            'status' => 'confirmed',
        ]);

        $response = $this->postJson('/api/teacher/lesson-replacements/bulk', [
            'lesson_ids' => [$lesson->id],
            'replacement_teacher_id' => $outsider->id,
            'reason' => 'Test',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'L\'enseignant remplaçant doit être affilié au même club que les cours sélectionnés.',
            ]);
    }

    #[Test]
    public function it_sends_request_email_to_club_admins_on_single_store_when_lesson_has_club()
    {
        Mail::fake();

        $user = $this->actingAsTeacher();
        $teacher = $user->teacher;
        $club = Club::factory()->create();
        $teacher->clubs()->attach($club->id, ['is_active' => true, 'joined_at' => now()]);

        $otherTeacher = Teacher::factory()->create();
        $otherTeacher->clubs()->attach($club->id, ['is_active' => true, 'joined_at' => now()]);

        $admin = User::factory()->create([
            'role' => 'club',
            'status' => 'active',
            'is_active' => true,
        ]);
        DB::table('club_user')->insert([
            'club_id' => $club->id,
            'user_id' => $admin->id,
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $courseType = CourseType::factory()->create();
        $location = Location::factory()->create();

        $lesson = Lesson::factory()->create([
            'teacher_id' => $teacher->id,
            'club_id' => $club->id,
            'course_type_id' => $courseType->id,
            'location_id' => $location->id,
            'start_time' => Carbon::now()->addDays(3)->setTime(10, 0),
            'end_time' => Carbon::now()->addDays(3)->setTime(11, 0),
            'status' => 'confirmed',
        ]);

        $this->postJson('/api/teacher/lesson-replacements', [
            'lesson_id' => $lesson->id,
            'replacement_teacher_id' => $otherTeacher->id,
            'reason' => 'Motif email',
        ])->assertStatus(201);

        $replacement = LessonReplacement::with('lesson')->latest('id')->first();
        $this->assertNotNull($replacement);
        $this->assertSame($club->id, (int) $replacement->lesson->club_id, 'Le cours doit conserver club_id pour déclencher l’email');

        Mail::assertSent(TeacherLessonReplacementInvitationMail::class, function (TeacherLessonReplacementInvitationMail $mail) use ($otherTeacher, $admin) {
            return $mail->lessons->count() === 1
                && $mail->reason === 'Motif email'
                && $mail->hasTo($otherTeacher->user->email)
                && $mail->hasCc($admin->email);
        });
    }

    #[Test]
    public function it_sends_acceptance_email_to_club_admins_when_lesson_has_club()
    {
        Mail::fake();

        $originalTeacherUser = User::factory()->create([
            'role' => 'teacher',
            'status' => 'active',
            'is_active' => true,
        ]);
        $originalTeacher = Teacher::factory()->create(['user_id' => $originalTeacherUser->id]);

        $club = Club::factory()->create();
        $originalTeacher->clubs()->attach($club->id, ['is_active' => true, 'joined_at' => now()]);

        $user = $this->actingAsTeacher();
        $replacementTeacher = $user->teacher;
        $replacementTeacher->clubs()->attach($club->id, ['is_active' => true, 'joined_at' => now()]);

        $admin = User::factory()->create([
            'role' => 'club',
            'status' => 'active',
            'is_active' => true,
        ]);
        DB::table('club_user')->insert([
            'club_id' => $club->id,
            'user_id' => $admin->id,
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $courseType = CourseType::factory()->create();
        $location = Location::factory()->create();
        $student = Student::factory()->create();

        $lesson = Lesson::factory()->create([
            'teacher_id' => $originalTeacher->id,
            'club_id' => $club->id,
            'student_id' => $student->id,
            'course_type_id' => $courseType->id,
            'location_id' => $location->id,
            'start_time' => Carbon::now()->addDays(3),
            'status' => 'confirmed',
        ]);

        $replacement = LessonReplacement::factory()->create([
            'lesson_id' => $lesson->id,
            'original_teacher_id' => $originalTeacher->id,
            'replacement_teacher_id' => $replacementTeacher->id,
            'status' => 'pending',
        ]);

        $this->postJson("/api/teacher/lesson-replacements/{$replacement->id}/respond", [
            'action' => 'accept',
        ])->assertStatus(200);

        Mail::assertSent(TeacherLessonReplacementOutcomeMail::class, function (TeacherLessonReplacementOutcomeMail $mail) use ($originalTeacherUser, $admin) {
            return $mail->accepted === true
                && $mail->hasTo($originalTeacherUser->email)
                && $mail->hasCc($admin->email);
        });
    }

    #[Test]
    public function it_sends_outcome_email_to_requester_on_reject_with_club_cc()
    {
        Mail::fake();

        $originalTeacherUser = User::factory()->create([
            'role' => 'teacher',
            'status' => 'active',
            'is_active' => true,
        ]);
        $originalTeacher = Teacher::factory()->create(['user_id' => $originalTeacherUser->id]);

        $club = Club::factory()->create();
        $originalTeacher->clubs()->attach($club->id, ['is_active' => true, 'joined_at' => now()]);

        $user = $this->actingAsTeacher();
        $replacementTeacher = $user->teacher;
        $replacementTeacher->clubs()->attach($club->id, ['is_active' => true, 'joined_at' => now()]);

        $admin = User::factory()->create([
            'role' => 'club',
            'status' => 'active',
            'is_active' => true,
        ]);
        DB::table('club_user')->insert([
            'club_id' => $club->id,
            'user_id' => $admin->id,
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $courseType = CourseType::factory()->create();
        $location = Location::factory()->create();

        $lesson = Lesson::factory()->create([
            'teacher_id' => $originalTeacher->id,
            'club_id' => $club->id,
            'course_type_id' => $courseType->id,
            'location_id' => $location->id,
            'start_time' => Carbon::now()->addDays(3),
            'status' => 'confirmed',
        ]);

        $replacement = LessonReplacement::factory()->create([
            'lesson_id' => $lesson->id,
            'original_teacher_id' => $originalTeacher->id,
            'replacement_teacher_id' => $replacementTeacher->id,
            'status' => 'pending',
        ]);

        $this->postJson("/api/teacher/lesson-replacements/{$replacement->id}/respond", [
            'action' => 'reject',
        ])->assertStatus(200);

        Mail::assertSent(TeacherLessonReplacementOutcomeMail::class, function (TeacherLessonReplacementOutcomeMail $mail) use ($originalTeacherUser, $admin) {
            return $mail->accepted === false
                && $mail->hasTo($originalTeacherUser->email)
                && $mail->hasCc($admin->email);
        });
    }
}

