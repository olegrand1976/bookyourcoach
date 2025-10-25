<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Club;
use App\Models\Teacher;
use App\Models\Contract;
use Illuminate\Support\Facades\Hash;

class GraphTestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Créer des clubs de test
        $clubs = [
            [
                'name' => 'Club Équestre de Bruxelles',
                'description' => 'Club d\'équitation situé au cœur de Bruxelles',
                'address' => 'Rue de l\'Équitation 123',
                'city' => 'Bruxelles',
                'postal_code' => '1000',
                'phone' => '+32 2 123 45 67',
                'email' => 'contact@club-bruxelles.be',
                'website' => 'https://club-bruxelles.be'
            ],
            [
                'name' => 'Centre Équestre de Liège',
                'description' => 'Centre moderne avec installations complètes',
                'address' => 'Chemin des Chevaux 456',
                'city' => 'Liège',
                'postal_code' => '4000',
                'phone' => '+32 4 234 56 78',
                'email' => 'info@centre-liege.be',
                'website' => 'https://centre-liege.be'
            ],
            [
                'name' => 'Écuries d\'Anvers',
                'description' => 'Écuries familiales depuis 1985',
                'address' => 'Avenue des Écuries 789',
                'city' => 'Anvers',
                'postal_code' => '2000',
                'phone' => '+32 3 345 67 89',
                'email' => 'contact@ecuries-anvers.be'
            ]
        ];

        $createdClubs = [];
        foreach ($clubs as $clubData) {
            $club = Club::create($clubData);
            $createdClubs[] = $club;
        }

        // Créer des utilisateurs de test
        $users = [
            [
                'name' => 'Admin Test',
                'first_name' => 'Admin',
                'last_name' => 'Test',
                'email' => 'admin@test.com',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'phone' => '+32 2 111 11 11',
                'city' => 'Bruxelles',
                'postal_code' => '1000'
            ],
            [
                'name' => 'Marie Dubois',
                'first_name' => 'Marie',
                'last_name' => 'Dubois',
                'email' => 'marie.dubois@test.com',
                'password' => Hash::make('password'),
                'role' => 'student',
                'phone' => '+32 2 222 22 22',
                'city' => 'Bruxelles',
                'postal_code' => '1000'
            ],
            [
                'name' => 'Jean Martin',
                'first_name' => 'Jean',
                'last_name' => 'Martin',
                'email' => 'jean.martin@test.com',
                'password' => Hash::make('password'),
                'role' => 'student',
                'phone' => '+32 4 333 33 33',
                'city' => 'Liège',
                'postal_code' => '4000'
            ],
            [
                'name' => 'Sophie Leroy',
                'first_name' => 'Sophie',
                'last_name' => 'Leroy',
                'email' => 'sophie.leroy@test.com',
                'password' => Hash::make('password'),
                'role' => 'student',
                'phone' => '+32 3 444 44 44',
                'city' => 'Anvers',
                'postal_code' => '2000'
            ]
        ];

        $createdUsers = [];
        foreach ($users as $userData) {
            $user = User::create($userData);
            $createdUsers[] = $user;
        }

        // Créer des enseignants
        $teachers = [
            [
                'user_id' => $createdUsers[0]->id, // Admin comme enseignant aussi
                'bio' => 'Enseignant expérimenté avec plus de 15 ans d\'expérience',
                'experience_years' => 15,
                'hourly_rate' => 50.00,
                'availability' => 'Lundi à Vendredi 9h-17h',
                'certifications' => 'Diplôme d\'instructeur FEI'
            ],
            [
                'user_id' => $createdUsers[1]->id, // Marie comme enseignante
                'bio' => 'Spécialiste en dressage et saut d\'obstacles',
                'experience_years' => 8,
                'hourly_rate' => 45.00,
                'availability' => 'Mardi, Jeudi, Samedi',
                'certifications' => 'Brevet d\'État, FEI Level 2'
            ]
        ];

        $createdTeachers = [];
        foreach ($teachers as $teacherData) {
            $teacher = Teacher::create($teacherData);
            $createdTeachers[] = $teacher;
        }

        // Créer des contrats
        $contracts = [
            [
                'teacher_id' => $createdTeachers[0]->id,
                'club_id' => $createdClubs[0]->id,
                'type' => 'CDI',
                'start_date' => '2024-01-01',
                'end_date' => null,
                'hours_per_week' => 40,
                'hourly_rate' => 50.00,
                'status' => 'active'
            ],
            [
                'teacher_id' => $createdTeachers[1]->id,
                'club_id' => $createdClubs[1]->id,
                'type' => 'CDD',
                'start_date' => '2024-06-01',
                'end_date' => '2024-12-31',
                'hours_per_week' => 20,
                'hourly_rate' => 45.00,
                'status' => 'active'
            ],
            [
                'teacher_id' => $createdTeachers[0]->id,
                'club_id' => $createdClubs[2]->id,
                'type' => 'Freelance',
                'start_date' => '2024-03-01',
                'end_date' => null,
                'hours_per_week' => 10,
                'hourly_rate' => 55.00,
                'status' => 'active'
            ]
        ];

        foreach ($contracts as $contractData) {
            Contract::create($contractData);
        }

        // Associer des utilisateurs aux clubs (adhésions)
        $createdUsers[1]->clubs()->attach($createdClubs[0]->id, ['created_at' => now()]);
        $createdUsers[2]->clubs()->attach($createdClubs[1]->id, ['created_at' => now()]);
        $createdUsers[3]->clubs()->attach($createdClubs[2]->id, ['created_at' => now()]);
        $createdUsers[1]->clubs()->attach($createdClubs[1]->id, ['created_at' => now()]); // Marie dans 2 clubs

        $this->command->info('Données de test pour la visualisation graphique créées avec succès !');
        $this->command->info('- ' . count($createdClubs) . ' clubs');
        $this->command->info('- ' . count($createdUsers) . ' utilisateurs');
        $this->command->info('- ' . count($createdTeachers) . ' enseignants');
        $this->command->info('- ' . count($contracts) . ' contrats');
    }
}
