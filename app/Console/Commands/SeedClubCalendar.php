<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
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

class SeedClubCalendar extends Command
{
    protected $signature = 'seed:club-calendar {club_id=3} {months=6}';
    protected $description = 'Remplit le calendrier d\'un club avec des cours sur X mois';

    public function handle()
    {
        $clubId = $this->argument('club_id');
        $nbMonths = $this->argument('months');

        $this->info("🎯 Début du remplissage du calendrier sur {$nbMonths} mois");
        $this->info("================================================\n");

        try {
            // 1. Récupérer le club
            $club = Club::findOrFail($clubId);
            $this->info("✅ Club trouvé: {$club->name} (ID: {$club->id})\n");

            // 2. Créer des créneaux horaires
            $this->info("📅 Création des créneaux horaires...");
            $discipline = DB::table('disciplines')->where('name', 'Cours individuel enfant')->first();
            
            if (!$discipline) {
                $this->error("❌ Discipline 'Cours individuel enfant' non trouvée");
                return 1;
            }

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

                    $courseType = CourseType::where('name', 'Cours individuel enfant')->first();
                    if ($courseType) {
                        $slot->courseTypes()->attach($courseType->id);
                    }

                    $createdSlots[] = $slot;
                    $this->line("  ✓ Créneau créé: {$slotData['name']}");
                } else {
                    $createdSlots[] = $existingSlot;
                    $this->line("  ℹ️  Créneau existant: {$slotData['name']}");
                }
            }
            $this->info("✅ " . count($createdSlots) . " créneaux disponibles\n");

            // 3. Récupérer ou créer des enseignants
            $this->info("👨‍🏫 Gestion des enseignants...");
            $teachers = Teacher::whereHas('user', function($q) {
                $q->where('email', 'LIKE', '%centre-equestre-des-etoiles%')
                  ->orWhere('email', 'LIKE', '%centre-Équestre-des-Étoiles%');
            })->get();

            if ($teachers->count() < 3) {
                $this->line("  ➕ Création d'enseignants supplémentaires...");
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

                        // Associer au club via la table pivot
                        $teacher->clubs()->attach($club->id);

                        $teachers->push($teacher);
                        $this->line("    ✓ {$teacherData['name']} créé");
                    }
                }
            }
            $this->info("✅ {$teachers->count()} enseignants disponibles\n");

            // 4. Créer des élèves
            $this->info("👦 Création d'élèves...");
            $studentNames = [
                ['name' => 'Lucas Martin', 'email' => 'lucas.martin@etoiles.com', 'age' => 8],
                ['name' => 'Emma Dubois', 'email' => 'emma.dubois@etoiles.com', 'age' => 10],
                ['name' => 'Noah Bernard', 'email' => 'noah.bernard@etoiles.com', 'age' => 7],
                ['name' => 'Léa Thomas', 'email' => 'lea.thomas@etoiles.com', 'age' => 9],
                ['name' => 'Louis Robert', 'email' => 'louis.robert@etoiles.com', 'age' => 11],
                ['name' => 'Chloé Petit', 'email' => 'chloe.petit@etoiles.com', 'age' => 8],
                ['name' => 'Gabriel Richard', 'email' => 'gabriel.richard@etoiles.com', 'age' => 12],
                ['name' => 'Zoé Durand', 'email' => 'zoe.durand@etoiles.com', 'age' => 6],
                ['name' => 'Arthur Moreau', 'email' => 'arthur.moreau@etoiles.com', 'age' => 9],
                ['name' => 'Camille Simon', 'email' => 'camille.simon@etoiles.com', 'age' => 10],
                ['name' => 'Hugo Laurent', 'email' => 'hugo.laurent@etoiles.com', 'age' => 7],
                ['name' => 'Inès Lefebvre', 'email' => 'ines.lefebvre@etoiles.com', 'age' => 11],
                ['name' => 'Raphaël Michel', 'email' => 'raphael.michel@etoiles.com', 'age' => 8],
                ['name' => 'Manon Garcia', 'email' => 'manon.garcia@etoiles.com', 'age' => 9],
                ['name' => 'Tom Roux', 'email' => 'tom.roux@etoiles.com', 'age' => 10],
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
                    $this->line("  ✓ {$studentData['name']} ({$studentData['age']} ans) créé");
                } else {
                    $student = Student::where('user_id', $existingUser->id)->first();
                    if ($student) {
                        $students[] = $student;
                    }
                }
            }
            $this->info("✅ " . count($students) . " élèves disponibles\n");

            // 5. Créer des cours sur X mois
            $this->info("📚 Création des cours sur {$nbMonths} mois...");
            $courseType = CourseType::where('name', 'Cours individuel enfant')->first();
            
            if (!$courseType) {
                $this->error("❌ Type de cours 'Cours individuel enfant' non trouvé");
                return 1;
            }

            $startDate = Carbon::now()->startOfWeek();
            $endDate = Carbon::now()->addMonths((int) $nbMonths);
            $lessonsCreated = 0;

            $currentDate = $startDate->copy();
            $bar = $this->output->createProgressBar(ceil($startDate->diffInWeeks($endDate)));
            
            while ($currentDate->lte($endDate)) {
                foreach ($createdSlots as $slot) {
                    $lessonDate = $currentDate->copy();
                    while ($lessonDate->dayOfWeek != $slot->day_of_week) {
                        $lessonDate->addDay();
                    }

                    if ($lessonDate->isPast()) {
                        continue;
                    }

                    $nbLessons = rand(2, 4);
                    
                    for ($i = 0; $i < $nbLessons && $i < count($students); $i++) {
                        $slotStart = Carbon::parse($slot->start_time);
                        $startTime = $lessonDate->copy()
                            ->setHour($slotStart->hour)
                            ->setMinute($slotStart->minute)
                            ->addMinutes($i * 30);

                        $endTime = $startTime->copy()->addMinutes(20);

                        $slotEnd = Carbon::parse($slot->end_time);
                        $slotEndFull = $lessonDate->copy()->setHour($slotEnd->hour)->setMinute($slotEnd->minute);
                        
                        if ($endTime->gt($slotEndFull)) {
                            break;
                        }

                        $teacher = $teachers->random();
                        $student = $students[array_rand($students)];

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
                        }
                    }
                }

                $currentDate->addWeek();
                $bar->advance();
            }
            $bar->finish();

            $this->newLine(2);
            $this->info("✅ {$lessonsCreated} cours créés sur {$nbMonths} mois\n");

            // 6. Statistiques finales
            $this->info("📊 STATISTIQUES FINALES");
            $this->info("======================");
            $this->line("Club: {$club->name}");
            $this->line("Créneaux: " . count($createdSlots));
            $this->line("Enseignants: {$teachers->count()}");
            $this->line("Élèves: " . count($students));
            $this->line("Cours créés: {$lessonsCreated}");
            $this->line("Période: " . $startDate->format('d/m/Y') . " → " . $endDate->format('d/m/Y'));
            
            $this->newLine();
            $this->info("🎉 Remplissage terminé avec succès !");

            return 0;

        } catch (\Exception $e) {
            $this->error("❌ ERREUR: " . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
    }
}

