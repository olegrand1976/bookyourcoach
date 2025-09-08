<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Skill;
use App\Models\Certification;
use App\Models\TeacherSkill;
use App\Models\TeacherCertification;
use App\Models\Teacher;
use App\Models\Student;
use App\Models\Lesson;
use App\Models\ActivityType;

class GraphDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🎓 Création des données pour les analyses graphiques...');
        $this->createSkills();
        $this->createCertifications();
        $this->assignSkillsToTeachers();
        $this->assignCertificationsToTeachers();
        $this->createLessonsWithEvaluations();
        $this->command->info('✅ Données graphiques créées avec succès');
    }

    private function createSkills(): void
    {
        $this->command->info('📚 Création des compétences...');
        
        $equestrianType = ActivityType::where('slug', 'equestrian')->first();
        $swimmingType = ActivityType::where('slug', 'swimming')->first();

        $skills = [
            ['name' => 'Dressage', 'category' => 'technical', 'activity_type_id' => $equestrianType?->id, 'description' => 'Art de dresser le cheval', 'icon' => '🏇'],
            ['name' => 'CSO', 'category' => 'technical', 'activity_type_id' => $equestrianType?->id, 'description' => 'Concours de saut d\'obstacles', 'icon' => '🏆'],
            ['name' => 'Natation Sportive', 'category' => 'technical', 'activity_type_id' => $swimmingType?->id, 'description' => 'Natation technique', 'icon' => '🏊‍♂️'],
            ['name' => 'Pédagogie', 'category' => 'pedagogical', 'activity_type_id' => null, 'description' => 'Compétences pédagogiques', 'icon' => '👨‍🏫'],
            ['name' => 'Communication', 'category' => 'communication', 'activity_type_id' => null, 'description' => 'Compétences en communication', 'icon' => '💬']
        ];

        foreach ($skills as $skillData) {
            Skill::firstOrCreate(['name' => $skillData['name']], array_merge($skillData, ['is_active' => true]));
        }
    }

    private function createCertifications(): void
    {
        $this->command->info('🏆 Création des certifications...');
        
        $equestrianType = ActivityType::where('slug', 'equestrian')->first();
        $swimmingType = ActivityType::where('slug', 'swimming')->first();

        $certifications = [
            ['name' => 'BEES 1', 'issuing_authority' => 'Ministère des Sports', 'category' => 'official', 'activity_type_id' => $equestrianType?->id],
            ['name' => 'Galop 7', 'issuing_authority' => 'FFÉ', 'category' => 'federation', 'activity_type_id' => $equestrianType?->id],
            ['name' => 'BEESAN', 'issuing_authority' => 'Ministère des Sports', 'category' => 'official', 'activity_type_id' => $swimmingType?->id],
            ['name' => 'BNSSA', 'issuing_authority' => 'Ministère de l\'Intérieur', 'category' => 'official', 'activity_type_id' => $swimmingType?->id, 'validity_years' => 5]
        ];

        foreach ($certifications as $certData) {
            Certification::firstOrCreate(['name' => $certData['name']], array_merge($certData, ['is_active' => true]));
        }
    }

    private function assignSkillsToTeachers(): void
    {
        $this->command->info('👨‍🏫 Attribution des compétences aux enseignants...');
        
        $teachers = Teacher::with('user')->get();
        $skills = Skill::all();

        foreach ($teachers as $teacher) {
            $randomSkills = $skills->random(rand(2, 4));
            foreach ($randomSkills as $skill) {
                TeacherSkill::firstOrCreate(
                    ['teacher_id' => $teacher->id, 'skill_id' => $skill->id],
                    [
                        'level' => ['beginner', 'intermediate', 'advanced', 'expert'][rand(0, 3)],
                        'experience_years' => rand(1, 8),
                        'acquired_date' => now()->subYears(rand(1, 5)),
                        'last_practiced' => now()->subDays(rand(1, 30)),
                        'is_verified' => rand(0, 1),
                        'is_active' => true
                    ]
                );
            }
        }
    }

    private function assignCertificationsToTeachers(): void
    {
        $this->command->info('📜 Attribution des certifications aux enseignants...');
        
        $teachers = Teacher::with('user')->get();
        $certifications = Certification::all();

        foreach ($teachers as $teacher) {
            $randomCerts = $certifications->random(rand(1, 2));
            foreach ($randomCerts as $certification) {
                TeacherCertification::firstOrCreate(
                    ['teacher_id' => $teacher->id, 'certification_id' => $certification->id],
                    [
                        'obtained_date' => now()->subYears(rand(1, 6)),
                        'expiry_date' => $certification->validity_years ? now()->addYears($certification->validity_years) : null,
                        'certificate_number' => strtoupper(substr($certification->name, 0, 4)) . '-' . rand(100000, 999999),
                        'is_valid' => true,
                        'is_verified' => rand(0, 1),
                        'is_active' => true
                    ]
                );
            }
        }
    }

    private function createLessonsWithEvaluations(): void
    {
        $this->command->info('📚 Création des cours avec évaluations...');
        
        $students = Student::with('user')->get();
        $teachers = Teacher::with('user')->get();

        foreach ($students as $student) {
            for ($i = 0; $i < rand(3, 8); $i++) {
                $teacher = $teachers->random();
                Lesson::firstOrCreate(
                    ['student_id' => $student->id, 'teacher_id' => $teacher->id, 'title' => 'Cours ' . ($i + 1)],
                    [
                        'discipline_id' => rand(1, 5),
                        'start_time' => now()->subDays(rand(1, 60)),
                        'end_time' => now()->subDays(rand(1, 60))->addHour(),
                        'duration' => 60,
                        'price' => rand(30, 70),
                        'status' => 'completed',
                        'rating' => rand(3, 5),
                        'progress' => rand(60, 100)
                    ]
                );
            }
        }
    }
}