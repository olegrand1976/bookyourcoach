<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Club;
use App\Models\Teacher;
use App\Models\Student;
use App\Models\Lesson;
use App\Models\CourseType;
use App\Models\Location;

class AdminClubTestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🎯 Création des données de test pour le club de l\'admin...');

        // 1. Trouver l'utilisateur admin
        $adminEmail = 'b.murgo1976@gmail.com';
        $admin = User::where('email', $adminEmail)->first();

        if (!$admin) {
            $this->command->error("❌ Utilisateur admin avec l'email {$adminEmail} introuvable");
            return;
        }

        $this->command->info("✅ Admin trouvé: {$admin->name} (ID: {$admin->id})");

        // 2. Trouver ou créer le club de l'admin
        $club = $this->findOrCreateAdminClub($admin);
        $this->command->info("✅ Club: {$club->name} (ID: {$club->id})");

        // 3. Créer des enseignants pour ce club
        $teachers = $this->createTeachersForClub($club);
        $this->command->info("✅ {$teachers->count()} enseignants créés");

        // 4. Créer des élèves pour ce club
        $students = $this->createStudentsForClub($club);
        $this->command->info("✅ {$students->count()} élèves créés");

        // 5. Récupérer les course types et locations
        $courseTypes = CourseType::all();
        $locations = Location::all();

        if ($courseTypes->isEmpty()) {
            $this->command->warn('⚠️ Aucun course_type trouvé. Veuillez exécuter CourseTypeSeeder d\'abord.');
            return;
        }

        if ($locations->isEmpty()) {
            $this->command->warn('⚠️ Aucune location trouvée. Création d\'une location par défaut...');
            $defaultLocation = Location::firstOrCreate(
                ['name' => 'Salle principale'],
                [
                    'address' => $club->address ?? 'Adresse du club',
                    'city' => $club->city ?? 'Ville',
                    'postal_code' => $club->postal_code ?? '00000',
                    'country' => $club->country ?? 'Belgium',
                    'club_id' => $club->id,
                ]
            );
            $locations = collect([$defaultLocation]);
        }

        // 6. Créer des cours pour les prochaines semaines
        $lessonsCreated = $this->createLessonsForClub($club, $teachers, $students, $courseTypes, $locations);
        $this->command->info("✅ {$lessonsCreated} cours créés");

        $this->command->info('🎉 Données de test créées avec succès pour le club de l\'admin !');
    }

    private function findOrCreateAdminClub(User $admin): Club
    {
        // Chercher si l'admin a déjà un club associé
        $clubUser = DB::table('club_user')
            ->where('user_id', $admin->id)
            ->where(function ($query) {
                $query->where('role', 'owner')
                      ->orWhere('role', 'manager')
                      ->orWhere('is_admin', true);
            })
            ->first();

        if ($clubUser) {
            $club = Club::find($clubUser->club_id);
            if ($club) {
                return $club;
            }
        }

        // Si aucun club trouvé, créer un club pour l'admin
        $club = Club::firstOrCreate(
            ['email' => $admin->email],
            [
                'name' => $admin->name . ' Club',
                'description' => 'Club de ' . $admin->name,
                'phone' => $admin->phone ?? '+32 2 123 45 67',
                'street' => $admin->street ?? 'Rue de l\'Équitation',
                'street_number' => $admin->street_number ?? '1',
                'city' => $admin->city ?? 'Bruxelles',
                'postal_code' => $admin->postal_code ?? '1000',
                'country' => $admin->country ?? 'Belgium',
                'is_active' => true,
            ]
        );

        // Lier l'admin au club
        DB::table('club_user')->updateOrInsert(
            ['club_id' => $club->id, 'user_id' => $admin->id],
            [
                'role' => 'owner',
                'is_admin' => true,
                'joined_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        return $club;
    }

    private function createTeachersForClub(Club $club)
    {
        $teachers = collect();
        $teacherNames = [
            ['Sophie', 'Martin', ['dressage', 'saut d\'obstacles']],
            ['Pierre', 'Dubois', ['concours complet', 'cross']],
            ['Marie', 'Leroy', ['dressage', 'équitation de loisir']],
            ['Jean', 'Moreau', ['saut d\'obstacles', 'voltige']],
            ['Claire', 'Petit', ['équitation western', 'endurance']],
        ];

        foreach ($teacherNames as $index => $teacherData) {
            $firstName = $teacherData[0];
            $lastName = $teacherData[1];
            $specialties = $teacherData[2];
            
            // Créer l'utilisateur enseignant
            $email = strtolower($firstName . '.' . $lastName . '@club' . $club->id . '.test');
            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $firstName . ' ' . $lastName,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'password' => Hash::make('password'),
                    'role' => 'teacher',
                    'phone' => '+32 ' . rand(470, 499) . ' ' . rand(100000, 999999),
                    'city' => $club->city ?? 'Bruxelles',
                    'country' => $club->country ?? 'Belgium',
                    'status' => 'active',
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );

            // Créer le profil enseignant
            $teacher = Teacher::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'club_id' => $club->id,
                    'specialties' => $specialties,
                    'experience_years' => rand(5, 20),
                    'certifications' => ['BEES 1', 'Galop 7 FFE'],
                    'hourly_rate' => rand(40, 80),
                    'bio' => 'Enseignant expérimenté spécialisé en ' . implode(' et ', $specialties),
                    'is_available' => true,
                    'rating' => rand(40, 50) / 10, // 4.0 à 5.0
                    'total_lessons' => rand(100, 500),
                ]
            );

            // Lier l'enseignant au club
            DB::table('club_teachers')->updateOrInsert(
                ['club_id' => $club->id, 'teacher_id' => $teacher->id],
                [
                    'allowed_disciplines' => json_encode($specialties),
                    'hourly_rate' => $teacher->hourly_rate,
                    'is_active' => true,
                    'joined_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            $teachers->push($teacher);
        }

        return $teachers;
    }

    private function createStudentsForClub(Club $club)
    {
        $students = collect();
        $firstNames = ['Emma', 'Lucas', 'Chloé', 'Nathan', 'Léa', 'Hugo', 'Manon', 'Ethan', 'Camille', 'Louis', 'Sarah', 'Gabriel', 'Inès', 'Raphaël', 'Zoé'];
        $lastNames = ['Dubois', 'Martin', 'Bernard', 'Thomas', 'Petit', 'Robert', 'Richard', 'Durand', 'Moreau', 'Simon', 'Laurent', 'Lefebvre', 'Michel', 'Garcia', 'David'];

        $levels = ['debutant', 'intermediaire', 'avance', 'expert'];

        // Créer 12-15 étudiants
        for ($i = 0; $i < 12; $i++) {
            $firstName = $firstNames[array_rand($firstNames)];
            $lastName = $lastNames[array_rand($lastNames)];
            $email = strtolower($firstName . '.' . $lastName . $i . '@student' . $club->id . '.test');

            // Créer l'utilisateur élève
            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $firstName . ' ' . $lastName,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'password' => Hash::make('password'),
                    'role' => 'student',
                    'phone' => '+32 ' . rand(470, 499) . ' ' . rand(100000, 999999),
                    'city' => $club->city ?? 'Bruxelles',
                    'country' => $club->country ?? 'Belgium',
                    'status' => 'active',
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );

            // Créer le profil élève
            $level = $levels[array_rand($levels)];
            $student = Student::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'club_id' => $club->id,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'phone' => $user->phone,
                    'level' => $level,
                    'goals' => 'Progresser en équitation et participer aux compétitions',
                    'medical_info' => 'Aucune allergie connue',
                    'emergency_contacts' => [
                        'name' => 'Parent ' . $lastName,
                        'phone' => '+32 ' . rand(470, 499) . ' ' . rand(100000, 999999),
                    ],
                ]
            );

            // Lier l'élève au club
            DB::table('club_students')->updateOrInsert(
                ['club_id' => $club->id, 'student_id' => $student->id],
                [
                    'level' => $level,
                    'goals' => 'Progresser en équitation',
                    'medical_info' => 'Aucune allergie connue',
                    'preferred_disciplines' => json_encode(['équitation de loisir', 'dressage']),
                    'is_active' => true,
                    'joined_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            $students->push($student);
        }

        return $students;
    }

    private function createLessonsForClub(Club $club, $teachers, $students, $courseTypes, $locations): int
    {
        if ($teachers->isEmpty() || $students->isEmpty()) {
            $this->command->warn('⚠️ Aucun enseignant ou élève pour créer des cours');
            return 0;
        }

        $lessonsCreated = 0;
        $startDate = Carbon::now()->startOfWeek();
        $teacherIds = $teachers->pluck('id')->toArray();
        $studentIds = $students->pluck('id')->toArray();
        $courseTypeIds = $courseTypes->pluck('id')->toArray();
        $locationIds = $locations->pluck('id')->toArray();

        $statuses = ['pending', 'confirmed', 'confirmed', 'confirmed']; // Plus de confirmed que pending
        $durations = [30, 45, 60, 60]; // Plus de cours de 60 minutes

        // Créer des cours pour les 4 prochaines semaines
        for ($week = 0; $week < 4; $week++) {
            for ($day = 1; $day <= 6; $day++) { // Lundi à Samedi
                $currentDate = $startDate->copy()->addWeeks($week)->addDays($day - 1);
                
                // 4-8 cours par jour
                $lessonsPerDay = rand(4, 8);
                
                for ($lesson = 0; $lesson < $lessonsPerDay; $lesson++) {
                    $hour = 9 + ($lesson * 1.5); // 9h, 10h30, 12h, 13h30, 15h, 16h30, 18h
                    if ($hour >= 19) continue; // Pas de cours après 19h
                    
                    $startTime = $currentDate->copy()->setHour((int)$hour)->setMinute(($hour - (int)$hour) * 60);
                    $duration = $durations[array_rand($durations)];
                    $endTime = $startTime->copy()->addMinutes($duration);
                    
                    // Sélectionner aléatoirement
                    $teacherId = $teacherIds[array_rand($teacherIds)];
                    $studentId = $studentIds[array_rand($studentIds)];
                    $courseTypeId = $courseTypeIds[array_rand($courseTypeIds)];
                    $locationId = $locationIds[array_rand($locationIds)];
                    $status = $statuses[array_rand($statuses)];
                    
                    // Créer le cours
                    $lessonData = [
                        'club_id' => $club->id,
                        'teacher_id' => $teacherId,
                        'student_id' => $studentId,
                        'course_type_id' => $courseTypeId,
                        'location_id' => $locationId,
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'status' => $status,
                        'payment_status' => $status === 'confirmed' ? (rand(0, 1) ? 'paid' : 'pending') : 'pending',
                        'price' => rand(35, 80),
                        'notes' => 'Cours de test généré automatiquement',
                        'est_legacy' => false,
                        'deduct_from_subscription' => rand(0, 1) === 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    $lessonId = DB::table('lessons')->insertGetId($lessonData);
                    
                    // Lier l'étudiant au cours via la table pivot
                    if (Schema::hasTable('lesson_student')) {
                        DB::table('lesson_student')->insert([
                            'lesson_id' => $lessonId,
                            'student_id' => $studentId,
                            'status' => 'confirmed',
                            'price' => $lessonData['price'],
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                    
                    $lessonsCreated++;
                }
            }
        }

        return $lessonsCreated;
    }
}
