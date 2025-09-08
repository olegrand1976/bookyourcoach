<?php

namespace Tests\Unit\Models;

use App\Models\Student;
use App\Models\User;
use App\Models\Club;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class StudentTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_be_instantiated()
    {
        $student = new Student();

        $this->assertInstanceOf(Student::class, $student);
    }

    /** @test */
    public function it_has_correct_table_name()
    {
        $student = new Student();

        $this->assertEquals('students', $student->getTable());
    }

    /** @test */
    public function it_uses_timestamps()
    {
        $student = new Student();

        $this->assertTrue($student->timestamps);
    }

    /** @test */
    public function it_can_be_created_with_required_fields()
    {
        $user = User::factory()->create(['role' => User::ROLE_STUDENT]);

        $studentData = [
            'user_id' => $user->id,
            'level' => 'debutant',
        ];

        $student = Student::create($studentData);

        $this->assertInstanceOf(Student::class, $student);
        $this->assertEquals($user->id, $student->user_id);
        $this->assertEquals('debutant', $student->level);
    }

    /** @test */
    public function it_has_user_relationship()
    {
        $student = Student::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $student->user());
        $this->assertInstanceOf(User::class, $student->user);
    }

    /** @test */
    public function it_has_club_relationship()
    {
        $student = Student::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $student->club());
    }

    /** @test */
    public function it_has_fillable_attributes()
    {
        $fillable = [
            'user_id',
            'club_id',
            'level',
            'goals',
            'medical_info',
            'emergency_contacts',
            'preferred_disciplines',
            'preferred_levels',
            'preferred_formats',
            'location',
            'max_price',
            'max_distance',
            'notifications_enabled',
        ];

        $student = new Student();
        $this->assertEquals($fillable, $student->getFillable());
    }

    /** @test */
    public function it_can_store_optional_fields()
    {
        $user = User::factory()->create(['role' => User::ROLE_STUDENT]);

        $studentData = [
            'user_id' => $user->id,
            'level' => 'intermediaire',
            'goals' => 'Apprendre le saut d\'obstacles',
            'medical_info' => 'Aucune allergie connue',
            'emergency_contacts' => [
                'name' => 'Marie Dupont',
                'phone' => '01 23 45 67 89',
                'relationship' => 'parent'
            ],
            'notifications_enabled' => true,
        ];

        $student = Student::create($studentData);

        $this->assertEquals('intermediaire', $student->level);
        $this->assertEquals('Apprendre le saut d\'obstacles', $student->goals);
        $this->assertEquals('Aucune allergie connue', $student->medical_info);
        $this->assertEquals('Marie Dupont', $student->emergency_contacts['name']);
        $this->assertEquals('01 23 45 67 89', $student->emergency_contacts['phone']);
        $this->assertTrue($student->notifications_enabled);
    }

    /** @test */
    public function it_can_be_associated_with_club()
    {
        $user = User::factory()->create(['role' => User::ROLE_STUDENT]);
        $club = Club::factory()->create();

        $student = Student::factory()->create([
            'user_id' => $user->id,
            'club_id' => $club->id
        ]);

        $this->assertEquals($club->id, $student->club_id);
        $this->assertInstanceOf(Club::class, $student->club);
    }

    /** @test */
    public function it_can_be_created_without_club()
    {
        $user = User::factory()->create(['role' => User::ROLE_STUDENT]);

        $student = Student::factory()->create([
            'user_id' => $user->id,
            'club_id' => null
        ]);

        $this->assertNull($student->club_id);
        $this->assertNull($student->club);
    }

    /** @test */
    public function it_casts_notifications_enabled_as_boolean()
    {
        $student = Student::factory()->create([
            'notifications_enabled' => true
        ]);

        $this->assertIsBool($student->notifications_enabled);
        $this->assertTrue($student->notifications_enabled);
    }

    /** @test */
    public function it_casts_emergency_contacts_as_array()
    {
        $student = Student::factory()->create([
            'emergency_contacts' => [
                'name' => 'Test Contact',
                'phone' => '123456789',
                'relationship' => 'parent'
            ]
        ]);

        $this->assertIsArray($student->emergency_contacts);
        $this->assertEquals('Test Contact', $student->emergency_contacts['name']);
    }

    /** @test */
    public function it_can_have_different_levels()
    {
        $levels = ['debutant', 'intermediaire', 'avance', 'expert'];

        foreach ($levels as $level) {
            $user = User::factory()->create(['role' => User::ROLE_STUDENT]);
            $student = Student::factory()->create([
                'user_id' => $user->id,
                'level' => $level
            ]);

            $this->assertEquals($level, $student->level);
        }
    }

    /** @test */
    public function it_can_store_goals_as_text()
    {
        $user = User::factory()->create(['role' => User::ROLE_STUDENT]);
        $goals = 'Apprendre le dressage et participer à des compétitions locales';

        $student = Student::factory()->create([
            'user_id' => $user->id,
            'goals' => $goals
        ]);

        $this->assertEquals($goals, $student->goals);
    }

    /** @test */
    public function it_can_store_medical_info()
    {
        $user = User::factory()->create(['role' => User::ROLE_STUDENT]);
        $medicalInfo = 'Allergie aux chevaux, prendre des antihistaminiques avant les cours';

        $student = Student::factory()->create([
            'user_id' => $user->id,
            'medical_info' => $medicalInfo
        ]);

        $this->assertEquals($medicalInfo, $student->medical_info);
    }

    /** @test */
    public function it_can_store_emergency_contacts_information()
    {
        $user = User::factory()->create(['role' => User::ROLE_STUDENT]);
        $emergencyContacts = [
            'name' => 'Jean Dupont',
            'phone' => '06 12 34 56 78',
            'relationship' => 'parent'
        ];

        $student = Student::factory()->create([
            'user_id' => $user->id,
            'emergency_contacts' => $emergencyContacts
        ]);

        $this->assertEquals($emergencyContacts, $student->emergency_contacts);
        $this->assertEquals('Jean Dupont', $student->emergency_contacts['name']);
        $this->assertEquals('06 12 34 56 78', $student->emergency_contacts['phone']);
    }
}