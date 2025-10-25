<?php

/**
 * Script de remplissage du calendrier sur 6 mois
 * Pour le club : Centre Équestre des Étoiles
 * 
 * Ce script va :
 * 1. Créer des créneaux horaires (si besoin)
 * 2. Ajouter des enseignants au club
 * 3. Ajouter des élèves
 * 4. Créer des cours sur 6 mois
 */

require __DIR__ . '/../vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\Club;
use App\Models\User;
use App\Models\Teacher;
use App\Models\Student;
use App\Models\ClubOpenSlot;
use App\Models\CourseType;
use App\Models\Lesson;
use Carbon\Carbon;

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🎯 Début du remplissage du calendrier sur 6 mois\n";
echo "================================================\n\n";

// Configuration
$CLUB_ID = 3; // Centre Équestre des Étoiles
$NB_MONTHS = 6;

try {
    // 1. Récupérer le club
    $club = Club::findOrFail($CLUB_ID);
    echo "✅ Club trouvé: {$club->name} (ID: {$club->id})\n\n";

    // 2. Créer des créneaux horaires si nécessaire
    echo "📅 Création des créneaux horaires...\n";
    $discipline = DB::table('disciplines')->where('name', 'Cours individuel enfant')->first();
    
    if (!$discipline) {
        echo "❌ Discipline 'Cours individuel enfant' non trouvée\n";
        exit(1);
    }

    // Créneaux du club (lundi au dimanche, 9h-18h)
    $slots = [
        ['day' => 0, 'start' => '09:00', 'end' => '17:00', 'name' => 'Dimanche matin/après-midi'],
        ['day' => 1, 'start' => '14:00', 'end' => '18:00', 'name' => 'Lundi après-midi'],
        ['day' => 2, 'start' => '14:00', 'end' => '18:00', 'name' => 'Mardi après-midi'],
        ['day' => 3, 'start' => '09:00', 'end' => '12:00', 'name' => 'Mercredi matin'],
        ['day' => 3, 'start' => '14:00', 'end' => '18:00', 'name' => 'Mercredi après-midi'],
        ['day' => 4, 'start' => '14:00', 'end' => '18:00', 'name' => 'Jeudi après-midi'],
        ['day' => 5, 'start' => '14:00', 'end' => '18:00', 'name' => 'Vendredi après-midi'],
        ['day' => 6, 'start' => '09:00', 'end' => '17:00', 'name' => 'Samedi toute la journée'],
    ];

    $createdSlots = [];
    foreach ($slots as $slotData) {
        // Vérifier si le créneau existe déjà
        $existingSlot = ClubOpenSlot::where('club_id', $club->id)
            ->where('day_of_week', $slotData['day'])
            ->where('start_time', $slotData['start'])
            ->first();

        if (!$existingSlot) {
            $slot = ClubOpenSlot::create([
                'club_id' => $club->id,
                'discipline_id' => $discipline->id,
                'day_of_week' => $slotData['day'],
                'start_time' => $slotData['start'],
                'end_time' => $slotData['end'],
                'status' => 'active',
                'max_simultaneous_lessons' => 5
            ]);

            // Associer les types de cours
            $courseType = CourseType::where('name', 'Cours individuel enfant')->first();
            if ($courseType) {
                $slot->courseTypes()->attach($courseType->id);
            }

            $createdSlots[] = $slot;
            echo "  ✓ Créneau créé: {$slotData['name']}\n";
        } else {
            $createdSlots[] = $existingSlot;
            echo "  ℹ️  Créneau existant: {$slotData['name']}\n";
        }
    }
    echo "✅ {count($createdSlots)} créneaux disponibles\n\n";

    // 3. Récupérer ou créer des enseignants
    echo "👨‍🏫 Gestion des enseignants...\n";
    $teachers = Teacher::whereHas('user', function($q) use ($club) {
        $q->where('email', 'LIKE', '%centre-Équestre-des-Étoiles%');
    })->get();

    if ($teachers->count() < 3) {
        echo "  ➕ Création d'enseignants supplémentaires...\n";
        $newTeachers = [
            ['name' => 'Sophie Rousseau', 'email' => 'sophie.rousseau@centre-equestre-des-etoiles.fr', 'specialties' => 'CSO, Dressage'],
            ['name' => 'Thomas Girard', 'email' => 'thomas.girard@centre-equestre-des-etoiles.fr', 'specialties' => 'Voltige, Poney'],
            ['name' => 'Emma Blanc', 'email' => 'emma.blanc@centre-equestre-des-etoiles.fr', 'specialties' => 'Initiation, Baby poney'],
        ];

        foreach ($newTeachers as $teacherData) {
            $existingUser = User::where('email', $teacherData['email'])->first();
            if (!$existingUser) {
                $user = User::create([
                    'name' => $teacherData['name'],
                    'email' => $teacherData['email'],
                    'password' => Hash::make('password'),
                    'role' => 'teacher',
                    'email_verified_at' => now()
                ]);

                $teacher = Teacher::create([
                    'user_id' => $user->id,
                    'specialties' => $teacherData['specialties'],
                    'hourly_rate' => 35.00,
                    'bio' => 'Enseignant passionné d\'équitation'
                ]);

                // Associer au club
                DB::table('teacher_club')->insert([
                    'teacher_id' => $teacher->id,
                    'club_id' => $club->id,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                $teachers->push($teacher);
                echo "    ✓ {$teacherData['name']} créé\n";
            }
        }
    }
    echo "✅ {$teachers->count()} enseignants disponibles\n\n";

    // 4. Créer des élèves
    echo "👦 Création d'élèves...\n";
    $studentNames = [
        ['name' => 'Lucas Martin', 'email' => 'lucas.martin@example.com', 'age' => 8],
        ['name' => 'Emma Dubois', 'email' => 'emma.dubois@example.com', 'age' => 10],
        ['name' => 'Noah Bernard', 'email' => 'noah.bernard@example.com', 'age' => 7],
        ['name' => 'Léa Thomas', 'email' => 'lea.thomas@example.com', 'age' => 9],
        ['name' => 'Louis Robert', 'email' => 'louis.robert@example.com', 'age' => 11],
        ['name' => 'Chloé Petit', 'email' => 'chloe.petit@example.com', 'age' => 8],
        ['name' => 'Gabriel Richard', 'email' => 'gabriel.richard@example.com', 'age' => 12],
        ['name' => 'Zoé Durand', 'email' => 'zoe.durand@example.com', 'age' => 6],
        ['name' => 'Arthur Moreau', 'email' => 'arthur.moreau@example.com', 'age' => 9],
        ['name' => 'Camille Simon', 'email' => 'camille.simon@example.com', 'age' => 10],
        ['name' => 'Hugo Laurent', 'email' => 'hugo.laurent@example.com', 'age' => 7],
        ['name' => 'Inès Lefebvre', 'email' => 'ines.lefebvre@example.com', 'age' => 11],
        ['name' => 'Raphaël Michel', 'email' => 'raphael.michel@example.com', 'age' => 8],
        ['name' => 'Manon Garcia', 'email' => 'manon.garcia@example.com', 'age' => 9],
        ['name' => 'Tom Roux', 'email' => 'tom.roux@example.com', 'age' => 10],
    ];

    $students = [];
    foreach ($studentNames as $studentData) {
        $existingUser = User::where('email', $studentData['email'])->first();
        if (!$existingUser) {
            $user = User::create([
                'name' => $studentData['name'],
                'email' => $studentData['email'],
                'password' => Hash::make('password'),
                'role' => 'student',
                'email_verified_at' => now()
            ]);

            $birthDate = Carbon::now()->subYears($studentData['age'])->subMonths(rand(0, 11));

            $student = Student::create([
                'user_id' => $user->id,
                'club_id' => $club->id,
                'date_of_birth' => $birthDate,
                'level' => ['debutant', 'intermediaire', 'avance'][rand(0, 2)]
            ]);

            $students[] = $student;
            echo "  ✓ {$studentData['name']} ({$studentData['age']} ans) créé\n";
        } else {
            $student = Student::where('user_id', $existingUser->id)->first();
            if ($student) {
                $students[] = $student;
                echo "  ℹ️  {$studentData['name']} existe déjà\n";
            }
        }
    }
    echo "✅ " . count($students) . " élèves disponibles\n\n";

    // 5. Créer des cours sur 6 mois
    echo "📚 Création des cours sur 6 mois...\n";
    $courseType = CourseType::where('name', 'Cours individuel enfant')->first();
    
    if (!$courseType) {
        echo "❌ Type de cours 'Cours individuel enfant' non trouvé\n";
        exit(1);
    }

    $startDate = Carbon::now()->startOfWeek();
    $endDate = Carbon::now()->addMonths($NB_MONTHS);
    $lessonsCreated = 0;

    // Pour chaque semaine sur 6 mois
    $currentDate = $startDate->copy();
    while ($currentDate->lte($endDate)) {
        // Pour chaque créneau
        foreach ($createdSlots as $slot) {
            // Trouver le prochain jour correspondant au créneau
            $lessonDate = $currentDate->copy();
            while ($lessonDate->dayOfWeek != $slot->day_of_week) {
                $lessonDate->addDay();
            }

            // Ne pas créer de cours dans le passé
            if ($lessonDate->isPast()) {
                continue;
            }

            // Créer 2-4 cours par créneau (aléatoire)
            $nbLessons = rand(2, 4);
            
            for ($i = 0; $i < $nbLessons && $i < count($students); $i++) {
                // Calculer l'heure de début (espacés de 30 minutes)
                $slotStart = Carbon::createFromFormat('H:i', $slot->start_time);
                $startTime = $lessonDate->copy()
                    ->setHour($slotStart->hour)
                    ->setMinute($slotStart->minute)
                    ->addMinutes($i * 30);

                $endTime = $startTime->copy()->addMinutes(20); // Cours de 20 min

                // Vérifier que ça ne dépasse pas la fin du créneau
                $slotEnd = Carbon::createFromFormat('H:i', $slot->end_time);
                $slotEndFull = $lessonDate->copy()->setHour($slotEnd->hour)->setMinute($slotEnd->minute);
                
                if ($endTime->gt($slotEndFull)) {
                    break;
                }

                // Sélectionner un enseignant et un élève aléatoirement
                $teacher = $teachers->random();
                $student = $students[array_rand($students)];

                // Vérifier qu'il n'existe pas déjà
                $existingLesson = Lesson::where('club_id', $club->id)
                    ->where('teacher_id', $teacher->id)
                    ->where('start_time', $startTime)
                    ->first();

                if (!$existingLesson) {
                    Lesson::create([
                        'club_id' => $club->id,
                        'teacher_id' => $teacher->id,
                        'student_id' => $student->id,
                        'course_type_id' => $courseType->id,
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'price' => 18.00,
                        'status' => 'confirmed',
                        'payment_status' => 'pending'
                    ]);

                    $lessonsCreated++;

                    if ($lessonsCreated % 50 == 0) {
                        echo "  ... {$lessonsCreated} cours créés\n";
                    }
                }
            }
        }

        // Passer à la semaine suivante
        $currentDate->addWeek();
    }

    echo "✅ {$lessonsCreated} cours créés sur 6 mois\n\n";

    // 6. Statistiques finales
    echo "📊 STATISTIQUES FINALES\n";
    echo "======================\n";
    echo "Club: {$club->name}\n";
    echo "Créneaux: " . count($createdSlots) . "\n";
    echo "Enseignants: {$teachers->count()}\n";
    echo "Élèves: " . count($students) . "\n";
    echo "Cours créés: {$lessonsCreated}\n";
    echo "Période: " . $startDate->format('d/m/Y') . " → " . $endDate->format('d/m/Y') . "\n\n";

    echo "🎉 Remplissage terminé avec succès !\n";

} catch (\Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}

