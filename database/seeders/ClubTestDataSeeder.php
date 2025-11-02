<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class ClubTestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🏇 Création des données de test pour les clubs...');

        // 1. Créer des clubs réalistes
        $clubs = $this->createClubs();
        $this->command->info("✅ {$clubs->count()} clubs créés");

        // 2. Créer des gestionnaires de clubs
        $managers = $this->createClubManagers($clubs);
        $this->command->info("✅ {$managers->count()} gestionnaires de clubs créés");

        // 3. Créer des enseignants et les lier aux clubs
        $teachers = $this->createTeachersForClubs($clubs);
        $this->command->info("✅ {$teachers->count()} enseignants créés et liés aux clubs");

        // 4. Créer des étudiants et les lier aux clubs
        $students = $this->createStudentsForClubs($clubs);
        $this->command->info("✅ {$students->count()} étudiants créés et liés aux clubs");

        // 5. Créer des cours pour tester le dashboard
        $this->createLessonsForClubs($clubs, $teachers, $students);
        $this->command->info("✅ Cours créés pour les clubs");

        $this->command->info('🎉 Données de test des clubs créées avec succès !');
    }

    private function createClubs()
    {
        $clubsData = [
            [
                'name' => 'Club Équestre de la Vallée Dorée',
                'description' => 'Club familial situé dans un cadre verdoyant, idéal pour débuter l\'équitation. Nous proposons des cours pour tous niveaux dans une atmosphère conviviale.',
                'email' => 'contact@vallee-doree.fr',
                'phone' => '+33 1 23 45 67 89',
                'street' => 'Route de la Forêt',
                'street_number' => '15',
                'city' => 'Fontainebleau',
                'postal_code' => '77300',
                'country' => 'France',
                'website' => 'https://www.vallee-doree.fr',
                'facilities' => json_encode(['manège couvert', 'carrière extérieure', 'paddocks', 'club house']),
                'disciplines' => json_encode(['dressage', 'saut d\'obstacles', 'équitation de loisir']),
                'max_students' => 80,
                'subscription_price' => 45.00,
                'is_active' => true
            ],
            [
                'name' => 'Centre Équestre des Étoiles',
                'description' => 'Centre moderne avec installations de pointe. Spécialisé dans la compétition et la formation de cavaliers de haut niveau.',
                'email' => 'info@etoiles-equestres.fr',
                'phone' => '+33 1 98 76 54 32',
                'street' => 'Avenue des Champions',
                'street_number' => '42',
                'city' => 'Chantilly',
                'postal_code' => '60500',
                'country' => 'France',
                'website' => 'https://www.etoiles-equestres.fr',
                'facilities' => json_encode(['manège olympique', 'carrière de dressage', 'parcours cross', 'écuries modernes', 'salle de cours']),
                'disciplines' => json_encode(['dressage', 'saut d\'obstacles', 'concours complet', 'voltige']),
                'max_students' => 120,
                'subscription_price' => 65.00,
                'is_active' => true
            ],
            [
                'name' => 'Poney Club des Petits Cavaliers',
                'description' => 'Club spécialisé dans l\'initiation des enfants à l\'équitation. Poneys adaptés et moniteurs expérimentés pour un apprentissage en toute sécurité.',
                'email' => 'bonjour@petits-cavaliers.fr',
                'phone' => '+33 2 34 56 78 90',
                'street' => 'Chemin des Poneys',
                'street_number' => '8',
                'city' => 'Rouen',
                'postal_code' => '76000',
                'country' => 'France',
                'website' => 'https://www.petits-cavaliers.fr',
                'facilities' => json_encode(['manège couvert', 'paddocks poneys', 'aire de jeux', 'salle d\'activités']),
                'disciplines' => json_encode(['équitation de loisir', 'jeux équestres', 'balades']),
                'max_students' => 60,
                'subscription_price' => 35.00,
                'is_active' => true
            ],
            [
                'name' => 'Haras de la Côte d\'Azur',
                'description' => 'Haras prestigieux sur la Côte d\'Azur, combinant tradition et modernité. Cours particuliers et stages de perfectionnement.',
                'email' => 'contact@haras-azur.fr',
                'phone' => '+33 4 93 12 34 56',
                'street' => 'Route des Collines',
                'street_number' => '25',
                'city' => 'Nice',
                'postal_code' => '06000',
                'country' => 'France',
                'website' => 'https://www.haras-azur.fr',
                'facilities' => json_encode(['manège couvert', 'carrière dressage', 'piste de galop', 'écuries de luxe', 'spa équin']),
                'disciplines' => json_encode(['dressage', 'saut d\'obstacles', 'équitation western', 'endurance']),
                'max_students' => 50,
                'subscription_price' => 85.00,
                'is_active' => true
            ]
        ];

        $clubs = collect();
        foreach ($clubsData as $clubData) {
            // Vérifier si le club existe déjà
            $existingClub = DB::table('clubs')->where('email', $clubData['email'])->first();
            
            if ($existingClub) {
                $clubs->push($existingClub);
                continue;
            }

            $clubData['created_at'] = now();
            $clubData['updated_at'] = now();
            
            $clubId = DB::table('clubs')->insertGetId($clubData);
            $club = DB::table('clubs')->where('id', $clubId)->first();
            $clubs->push($club);
        }

        return $clubs;
    }

    private function createClubManagers($clubs)
    {
        $managers = collect();
        
        foreach ($clubs as $club) {
            $managerData = [
                'name' => 'Gestionnaire ' . $club->name,
                'first_name' => 'Gestionnaire',
                'last_name' => explode(' ', $club->name)[0],
                'email' => 'manager@' . strtolower(str_replace([' ', 'é', 'è', 'ê', 'ë', 'à', 'â', 'ä', 'ç', 'ù', 'û', 'ü', 'ô', 'ö', 'î', 'ï'], ['-', 'e', 'e', 'e', 'e', 'a', 'a', 'a', 'c', 'u', 'u', 'u', 'o', 'o', 'i', 'i'], $club->name)) . '.fr',
                'password' => Hash::make('password'),
                'role' => 'club',
                'phone' => $club->phone,
                'city' => $club->city,
                'country' => $club->country,
                'status' => 'active',
                'is_active' => true,
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now()
            ];

            // Vérifier si le gestionnaire existe déjà
            $existingManager = DB::table('users')->where('email', $managerData['email'])->first();
            
            if ($existingManager) {
                $userId = $existingManager->id;
                $managers->push($existingManager);
            } else {
                $userId = DB::table('users')->insertGetId($managerData);
                $manager = DB::table('users')->where('id', $userId)->first();
                $managers->push($manager);
            }

            // Lier le gestionnaire au club via club_user (même s'il existe déjà, on s'assure de la liaison)
            DB::table('club_user')->updateOrInsert(
                ['club_id' => $club->id, 'user_id' => $userId],
                [
                    'role' => 'manager',
                    'is_admin' => true,
                    'joined_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            );
        }

        return $managers;
    }

    private function createTeachersForClubs($clubs)
    {
        $teachers = collect();
        $teacherNames = [
            ['Sophie', 'Martin', 'dressage', 'saut d\'obstacles'],
            ['Pierre', 'Dubois', 'concours complet', 'cross'],
            ['Marie', 'Leroy', 'dressage', 'équitation de loisir'],
            ['Jean', 'Moreau', 'saut d\'obstacles', 'voltige'],
            ['Claire', 'Petit', 'équitation western', 'endurance'],
            ['Antoine', 'Rousseau', 'dressage', 'concours complet'],
            ['Camille', 'Simon', 'saut d\'obstacles', 'équitation de loisir'],
            ['Thomas', 'Bernard', 'cross', 'endurance']
        ];

        $teacherIndex = 0;
        foreach ($clubs as $club) {
            // 2-3 enseignants par club
            $teachersPerClub = rand(2, 3);
            
            for ($i = 0; $i < $teachersPerClub && $teacherIndex < count($teacherNames); $i++) {
                $teacherName = $teacherNames[$teacherIndex];
                $teacherIndex++;

                $teacherData = [
                    'name' => $teacherName[0] . ' ' . $teacherName[1],
                    'first_name' => $teacherName[0],
                    'last_name' => $teacherName[1],
                    'email' => strtolower($teacherName[0] . '.' . $teacherName[1] . '@' . strtolower(str_replace([' ', 'é', 'è', 'ê', 'ë', 'à', 'â', 'ä', 'ç', 'ù', 'û', 'ü', 'ô', 'ö', 'î', 'ï'], ['-', 'e', 'e', 'e', 'e', 'a', 'a', 'a', 'c', 'u', 'u', 'u', 'o', 'o', 'i', 'i'], $club->name)) . '.fr'),
                    'password' => Hash::make('password'),
                    'role' => 'teacher',
                    'phone' => '+33 ' . rand(1, 9) . ' ' . rand(10, 99) . ' ' . rand(10, 99) . ' ' . rand(10, 99) . ' ' . rand(10, 99),
                    'city' => $club->city,
                    'country' => $club->country,
                    'status' => 'active',
                    'is_active' => true,
                    'email_verified_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now()
                ];

                // Vérifier si l'enseignant existe déjà
                $existingTeacher = DB::table('users')->where('email', $teacherData['email'])->first();
                
                if ($existingTeacher) {
                    $teachers->push($existingTeacher);
                    continue;
                }

                $userId = DB::table('users')->insertGetId($teacherData);
                $teacher = DB::table('users')->where('id', $userId)->first();
                $teachers->push($teacher);

            // Créer le profil enseignant
                $teacherProfileId = DB::table('teachers')->insertGetId([
                    'user_id' => $userId,
                    'club_id' => $club->id,
                    'specialties' => json_encode($teacherName[2] ? [$teacherName[2], $teacherName[3]] : ['équitation de loisir']),
                    'experience_years' => rand(5, 20),
                    'hourly_rate' => rand(40, 80),
                    'bio' => 'Enseignant expérimenté spécialisé en ' . implode(' et ', array_slice($teacherName, 2)),
                    'certifications' => json_encode(['BEES 1', 'Galop 7 FFE']),
                    'is_available' => true,
                    'rating' => rand(40, 50) / 10, // 4.0 à 5.0
                    'total_lessons' => rand(100, 500),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                // Lier l'enseignant au club
                DB::table('club_teachers')->insert([
                    'club_id' => $club->id,
                    'teacher_id' => $teacherProfileId,
                    'allowed_disciplines' => json_encode($club->disciplines ? json_decode($club->disciplines) : ['équitation de loisir']),
                    'hourly_rate' => rand(40, 80),
                    'is_active' => true,
                    'joined_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }

        return $teachers;
    }

    private function createStudentsForClubs($clubs)
    {
        $students = collect();
        $firstNames = ['Emma', 'Lucas', 'Chloé', 'Nathan', 'Léa', 'Hugo', 'Manon', 'Ethan', 'Camille', 'Louis', 'Sarah', 'Gabriel', 'Inès', 'Raphaël', 'Zoé', 'Maxime', 'Lola', 'Alexandre', 'Juliette', 'Théo'];
        $lastNames = ['Dubois', 'Martin', 'Bernard', 'Thomas', 'Petit', 'Robert', 'Richard', 'Durand', 'Moreau', 'Simon', 'Laurent', 'Lefebvre', 'Michel', 'Garcia', 'David', 'Rousseau', 'Leroy', 'Morel', 'Girard', 'André'];

        foreach ($clubs as $club) {
            // 8-15 étudiants par club
            $studentsPerClub = rand(8, 15);
            
            for ($i = 0; $i < $studentsPerClub; $i++) {
                $firstName = $firstNames[array_rand($firstNames)];
                $lastName = $lastNames[array_rand($lastNames)];
                $email = strtolower($firstName . '.' . $lastName . rand(1, 99) . '@' . strtolower(str_replace([' ', 'é', 'è', 'ê', 'ë', 'à', 'â', 'ä', 'ç', 'ù', 'û', 'ü', 'ô', 'ö', 'î', 'ï'], ['-', 'e', 'e', 'e', 'e', 'a', 'a', 'a', 'c', 'u', 'u', 'u', 'o', 'o', 'i', 'i'], $club->name)) . '.fr');

                // Vérifier si l'étudiant existe déjà
                $existingStudent = DB::table('users')->where('email', $email)->first();
                
                if ($existingStudent) {
                    $students->push($existingStudent);
                    continue;
                }

                $studentData = [
                    'name' => $firstName . ' ' . $lastName,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $email,
                    'password' => Hash::make('password'),
                    'role' => 'student',
                    'phone' => '+33 ' . rand(1, 9) . ' ' . rand(10, 99) . ' ' . rand(10, 99) . ' ' . rand(10, 99) . ' ' . rand(10, 99),
                    'city' => $club->city,
                    'country' => $club->country,
                    'status' => 'active',
                    'is_active' => true,
                    'email_verified_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now()
                ];

                $userId = DB::table('users')->insertGetId($studentData);
                $student = DB::table('users')->where('id', $userId)->first();
                $students->push($student);

            // Créer le profil étudiant
                $studentProfileId = DB::table('students')->insertGetId([
                    'user_id' => $userId,
                    'club_id' => $club->id,
                    'level' => ['debutant', 'intermediaire', 'avance', 'expert'][rand(0, 3)],
                    'goals' => 'Progresser en équitation et participer aux compétitions',
                    'medical_info' => 'Aucune allergie connue',
                    'emergency_contacts' => json_encode([
                        'name' => 'Parent ' . $lastName,
                        'phone' => '+33 ' . rand(1, 9) . ' ' . rand(10, 99) . ' ' . rand(10, 99) . ' ' . rand(10, 99) . ' ' . rand(10, 99)
                    ]),
                    'total_lessons' => rand(0, 50),
                    'total_spent' => rand(0, 2000),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                // Lier l'étudiant au club
                DB::table('club_students')->insert([
                    'club_id' => $club->id,
                    'student_id' => $studentProfileId,
                    'level' => ['debutant', 'intermediaire', 'avance', 'expert'][rand(0, 3)],
                    'goals' => 'Progresser en équitation',
                    'medical_info' => 'Aucune allergie connue',
                    'preferred_disciplines' => json_encode(['équitation de loisir']),
                    'is_active' => true,
                    'joined_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }

        return $students;
    }

    private function createLessonsForClubs($clubs, $teachers, $students)
    {
        // Récupérer les IDs des enseignants et étudiants
        $teacherIds = DB::table('teachers')->pluck('id')->toArray();
        $studentIds = DB::table('students')->pluck('id')->toArray();
        
        if (empty($teacherIds) || empty($studentIds)) {
            $this->command->warn('⚠️ Aucun enseignant ou étudiant trouvé pour créer des cours');
            return;
        }

        // Récupérer les IDs réels des course_types existants
        $courseTypes = DB::table('course_types')->pluck('id')->toArray();
        if (empty($courseTypes)) {
            $this->command->warn('⚠️ Aucun course_type trouvé, création de cours ignorée');
            return;
        }
        $lessonTitles = [
            'Cours de dressage',
            'Saut d\'obstacles',
            'Équitation de loisir',
            'Préparation compétition',
            'Cours débutant',
            'Perfectionnement',
            'Travail à pied',
            'Sortie en extérieur',
            'Cours particulier',
            'Cours collectif'
        ];

        // Créer des cours pour les 4 prochaines semaines
        $startDate = Carbon::now()->startOfWeek();
        $lessonsCreated = 0;
        
        for ($week = 0; $week < 4; $week++) {
            for ($day = 1; $day <= 6; $day++) { // Lundi à Samedi
                $currentDate = $startDate->copy()->addWeeks($week)->addDays($day - 1);
                
                // 3-6 cours par jour
                $lessonsPerDay = rand(3, 6);
                
                for ($lesson = 0; $lesson < $lessonsPerDay; $lesson++) {
                    $hour = 9 + ($lesson * 2); // 9h, 11h, 13h, 15h, 17h, 19h
                    if ($hour > 19) continue; // Pas de cours après 19h
                    
                    $startTime = $currentDate->copy()->setHour($hour)->setMinute(0);
                    $duration = [30, 45, 60][rand(0, 2)]; // 30, 45 ou 60 minutes
                    $endTime = $startTime->copy()->addMinutes($duration);
                    
                    $teacherId = $teacherIds[array_rand($teacherIds)];
                    $studentId = $studentIds[array_rand($studentIds)];
                    $courseTypeId = $courseTypes[array_rand($courseTypes)];
                    $title = $lessonTitles[array_rand($lessonTitles)];
                    
                    // Déterminer le club (ou cours personnel)
                    $clubId = null;
                    if (rand(1, 4) !== 1) { // 3/4 de chance d'être un cours de club
                        $club = $clubs->random();
                        $clubId = $club->id;
                    }
                    
                    // Créer le cours
                    $lessonData = [
                        'teacher_id' => $teacherId,
                        'student_id' => $studentId,
                        'course_type_id' => $courseTypeId,
                        'location_id' => 1, // Location par défaut
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'status' => ['pending', 'confirmed', 'completed'][rand(0, 2)],
                        'notes' => 'Cours de ' . $title,
                        'price' => rand(35, 80),
                        'payment_status' => ['pending', 'paid', 'failed'][rand(0, 2)],
                        'created_at' => now(),
                        'updated_at' => now()
                    ];
                    
                    // Ajouter club_id si la colonne existe
                    if (Schema::hasColumn('lessons', 'club_id')) {
                        $lessonData['club_id'] = $clubId;
                    }
                    
                    $lessonId = DB::table('lessons')->insertGetId($lessonData);
                    
                    // Lier l'étudiant au cours
                    DB::table('lesson_student')->insert([
                        'lesson_id' => $lessonId,
                        'student_id' => $studentId,
                        'status' => 'confirmed',
                        'price' => rand(35, 80),
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                    
                    $lessonsCreated++;
                }
            }
        }

        $this->command->info("✅ {$lessonsCreated} cours créés");
    }
}