<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Profile;
use App\Models\Teacher;
use App\Models\Student;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Administrateur principal
        $admin = User::create([
            'name' => 'Administrateur',
            'email' => 'admin@activibe.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        Profile::create([
            'user_id' => $admin->id,
            'first_name' => 'Admin',
            'last_name' => 'Système',
            'phone' => '+32 2 123 45 67',
            'date_of_birth' => '1980-01-01',
            'address' => 'Rue de l\'Administration 1, 1000 Bruxelles',
            'emergency_contact_name' => 'Support Technique',
            'emergency_contact_phone' => '+32 2 123 45 68',
        ]);

        // Enseignants de démonstration
        $teachers = [
            [
                'email' => 'sophie.martin@activibe.com',
                'profile' => [
                    'first_name' => 'Sophie',
                    'last_name' => 'Martin',
                    'phone' => '+32 475 123 456',
                    'date_of_birth' => '1985-03-15',
                    'address' => 'Avenue des Cavaliers 25, 1180 Uccle',
                ],
                'teacher' => [
                    'specialties' => ['dressage', 'saut d\'obstacles'],
                    'experience_years' => 12,
                    'certifications' => ['BEES 2', 'Galop 7 FFE'],
                    'bio' => 'Instructrice passionnée spécialisée en dressage classique et saut d\'obstacles.',
                    'hourly_rate' => 60.00,
                ]
            ],
            [
                'email' => 'jean.dubois@activibe.com',
                'profile' => [
                    'first_name' => 'Jean',
                    'last_name' => 'Dubois',
                    'phone' => '+32 476 234 567',
                    'date_of_birth' => '1978-07-22',
                    'address' => 'Chemin du Cross 18, 1410 Waterloo',
                ],
                'teacher' => [
                    'specialties' => ['cross-country', 'concours complet'],
                    'experience_years' => 18,
                    'certifications' => ['BEES 3', 'Juge niveau 2'],
                    'bio' => 'Ancien cavalier international, spécialiste du concours complet et cross-country.',
                    'hourly_rate' => 75.00,
                ]
            ],
            [
                'email' => 'marie.leroy@activibe.com',
                'profile' => [
                    'first_name' => 'Marie',
                    'last_name' => 'Leroy',
                    'phone' => '+32 477 345 678',
                    'date_of_birth' => '1990-11-08',
                    'address' => 'Rue Western 42, 3000 Leuven',
                ],
                'teacher' => [
                    'specialties' => ['équitation western', 'travail à pied'],
                    'experience_years' => 8,
                    'certifications' => ['Certificat Western', 'Éthologie équine'],
                    'bio' => 'Spécialiste de l\'équitation western et de l\'approche éthologique.',
                    'hourly_rate' => 55.00,
                ]
            ],
            [
                'email' => 'pierre.bernard@activibe.com',
                'profile' => [
                    'first_name' => 'Pierre',
                    'last_name' => 'Bernard',
                    'phone' => '+32 478 456 789',
                    'date_of_birth' => '1982-05-30',
                    'address' => 'Boulevard des Enfants 15, 1200 Woluwe',
                ],
                'teacher' => [
                    'specialties' => ['équitation enfants', 'poney club'],
                    'experience_years' => 15,
                    'certifications' => ['BEES 1', 'Animateur Poney'],
                    'bio' => 'Moniteur spécialisé dans l\'enseignement aux enfants et l\'animation poney.',
                    'hourly_rate' => 45.00,
                ]
            ]
        ];

        foreach ($teachers as $teacherData) {
            $user = User::create([
                'name' => $teacherData['profile']['first_name'] . ' ' . $teacherData['profile']['last_name'],
                'email' => $teacherData['email'],
                'password' => Hash::make('password123'),
                'role' => 'teacher',
                'email_verified_at' => now(),
            ]);

            $profile = Profile::create(array_merge(
                ['user_id' => $user->id],
                $teacherData['profile'],
                [
                    'emergency_contact_name' => 'Contact Urgence',
                    'emergency_contact_phone' => '+32 2 911 911 911',
                ]
            ));

            Teacher::create(array_merge(
                ['user_id' => $user->id],
                $teacherData['teacher']
            ));
        }

        // Élèves de démonstration
        $students = [
            [
                'email' => 'alice.durand@email.com',
                'profile' => [
                    'first_name' => 'Alice',
                    'last_name' => 'Durand',
                    'phone' => '+32 485 123 456',
                    'date_of_birth' => '1995-04-12',
                    'address' => 'Rue des Élèves 10, 1050 Ixelles',
                ],
                'student' => [
                    'level' => 'intermediaire',
                    'goals' => 'Perfectionnement en dressage',
                    'medical_info' => null,
                ]
            ],
            [
                'email' => 'bob.martin@email.com',
                'profile' => [
                    'first_name' => 'Bob',
                    'last_name' => 'Martin',
                    'phone' => '+32 486 234 567',
                    'date_of_birth' => '1988-09-25',
                    'address' => 'Avenue du Saut 33, 1000 Bruxelles',
                ],
                'student' => [
                    'level' => 'avance',
                    'goals' => 'Préparation concours obstacles',
                    'medical_info' => null,
                ]
            ],
            [
                'email' => 'charlotte.dupont@email.com',
                'profile' => [
                    'first_name' => 'Charlotte',
                    'last_name' => 'Dupont',
                    'phone' => '+32 487 345 678',
                    'date_of_birth' => '2010-06-18',
                    'address' => 'Chemin des Poneys 7, 1200 Woluwe',
                ],
                'student' => [
                    'level' => 'debutant',
                    'goals' => 'Découverte de l\'équitation',
                    'medical_info' => 'Aucune allergie connue',
                ]
            ],
            [
                'email' => 'david.laurent@email.com',
                'profile' => [
                    'first_name' => 'David',
                    'last_name' => 'Laurent',
                    'phone' => '+32 488 456 789',
                    'date_of_birth' => '1992-12-03',
                    'address' => 'Sentier Western 88, 3000 Leuven',
                ],
                'student' => [
                    'level' => 'debutant',
                    'goals' => 'Apprentissage équitation western',
                    'medical_info' => null,
                ]
            ],
            [
                'email' => 'emma.rousseau@email.com',
                'profile' => [
                    'first_name' => 'Emma',
                    'last_name' => 'Rousseau',
                    'phone' => '+32 489 567 890',
                    'date_of_birth' => '1998-08-14',
                    'address' => 'Route du Cross 55, 1410 Waterloo',
                ],
                'student' => [
                    'level' => 'intermediaire',
                    'goals' => 'Découverte du cross-country',
                    'medical_info' => null,
                ]
            ]
        ];

        foreach ($students as $studentData) {
            $user = User::create([
                'name' => $studentData['profile']['first_name'] . ' ' . $studentData['profile']['last_name'],
                'email' => $studentData['email'],
                'password' => Hash::make('password123'),
                'role' => 'student',
                'email_verified_at' => now(),
            ]);

            $profile = Profile::create(array_merge(
                ['user_id' => $user->id],
                $studentData['profile'],
                [
                    'emergency_contact_name' => 'Parent/Tuteur',
                    'emergency_contact_phone' => '+32 2 999 999 999',
                ]
            ));

            Student::create(array_merge(
                ['user_id' => $user->id],
                $studentData['student']
            ));
        }
    }
}
