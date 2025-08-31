<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Lesson;
use App\Models\CourseType;
use App\Models\Location;
use App\Models\Availability;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class TestDataSeeder extends Seeder
{
    public function run()
    {
        $this->command->info('Création des données de test...');

        // Créer des types de cours
        $courseTypes = [
            ['name' => 'Tennis', 'description' => 'Cours de tennis pour tous niveaux'],
            ['name' => 'Football', 'description' => 'Entraînement football'],
            ['name' => 'Basketball', 'description' => 'Cours de basketball'],
            ['name' => 'Natation', 'description' => 'Cours de natation'],
            ['name' => 'Yoga', 'description' => 'Cours de yoga'],
            ['name' => 'Fitness', 'description' => 'Entraînement fitness'],
            ['name' => 'Danse', 'description' => 'Cours de danse'],
            ['name' => 'Escalade', 'description' => 'Cours d\'escalade'],
        ];

        foreach ($courseTypes as $courseType) {
            CourseType::firstOrCreate(
                ['name' => $courseType['name']],
                $courseType
            );
        }

        // Créer des lieux
        $locations = [
            [
                'name' => 'Complexe Sportif Central', 
                'address' => '123 Rue du Sport, Paris', 
                'city' => 'Paris',
                'postal_code' => '75001',
                'country' => 'France',
            ],
            [
                'name' => 'Gymnase Municipal', 
                'address' => '456 Avenue des Athlètes, Lyon', 
                'city' => 'Lyon',
                'postal_code' => '69001',
                'country' => 'France',
            ],
            [
                'name' => 'Piscine Olympique', 
                'address' => '789 Boulevard de la Forme, Marseille', 
                'city' => 'Marseille',
                'postal_code' => '13001',
                'country' => 'France',
            ],
            [
                'name' => 'Tennis Club Premium', 
                'address' => '321 Chemin des Champions, Nice', 
                'city' => 'Nice',
                'postal_code' => '06000',
                'country' => 'France',
            ],
            [
                'name' => 'Centre de Fitness', 
                'address' => '654 Rue de la Santé, Bordeaux', 
                'city' => 'Bordeaux',
                'postal_code' => '33000',
                'country' => 'France',
            ],
        ];

        foreach ($locations as $location) {
            Location::firstOrCreate(
                ['name' => $location['name']],
                $location
            );
        }

        // Créer des utilisateurs de test supplémentaires
        $testUsers = [
            // Étudiants
            [
                'name' => 'Lucas Moreau',
                'email' => 'lucas.moreau@test.com',
                'password' => 'password123',
                'role' => 'student',
                'phone' => '0123456789',
                'status' => 'active',
            ],
            [
                'name' => 'Camille Petit',
                'email' => 'camille.petit@test.com',
                'password' => 'password123',
                'role' => 'student',
                'phone' => '0123456790',
                'status' => 'active',
            ],
            [
                'name' => 'Hugo Simon',
                'email' => 'hugo.simon@test.com',
                'password' => 'password123',
                'role' => 'student',
                'phone' => '0123456791',
                'status' => 'active',
            ],
            // Enseignants
            [
                'name' => 'Sarah Johnson',
                'email' => 'sarah.johnson@test.com',
                'password' => 'password123',
                'role' => 'teacher',
                'phone' => '0123456792',
                'status' => 'active',
            ],
            [
                'name' => 'Michael Brown',
                'email' => 'michael.brown@test.com',
                'password' => 'password123',
                'role' => 'teacher',
                'phone' => '0123456793',
                'status' => 'active',
            ],
            [
                'name' => 'Lisa Davis',
                'email' => 'lisa.davis@test.com',
                'password' => 'password123',
                'role' => 'teacher',
                'phone' => '0123456794',
                'status' => 'active',
            ],
        ];

        foreach ($testUsers as $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => Hash::make($userData['password']),
                    'role' => $userData['role'],
                    'phone' => $userData['phone'],
                    'status' => $userData['status'],
                    'is_active' => true,
                ]
            );

            // Créer l'enregistrement correspondant dans students ou teachers
            if ($userData['role'] === 'student') {
                Student::firstOrCreate(
                    ['user_id' => $user->id],
                    [
                        'level' => ['debutant', 'intermediaire', 'avance', 'expert'][array_rand(['debutant', 'intermediaire', 'avance', 'expert'])],
                        'goals' => 'Améliorer mes compétences sportives',
                        'total_lessons' => 0,
                        'total_spent' => 0,
                    ]
                );
            } elseif ($userData['role'] === 'teacher') {
                Teacher::firstOrCreate(
                    ['user_id' => $user->id],
                    [
                        'experience_years' => rand(2, 15),
                        'hourly_rate' => rand(30, 100),
                        'bio' => "Enseignant expérimenté en {$userData['name']}",
                        'is_available' => true,
                        'max_travel_distance' => rand(10, 50),
                        'rating' => rand(35, 50) / 10,
                        'total_lessons' => 0,
                    ]
                );
            }
        }

        // Créer des leçons de test
        $teachers = Teacher::with('user')->get();
        $students = Student::with('user')->get();
        $courseTypes = CourseType::all();
        $locations = Location::all();

        foreach ($teachers as $teacher) {
            // Créer 3-5 leçons par enseignant
            $numLessons = rand(3, 5);
            for ($i = 0; $i < $numLessons; $i++) {
                $courseType = $courseTypes->random();
                $location = $locations->random();
                $student = $students->random();
                
                $startTime = Carbon::now()->addDays(rand(1, 30))->setHour(rand(9, 17))->setMinute(0);
                $endTime = $startTime->copy()->addMinutes(rand(60, 120));
                
                Lesson::firstOrCreate(
                    [
                        'teacher_id' => $teacher->id,
                        'course_type_id' => $courseType->id,
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                    ],
                    [
                        'student_id' => $student->id,
                        'location_id' => $location->id,
                        'price' => rand(20, 80),
                        'status' => 'confirmed',
                        'notes' => "Cours de {$courseType->name} avec {$teacher->user->name}",
                        'payment_status' => 'paid',
                    ]
                );
            }
        }

        // Créer des disponibilités pour les enseignants
        $locations = Location::all();
        
        foreach ($teachers as $teacher) {
            // Créer 2-4 disponibilités par enseignant
            $numAvailabilities = rand(2, 4);
            for ($i = 0; $i < $numAvailabilities; $i++) {
                $location = $locations->random();
                $startHour = rand(8, 18);
                $endHour = $startHour + rand(1, 3);
                
                $startTime = Carbon::now()->addDays(rand(1, 30))->setHour($startHour)->setMinute(0);
                $endTime = $startTime->copy()->setHour($endHour);
                
                Availability::firstOrCreate(
                    [
                        'teacher_id' => $teacher->id,
                        'location_id' => $location->id,
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                    ],
                    [
                        'notes' => "Disponible de {$startHour}h à {$endHour}h",
                        'is_available' => true,
                    ]
                );
            }
        }



        $this->command->info('✅ Données de test créées avec succès !');
        $this->command->info('');
        $this->command->info('📋 Comptes de test disponibles :');
        $this->command->info('');
        $this->command->info('👨‍💼 Administrateur :');
        $this->command->info('   Email: admin@bookyourcoach.com');
        $this->command->info('   Mot de passe: password123');
        $this->command->info('');
        $this->command->info('👨‍🏫 Enseignants :');
        $this->command->info('   • sophie.martin@bookyourcoach.com');
        $this->command->info('   • sarah.johnson@test.com');
        $this->command->info('   • michael.brown@test.com');
        $this->command->info('   • lisa.davis@test.com');
        $this->command->info('   Mot de passe: password123');
        $this->command->info('');
        $this->command->info('👨‍🎓 Étudiants :');
        $this->command->info('   • alice.durand@email.com');
        $this->command->info('   • lucas.moreau@test.com');
        $this->command->info('   • camille.petit@test.com');
        $this->command->info('   • hugo.simon@test.com');
        $this->command->info('   Mot de passe: password123');
        $this->command->info('');
        $this->command->info('📊 Données créées :');
        $this->command->info('   • ' . CourseType::count() . ' types de cours');
        $this->command->info('   • ' . Location::count() . ' lieux');
        $this->command->info('   • ' . Lesson::count() . ' leçons');
        $this->command->info('   • ' . Availability::count() . ' disponibilités');
    }
}
