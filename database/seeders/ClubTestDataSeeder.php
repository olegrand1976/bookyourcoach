<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Club;
use App\Models\Teacher;
use App\Models\Student;
use Illuminate\Support\Facades\Hash;

class ClubTestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🏇 Création des données de test pour le système de club...');

        // Créer un administrateur
        $admin = User::firstOrCreate(
            ['email' => 'admin@activibe.com'],
            [
                'name' => 'Administrateur',
                'password' => Hash::make('password'),
                'role' => User::ROLE_ADMIN,
                'phone' => '01 23 45 67 89',
                'status' => 'active',
                'is_active' => true,
                'email_verified_at' => now()
            ]
        );

        // Créer un utilisateur club
        $clubUser = User::firstOrCreate(
            ['email' => 'club@activibe.com'],
            [
                'name' => 'Gérant Club',
                'password' => Hash::make('password'),
                'role' => 'club',
                'phone' => '01 23 45 67 90',
                'status' => 'active',
                'is_active' => true,
                'email_verified_at' => now()
            ]
        );

        // Créer un club
        $club = Club::firstOrCreate(
            ['email' => 'contact@club-equestre.fr'],
            [
                'name' => 'Club Équestre de Test',
                'description' => 'Un club équestre pour les tests automatisés',
                'phone' => '01 23 45 67 91',
                'address' => '123 Rue de l\'Équitation',
                'city' => 'Paris',
                'postal_code' => '75001',
                'country' => 'France',
                'website' => 'https://club-equestre-test.fr',
                'facilities' => [
                    'Manège couvert',
                    'Carrière extérieure',
                    'Écuries',
                    'Club house',
                    'Parking'
                ],
                'disciplines' => [
                    'Dressage',
                    'Saut d\'obstacles',
                    'Cross',
                    'Équitation de loisir'
                ],
                'max_students' => 100,
                'subscription_price' => 150.00,
                'is_active' => true,
                'terms_and_conditions' => 'Conditions générales du club de test'
            ]
        );

        // Associer l'utilisateur club au club
        if (!$club->users()->where('user_id', $clubUser->id)->exists()) {
            $club->users()->attach($clubUser->id, [
                'role' => 'owner',
                'is_admin' => true,
                'joined_at' => now()
            ]);
        }

        // Créer des enseignants de test
        $teachers = [
            [
                'name' => 'Marie Dubois',
                'email' => 'marie.dubois@activibe.com',
                'phone' => '01 23 45 67 92',
                'specializations' => ['dressage', 'saut_obstacles'],
                'experience_years' => 10,
                'hourly_rate' => 60.00,
                'bio' => 'Enseignante expérimentée en dressage et saut d\'obstacles'
            ],
            [
                'name' => 'Pierre Martin',
                'email' => 'pierre.martin@activibe.com',
                'phone' => '01 23 45 67 93',
                'specializations' => ['cross', 'equitation_loisir'],
                'experience_years' => 8,
                'hourly_rate' => 55.00,
                'bio' => 'Spécialiste du cross et de l\'équitation de loisir'
            ],
            [
                'name' => 'Sophie Leroy',
                'email' => 'sophie.leroy@activibe.com',
                'phone' => '01 23 45 67 94',
                'specializations' => ['dressage'],
                'experience_years' => 15,
                'hourly_rate' => 70.00,
                'bio' => 'Maître de dressage avec 15 ans d\'expérience'
            ]
        ];

        foreach ($teachers as $teacherData) {
            $teacherUser = User::firstOrCreate(
                ['email' => $teacherData['email']],
                [
                    'name' => $teacherData['name'],
                    'password' => Hash::make('password'),
                    'role' => User::ROLE_TEACHER,
                    'phone' => $teacherData['phone'],
                    'status' => 'active',
                    'is_active' => true,
                    'email_verified_at' => now()
                ]
            );

            // Créer le profil enseignant
            Teacher::firstOrCreate(
                ['user_id' => $teacherUser->id],
                [
                    'club_id' => $club->id,
                    'specialties' => $teacherData['specializations'],
                    'experience_years' => $teacherData['experience_years'],
                    'hourly_rate' => $teacherData['hourly_rate'],
                    'bio' => $teacherData['bio'],
                    'is_available' => true
                ]
            );

            // Associer l'enseignant au club
            if (!$club->users()->where('user_id', $teacherUser->id)->exists()) {
                $club->users()->attach($teacherUser->id, [
                    'role' => 'teacher',
                    'is_admin' => false,
                    'joined_at' => now()
                ]);
            }
        }

        // Créer des étudiants de test
        $students = [
            [
                'name' => 'Alice Dupont',
                'email' => 'alice.dupont@activibe.com',
                'phone' => '01 23 45 67 95',
                'level' => 'intermediaire',
                'goals' => 'Améliorer mon dressage et participer à des compétitions',
                'medical_info' => 'Aucune allergie connue'
            ],
            [
                'name' => 'Bob Moreau',
                'email' => 'bob.moreau@activibe.com',
                'phone' => '01 23 45 67 96',
                'level' => 'debutant',
                'goals' => 'Apprendre les bases de l\'équitation',
                'medical_info' => 'Asthme léger'
            ],
            [
                'name' => 'Claire Bernard',
                'email' => 'claire.bernard@activibe.com',
                'phone' => '01 23 45 67 97',
                'level' => 'avance',
                'goals' => 'Préparation aux compétitions de saut d\'obstacles',
                'medical_info' => 'Aucune restriction'
            ],
            [
                'name' => 'David Petit',
                'email' => 'david.petit@activibe.com',
                'phone' => '01 23 45 67 98',
                'level' => 'intermediaire',
                'goals' => 'Équitation de loisir et randonnées',
                'medical_info' => 'Aucune allergie connue'
            ],
            [
                'name' => 'Emma Rousseau',
                'email' => 'emma.rousseau@activibe.com',
                'phone' => '01 23 45 67 99',
                'level' => 'debutant',
                'goals' => 'Découvrir l\'équitation et passer le galop 1',
                'medical_info' => 'Aucune restriction'
            ]
        ];

        foreach ($students as $studentData) {
            $studentUser = User::firstOrCreate(
                ['email' => $studentData['email']],
                [
                    'name' => $studentData['name'],
                    'password' => Hash::make('password'),
                    'role' => User::ROLE_STUDENT,
                    'phone' => $studentData['phone'],
                    'status' => 'active',
                    'is_active' => true,
                    'email_verified_at' => now()
                ]
            );

            // Créer le profil étudiant
            Student::firstOrCreate(
                ['user_id' => $studentUser->id],
                [
                    'club_id' => $club->id,
                    'level' => $studentData['level'],
                    'goals' => $studentData['goals'],
                    'medical_info' => $studentData['medical_info']
                ]
            );

            // Associer l'étudiant au club
            if (!$club->users()->where('user_id', $studentUser->id)->exists()) {
                $club->users()->attach($studentUser->id, [
                    'role' => 'student',
                    'is_admin' => false,
                    'joined_at' => now()
                ]);
            }
        }

        // Créer des clubs supplémentaires pour les tests
        $additionalClubs = [
            [
                'name' => 'Centre Équestre de la Vallée',
                'email' => 'contact@centre-vallee.fr',
                'city' => 'Lyon',
                'max_students' => 80,
                'subscription_price' => 120.00
            ],
            [
                'name' => 'Écuries du Soleil',
                'email' => 'info@ecuries-soleil.fr',
                'city' => 'Marseille',
                'max_students' => 60,
                'subscription_price' => 100.00
            ],
            [
                'name' => 'Club Hippique de la Forêt',
                'email' => 'contact@hippique-foret.fr',
                'city' => 'Bordeaux',
                'max_students' => 120,
                'subscription_price' => 180.00
            ]
        ];

        foreach ($additionalClubs as $clubData) {
            Club::firstOrCreate(
                ['email' => $clubData['email']],
                [
                    'name' => $clubData['name'],
                    'description' => 'Club équestre pour les tests',
                    'phone' => '01 23 45 67 00',
                    'address' => '123 Rue de l\'Équitation',
                    'city' => $clubData['city'],
                    'postal_code' => '75001',
                    'country' => 'France',
                    'website' => 'https://example.fr',
                    'facilities' => ['Manège', 'Carrière', 'Écuries'],
                    'disciplines' => ['Dressage', 'Saut d\'obstacles'],
                    'max_students' => $clubData['max_students'],
                    'subscription_price' => $clubData['subscription_price'],
                    'is_active' => true,
                    'terms_and_conditions' => 'Conditions générales'
                ]
            );
        }

        $this->command->info('✅ Données de test créées avec succès !');
        $this->command->info("📊 Résumé :");
        $this->command->info("- 1 administrateur");
        $this->command->info("- 1 utilisateur club");
        $this->command->info("- 4 clubs");
        $this->command->info("- 3 enseignants");
        $this->command->info("- 5 étudiants");
        $this->command->info("- Toutes les associations club-utilisateur");
    }
}
