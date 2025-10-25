<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Profile;
use App\Models\Teacher;
use App\Models\Student;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoProfilesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Crée 3 profils de démonstration : Admin, Coach, Élève
     */
    public function run(): void
    {
        $this->command->info('🐎 Création des profils de démonstration activibe...');

        // 1. PROFIL ADMINISTRATEUR
        $admin = User::updateOrCreate(
            ['email' => 'admin@activibe.fr'],
            [
                'name' => 'Marie Dubois',
                'email' => 'admin@activibe.fr',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );

        Profile::updateOrCreate(
            ['user_id' => $admin->id],
            [
                'user_id' => $admin->id,
                'first_name' => 'Marie',
                'last_name' => 'Dubois',
                'phone' => '+33 1 42 86 97 53',
                'date_of_birth' => '1982-05-20',
                'address' => '15 Avenue des Champs-Élysées, 75008 Paris',
                'emergency_contact_name' => 'Pierre Dubois',
                'emergency_contact_phone' => '+33 1 42 86 97 54',
            ]
        );

        $this->command->info('✅ Profil Admin créé : admin@activibe.fr / admin123');

        // 2. PROFIL COACH/ENSEIGNANT
        $coach = User::updateOrCreate(
            ['email' => 'coach@activibe.fr'],
            [
                'name' => 'Jean-Luc Moreau',
                'email' => 'coach@activibe.fr',
                'password' => Hash::make('coach123'),
                'role' => 'teacher',
                'email_verified_at' => now(),
            ]
        );

        $coachProfile = Profile::updateOrCreate(
            ['user_id' => $coach->id],
            [
                'user_id' => $coach->id,
                'first_name' => 'Jean-Luc',
                'last_name' => 'Moreau',
                'phone' => '+33 6 12 34 56 78',
                'date_of_birth' => '1975-09-12',
                'address' => '8 Rue de la Cavalerie, 78000 Versailles',
                'emergency_contact_name' => 'Sylvie Moreau',
                'emergency_contact_phone' => '+33 6 12 34 56 79',
            ]
        );

        Teacher::updateOrCreate(
            ['user_id' => $coach->id],
            [
                'user_id' => $coach->id,
                'specialties' => ['Dressage', 'Obstacle', 'Cross-country'],
                'certifications' => [
                    'BPJEPS - Activités Équestres',
                    'Galop 7 FFE',
                    'Formation Éthologie Équine'
                ],
                'experience_years' => 15,
                'hourly_rate' => 65.00,
                'bio' => 'Instructeur passionné avec 15 ans d\'expérience. Spécialisé en dressage classique et obstacle. Approche pédagogique basée sur le respect du cheval et la progression personnalisée de chaque cavalier.',
                'max_travel_distance' => 50,
                'is_available' => true,
                'rating' => 4.8,
                'total_lessons' => 450,
            ]
        );

        $this->command->info('✅ Profil Coach créé : coach@activibe.fr / coach123');

        // 3. PROFIL ÉLÈVE
        $student = User::updateOrCreate(
            ['email' => 'eleve@activibe.fr'],
            [
                'name' => 'Emma Leclerc',
                'email' => 'eleve@activibe.fr',
                'password' => Hash::make('eleve123'),
                'role' => 'student',
                'email_verified_at' => now(),
            ]
        );

        $studentProfile = Profile::updateOrCreate(
            ['user_id' => $student->id],
            [
                'user_id' => $student->id,
                'first_name' => 'Emma',
                'last_name' => 'Leclerc',
                'phone' => '+33 6 98 76 54 32',
                'date_of_birth' => '1995-12-03',
                'address' => '42 Boulevard Saint-Michel, 75005 Paris',
                'emergency_contact_name' => 'Thomas Leclerc',
                'emergency_contact_phone' => '+33 6 98 76 54 33',
            ]
        );

        Student::updateOrCreate(
            ['user_id' => $student->id],
            [
                'user_id' => $student->id,
                'level' => 'intermediaire',
                'goals' => 'Perfectionnement obstacle et préparation compétition amateur',
                'medical_info' => 'Aucune condition médicale particulière',
                'emergency_contacts' => json_encode([
                    'nom' => 'Thomas Leclerc',
                    'telephone' => '+33 6 98 76 54 33',
                    'relation' => 'Père'
                ]),
                'total_lessons' => 12,
                'total_spent' => 780.00,
                'preferences' => json_encode([
                    'type_lecon' => 'individuel',
                    'discipline_preferee' => 'obstacle',
                    'moment_prefere' => 'matin'
                ]),
            ]
        );

        $this->command->info('✅ Profil Élève créé : eleve@activibe.fr / eleve123');

        $this->command->info('');
        $this->command->info('🎯 IDENTIFIANTS DE CONNEXION :');
        $this->command->info('=====================================');
        $this->command->info('👨‍💼 ADMIN     : admin@activibe.fr / admin123');
        $this->command->info('🏇 COACH     : coach@activibe.fr / coach123');
        $this->command->info('👩‍🎓 ÉLÈVE     : eleve@activibe.fr / eleve123');
        $this->command->info('');
        $this->command->info('🌐 Connectez-vous sur : http://localhost:3000/login');
    }
}
