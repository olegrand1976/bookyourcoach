<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class TeacherTestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌱 Création des données de test pour l\'enseignant...');

        // 1. Créer ou récupérer Sophie Martin (enseignante)
        $sophie = $this->createOrGetSophieMartin();
        $this->command->info("✅ Enseignante Sophie Martin créée/récupérée (ID: {$sophie->id})");

        // 2. Récupérer ou créer 2 clubs
        $club1 = $this->createClub('Club Équestre de la Vallée', 'Un club familial au cœur de la vallée');
        $club2 = $this->createClub('Centre Équestre des Étoiles', 'Centre moderne avec installations de pointe');
        $this->command->info("✅ Clubs récupérés/créés: {$club1->name} et {$club2->name}");

        // 3. Lier Sophie aux 2 clubs
        $teacherRecord = DB::table('teachers')->where('user_id', $sophie->id)->first();
        if ($teacherRecord) {
            $this->linkTeacherToClubs($teacherRecord->id, [$club1->id, $club2->id]);
            $this->command->info("✅ Sophie liée aux 2 clubs");
        } else {
            $this->command->error("❌ Profil enseignant non trouvé pour Sophie");
        }

        // 4. Créer des élèves pour chaque club  
        $club1Students = $this->createStudentsForClub($club1->id, 3);
        $club2Students = $this->createStudentsForClub($club2->id, 4);
        $allStudents = $club1Students->merge($club2Students);
        $this->command->info("✅ Élèves créés: {$club1Students->count()} pour {$club1->name}, {$club2Students->count()} pour {$club2->name}");

        // 5. Créer des cours pour Sophie avec les élèves
        $this->createLessonsForTeacher($teacherRecord->id, $allStudents, $club1->id, $club2->id);
        $this->command->info("✅ Cours créés pour Sophie");

        $this->command->info('🎉 Données de test créées avec succès !');
    }

    private function createOrGetSophieMartin()
    {
        // Vérifier si Sophie existe déjà
        $sophie = DB::table('users')->where('email', 'sophie.martin@activibe.com')->first();
        
        if (!$sophie) {
            $userId = DB::table('users')->insertGetId([
                'name' => 'Sophie Martin',
                'first_name' => 'Sophie',
                'last_name' => 'Martin',
                'email' => 'sophie.martin@activibe.com',
                'password' => Hash::make('password'),
                'role' => 'teacher',
                'phone' => '+32 478 123 456',
                'city' => 'Bruxelles',
                'country' => 'Belgique',
                'status' => 'active',
                'is_active' => true,
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            $sophie = DB::table('users')->where('id', $userId)->first();
        }

        // Créer le profil enseignant si nécessaire
        $teacherProfile = DB::table('teachers')->where('user_id', $sophie->id)->first();
        if (!$teacherProfile) {
            DB::table('teachers')->insert([
                'user_id' => $sophie->id,
                'specialties' => json_encode(['dressage', 'saut d\'obstacles']),
                'experience_years' => 12,
                'hourly_rate' => 60.00,
                'bio' => 'Instructrice passionnée spécialisée en dressage classique et saut d\'obstacles.',
                'certifications' => json_encode(['BEES 2', 'Galop 7 FFE']),
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        return $sophie;
    }

    private function createClub($name, $description)
    {
        // Vérifier si le club existe déjà
        $existingClub = DB::table('clubs')->where('name', $name)->first();
        
        if ($existingClub) {
            return $existingClub;
        }

        $clubId = DB::table('clubs')->insertGetId([
            'name' => $name,
            'description' => $description,
            'address' => '123 Rue des Chevaux, 1000 Bruxelles',
            'phone' => '+32 2 123 45 67',
            'email' => strtolower(str_replace(' ', '.', $name)) . '@activibe.com',
            'website' => 'https://' . strtolower(str_replace(' ', '', $name)) . '.be',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return DB::table('clubs')->where('id', $clubId)->first();
    }

    private function linkTeacherToClubs($teacherId, $clubIds)
    {
        foreach ($clubIds as $clubId) {
            // Vérifier si la liaison existe déjà
            $existing = DB::table('club_teachers')
                ->where('teacher_id', $teacherId)
                ->where('club_id', $clubId)
                ->first();

            if (!$existing) {
                DB::table('club_teachers')->insert([
                    'teacher_id' => $teacherId,
                    'club_id' => $clubId,
                    'contract_type' => 'freelance',
                    'is_active' => true,
                    'joined_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }
    }

    private function createStudentsForClub($clubId, $count)
    {
        $students = collect();
        $club = DB::table('clubs')->where('id', $clubId)->first();
        $clubName = $club->name;

        for ($i = 1; $i <= $count; $i++) {
            $firstName = $this->getRandomFirstName();
            $lastName = $this->getRandomLastName();
            $email = strtolower($firstName . '.' . $lastName . '@activibe.com');

            // Vérifier si l'élève existe déjà
            $existingUser = DB::table('users')->where('email', $email)->first();
            if ($existingUser) {
                // Récupérer l'ID de l'enregistrement student
                $existingStudent = DB::table('students')->where('user_id', $existingUser->id)->first();
                if ($existingStudent) {
                    $userData = (array) $existingUser;
                    $userData['student_id'] = $existingStudent->id;
                    $students->push((object) $userData);
                }
                continue;
            }

            $userId = DB::table('users')->insertGetId([
                'name' => $firstName . ' ' . $lastName,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'password' => Hash::make('password'),
                'role' => 'student',
                'phone' => '+32 4' . rand(10, 99) . ' ' . rand(100, 999) . ' ' . rand(100, 999),
                'city' => 'Bruxelles',
                'country' => 'Belgique',
                'status' => 'active',
                'is_active' => true,
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Créer le profil étudiant
            $studentId = DB::table('students')->insertGetId([
                'user_id' => $userId,
                'level' => $this->getRandomLevel(),
                'emergency_contacts' => json_encode([
                    'name' => 'Parent ' . $lastName,
                    'phone' => '+32 2 ' . rand(100, 999) . ' ' . rand(10, 99) . ' ' . rand(10, 99)
                ]),
                'medical_info' => 'Aucune allergie connue',
                'club_id' => $clubId,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            $user = DB::table('users')->where('id', $userId)->first();
            // Ajouter l'ID de l'enregistrement student comme propriété
            $userData = (array) $user;
            $userData['student_id'] = $studentId;
            $students->push((object) $userData);
        }

        return $students;
    }


    private function createLessonsForTeacher($teacherId, $students, $club1Id, $club2Id)
    {
        $courseTypeIds = [1, 2, 3, 4]; // IDs des course_types créés
        $locationIds = [1, 2, 3, 4]; // IDs des locations créées
        $lessonTitles = [
            'Cours de dressage',
            'Saut d\'obstacles',
            'Équitation de loisir',
            'Préparation compétition',
            'Cours débutant',
            'Perfectionnement',
            'Travail à pied',
            'Sortie en extérieur'
        ];

        // Créer des cours pour les 2 prochaines semaines
        $startDate = Carbon::now()->startOfWeek();
        
        for ($week = 0; $week < 2; $week++) {
            for ($day = 1; $day <= 5; $day++) { // Lundi à Vendredi
                $currentDate = $startDate->copy()->addWeeks($week)->addDays($day - 1);
                
                // 2-4 cours par jour
                $lessonsPerDay = rand(2, 4);
                
                for ($lesson = 0; $lesson < $lessonsPerDay; $lesson++) {
                    $hour = 9 + ($lesson * 2); // 9h, 11h, 13h, 15h
                    $startTime = $currentDate->copy()->setHour($hour)->setMinute(0);
                    $duration = [30, 45, 60][rand(0, 2)]; // 30, 45 ou 60 minutes
                    $endTime = $startTime->copy()->addMinutes($duration);
                    
                    // Vérifier les chevauchements
                    if ($this->hasTimeConflict($teacherId, $startTime, $endTime)) {
                        continue; // Passer ce créneau s'il y a conflit
                    }
                    
                    $student = $students->random();
                    $courseTypeId = $courseTypeIds[rand(0, count($courseTypeIds) - 1)];
                    $locationId = $locationIds[rand(0, count($locationIds) - 1)];
                    $title = $lessonTitles[rand(0, count($lessonTitles) - 1)];
                    
                    // Déterminer le club (ou calendrier personnel)
                    $clubId = null;
                    if (rand(1, 3) === 1) { // 1/3 de chance d'être un cours personnel
                        $clubId = null;
                    } else {
                        $clubId = rand(1, 2) === 1 ? $club1Id : $club2Id;
                    }
                    
                    // Créer le cours
                    $lessonId = DB::table('lessons')->insertGetId([
                        'teacher_id' => $teacherId,
                        'student_id' => $student->student_id, // Utiliser l'ID de l'enregistrement student
                        'course_type_id' => $courseTypeId,
                        'location_id' => $locationId,
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'status' => 'scheduled',
                        'notes' => 'Cours de ' . $title . ' avec ' . $student->name,
                        'price' => 45.00,
                        'payment_status' => 'pending',
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                    
                    // Lier l'étudiant au cours
                    DB::table('lesson_student')->insert([
                        'lesson_id' => $lessonId,
                        'student_id' => $student->student_id,
                        'status' => 'confirmed',
                        'price' => 45.00,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
            }
        }
    }

    private function hasTimeConflict($teacherId, $startTime, $endTime)
    {
        $conflicts = DB::table('lessons')
            ->where('teacher_id', $teacherId)
            ->where(function ($query) use ($startTime, $endTime) {
                $query->whereBetween('start_time', [$startTime, $endTime])
                      ->orWhereBetween('end_time', [$startTime, $endTime])
                      ->orWhere(function ($q) use ($startTime, $endTime) {
                          $q->where('start_time', '<=', $startTime)
                            ->where('end_time', '>=', $endTime);
                      });
            })
            ->count();

        return $conflicts > 0;
    }

    private function getRandomFirstName()
    {
        $firstNames = ['Emma', 'Lucas', 'Chloé', 'Nathan', 'Léa', 'Hugo', 'Manon', 'Ethan', 'Camille', 'Louis', 'Sarah', 'Gabriel', 'Inès', 'Raphaël', 'Zoé'];
        return $firstNames[array_rand($firstNames)];
    }

    private function getRandomLastName()
    {
        $lastNames = ['Dubois', 'Martin', 'Bernard', 'Thomas', 'Petit', 'Robert', 'Richard', 'Durand', 'Moreau', 'Simon', 'Laurent', 'Lefebvre', 'Michel', 'Garcia', 'David'];
        return $lastNames[array_rand($lastNames)];
    }

    private function getRandomLevel()
    {
        $levels = ['debutant', 'intermediaire', 'avance', 'expert'];
        return $levels[array_rand($levels)];
    }
}