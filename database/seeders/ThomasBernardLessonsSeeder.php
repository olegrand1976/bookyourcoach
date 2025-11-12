<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class ThomasBernardLessonsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🏇 Création des cours de test pour Thomas Bernard...');

        // 1. Récupérer Thomas Bernard
        $thomas = DB::table('users')
            ->where('email', 'thomas.bernard@poney-club-des-petits-cavaliers.fr')
            ->first();

        if (!$thomas) {
            $this->command->error('❌ Thomas Bernard non trouvé');
            return;
        }

        $this->command->info("✅ Thomas Bernard trouvé (ID: {$thomas->id})");

        // 2. Récupérer son profil enseignant
        $teacher = DB::table('teachers')->where('user_id', $thomas->id)->first();

        if (!$teacher) {
            $this->command->error('❌ Profil enseignant non trouvé pour Thomas Bernard');
            return;
        }

        $this->command->info("✅ Profil enseignant trouvé (ID: {$teacher->id})");

        // 3. Récupérer son club
        $clubId = $teacher->club_id;
        $club = DB::table('clubs')->where('id', $clubId)->first();

        if (!$club) {
            $this->command->error('❌ Club non trouvé');
            return;
        }

        $this->command->info("✅ Club trouvé: {$club->name} (ID: {$clubId})");

        // 4. Vérifier les cours existants
        $existingLessons = DB::table('lessons')
            ->where('teacher_id', $teacher->id)
            ->count();

        $this->command->info("ℹ️  {$existingLessons} cours existants pour Thomas Bernard");

        // Si plus de 20 cours existent, ne pas créer de nouveaux cours
        if ($existingLessons >= 20) {
            $this->command->info("✅ Thomas Bernard a déjà assez de cours assignés ({$existingLessons} cours)");
            return;
        }

        // Si des cours existent mais moins de 20, on en crée quand même pour avoir au moins 20 cours au total
        if ($existingLessons > 0 && $existingLessons < 20) {
            $this->command->info("ℹ️  {$existingLessons} cours existent, création de cours supplémentaires pour atteindre au moins 20 cours...");
        }

        // 5. Récupérer ou créer des étudiants pour le club
        $students = $this->getOrCreateStudentsForClub($clubId);
        $this->command->info("✅ {$students->count()} étudiants disponibles pour le club");

        // 6. Récupérer les types de cours disponibles
        $courseTypes = $this->getCourseTypesForClub($clubId);
        if ($courseTypes->isEmpty()) {
            $this->command->warn('⚠️  Aucun type de cours trouvé, création de types par défaut');
            $courseTypes = $this->createDefaultCourseTypes();
        }
        $this->command->info("✅ {$courseTypes->count()} types de cours disponibles");

        // 7. Récupérer ou créer des locations
        $locations = $this->getOrCreateLocations($clubId);
        $this->command->info("✅ {$locations->count()} locations disponibles");

        // 8. Créer des cours pour les prochaines semaines
        $lessonsCreated = $this->createLessonsForTeacher(
            $teacher->id,
            $students,
            $courseTypes,
            $locations,
            $clubId
        );

        $this->command->info("✅ {$lessonsCreated} cours créés pour Thomas Bernard");
        $this->command->info('🎉 Cours de test créés avec succès !');
    }

    private function getOrCreateStudentsForClub($clubId)
    {
        // Récupérer les étudiants existants du club
        $existingStudents = DB::table('students')
            ->join('users', 'students.user_id', '=', 'users.id')
            ->where('students.club_id', $clubId)
            ->select('students.id as student_id', 'users.id as user_id', 'users.name')
            ->get();

        if ($existingStudents->isNotEmpty()) {
            return $existingStudents;
        }

        // Créer 3 étudiants de test
        $students = collect();
        $firstNames = ['Emma', 'Lucas', 'Chloé'];
        $lastNames = ['Dupont', 'Martin', 'Bernard'];

        for ($i = 0; $i < 3; $i++) {
            $firstName = $firstNames[$i];
            $lastName = $lastNames[$i];
            $email = strtolower($firstName . '.' . $lastName . '@activibe.com');

            $userId = DB::table('users')->insertGetId([
                'name' => $firstName . ' ' . $lastName,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'password' => Hash::make('password'),
                'role' => 'student',
                'phone' => '+33 6 ' . rand(10, 99) . ' ' . rand(10, 99) . ' ' . rand(10, 99) . ' ' . rand(10, 99),
                'city' => 'Rouen',
                'country' => 'France',
                'status' => 'active',
                'is_active' => true,
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            $studentId = DB::table('students')->insertGetId([
                'user_id' => $userId,
                'club_id' => $clubId,
                'level' => ['debutant', 'intermediaire', 'avance'][$i],
                'emergency_contacts' => json_encode([
                    'name' => 'Parent ' . $lastName,
                    'phone' => '+33 2 ' . rand(30, 39) . ' ' . rand(10, 99) . ' ' . rand(10, 99) . ' ' . rand(10, 99)
                ]),
                'medical_info' => 'Aucune allergie connue',
                'created_at' => now(),
                'updated_at' => now()
            ]);

            $students->push((object) [
                'student_id' => $studentId,
                'user_id' => $userId,
                'name' => $firstName . ' ' . $lastName
            ]);
        }

        return $students;
    }

    private function getCourseTypesForClub($clubId)
    {
        // Récupérer les disciplines du club
        $club = DB::table('clubs')->where('id', $clubId)->first();
        $disciplines = json_decode($club->disciplines ?? '[]', true);

        if (empty($disciplines)) {
            return collect();
        }

        // Pour simplifier, récupérer tous les course_types disponibles
        // Dans un vrai cas, on devrait filtrer par discipline
        return DB::table('course_types')
            ->select('id', 'name', 'duration', 'price')
            ->limit(5)
            ->get();
    }

    private function createDefaultCourseTypes()
    {
        $courseTypes = collect();
        
        $types = [
            ['name' => 'Cours individuel', 'duration' => 30, 'price' => 35.00],
            ['name' => 'Cours collectif', 'duration' => 60, 'price' => 25.00],
            ['name' => 'Initiation poney', 'duration' => 30, 'price' => 30.00]
        ];

        foreach ($types as $typeData) {
            $courseTypeId = DB::table('course_types')->insertGetId([
                'name' => $typeData['name'],
                'duration' => $typeData['duration'],
                'price' => $typeData['price'],
                'description' => $typeData['name'],
                'max_participants' => $typeData['name'] === 'Cours collectif' ? 8 : 1,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            $courseTypes->push((object) [
                'id' => $courseTypeId,
                'name' => $typeData['name'],
                'duration' => $typeData['duration'],
                'price' => $typeData['price']
            ]);
        }

        return $courseTypes;
    }

    private function getOrCreateLocations($clubId)
    {
        // Récupérer les locations existantes (sans filtre club_id car la colonne n'existe peut-être pas)
        $existing = DB::table('locations')
            ->select('id', 'name')
            ->limit(5)
            ->get();

        if ($existing->isNotEmpty()) {
            return $existing;
        }

        // Créer une location par défaut
        $club = DB::table('clubs')->where('id', $clubId)->first();
        
        $locationData = [
            'name' => 'Manège principal - ' . $club->name,
            'address' => ($club->street ?? '') . ' ' . ($club->street_number ?? ''),
            'city' => $club->city ?? 'Rouen',
            'postal_code' => $club->postal_code ?? '76000',
            'country' => $club->country ?? 'France',
            'created_at' => now(),
            'updated_at' => now()
        ];

        $locationId = DB::table('locations')->insertGetId($locationData);

        return collect([
            (object) ['id' => $locationId, 'name' => 'Manège principal']
        ]);
    }

    private function createLessonsForTeacher($teacherId, $students, $courseTypes, $locations, $clubId)
    {
        $lessonsCreated = 0;
        $startDate = Carbon::now()->startOfWeek()->addDay(); // Commencer à partir de demain (mardi)
        
        // Vérifier combien de cours existent déjà
        $existingCount = DB::table('lessons')->where('teacher_id', $teacherId)->count();
        $targetLessons = max(15, 20 - $existingCount); // Au moins 15-20 cours au total
        
        // Créer des cours pour les 4 prochaines semaines pour avoir plus de visibilité
        for ($week = 0; $week < 4; $week++) {
            // Lundi à Samedi
            for ($day = 1; $day <= 6; $day++) {
                $currentDate = $startDate->copy()->addWeeks($week)->addDays($day - 1);
                
                // Ignorer si la date est dans le passé
                if ($currentDate->isPast() && !$currentDate->isToday()) {
                    continue;
                }

                // 2-4 cours par jour
                $lessonsPerDay = rand(2, 4);
                
                for ($lesson = 0; $lesson < $lessonsPerDay; $lesson++) {
                    $hour = 9 + ($lesson * 2) + rand(0, 1); // 9h, 11h, 13h, 15h, 17h avec variation
                    $startTime = $currentDate->copy()->setHour($hour)->setMinute(0)->setSecond(0);
                    
                    // Ignorer si l'heure est dans le passé aujourd'hui
                    if ($startTime->isPast() && !$startTime->isToday()) {
                        continue;
                    }
                    
                    $courseType = $courseTypes->random();
                    $duration = $courseType->duration ?? 60;
                    $endTime = $startTime->copy()->addMinutes($duration);
                    
                    // Vérifier les chevauchements
                    if ($this->hasTimeConflict($teacherId, $startTime, $endTime)) {
                        continue;
                    }
                    
                    $student = $students->random();
                    $location = $locations->random();
                    $price = $courseType->price ?? 35.00;
                    
                    // Statuts variés : surtout confirmed et pending
                    $statuses = ['confirmed', 'confirmed', 'pending', 'pending', 'pending'];
                    $status = $statuses[array_rand($statuses)];
                    
                    // Créer le cours
                    $lessonId = DB::table('lessons')->insertGetId([
                        'teacher_id' => $teacherId,
                        'student_id' => $student->student_id,
                        'course_type_id' => $courseType->id,
                        'location_id' => $location->id,
                        'club_id' => $clubId,
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'status' => $status,
                        'notes' => 'Cours de ' . $courseType->name . ' avec ' . $student->name,
                        'price' => $price,
                        'payment_status' => $status === 'confirmed' ? 'pending' : 'pending',
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                    
                    // Lier l'étudiant au cours via lesson_student
                    DB::table('lesson_student')->insert([
                        'lesson_id' => $lessonId,
                        'student_id' => $student->student_id,
                        'status' => $status,
                        'price' => $price,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                    
                    $lessonsCreated++;
                }
            }
        }

        return $lessonsCreated;
    }

    private function hasTimeConflict($teacherId, $startTime, $endTime)
    {
        $conflicts = DB::table('lessons')
            ->where('teacher_id', $teacherId)
            ->where(function ($query) use ($startTime, $endTime) {
                $query->whereBetween('start_time', [$startTime, $endTime->copy()->subSecond()])
                      ->orWhereBetween('end_time', [$startTime->copy()->addSecond(), $endTime])
                      ->orWhere(function ($q) use ($startTime, $endTime) {
                          $q->where('start_time', '<=', $startTime)
                            ->where('end_time', '>=', $endTime);
                      });
            })
            ->count();

        return $conflicts > 0;
    }
}

