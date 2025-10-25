<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Club;
use App\Models\Teacher;
use App\Models\Student;
use App\Models\Lesson;
use App\Models\CourseType;
use App\Models\Location;
use App\Models\Payment;
use App\Models\Invoice;
use Carbon\Carbon;

class CompleteEnvironmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        echo "🚀 Création d'un environnement complet de test pour BookYourCoach...\n\n";

        // Vérifier que les données de base existent
        $this->checkBaseData();

        // Créer l'environnement de cours complet
        $this->createCompleteLessonEnvironment();

        // Créer des statistiques réalistes
        $this->createRealisticStatistics();

        // Générer un rapport final
        $this->generateFinalReport();

        echo "\n🎉 Environnement complet créé avec succès !\n";
        echo "📊 Vous pouvez maintenant tester toutes les fonctionnalités du système.\n";
    }

    /**
     * Vérifier que les données de base existent
     */
    private function checkBaseData()
    {
        echo "🔍 Vérification des données de base...\n";

        $requiredData = [
            'clubs' => Club::count(),
            'course_types' => CourseType::count(),
            'locations' => Location::count(),
            'teachers' => Teacher::count(),
            'students' => Student::count(),
            'users' => User::count()
        ];

        foreach ($requiredData as $type => $count) {
            if ($count === 0) {
                echo "❌ Aucun {$type} trouvé. Veuillez d'abord exécuter les seeders de base.\n";
                echo "   Commandes suggérées :\n";
                echo "   - php artisan db:seed --class=ClubTestDataSeeder\n";
                echo "   - php artisan db:seed --class=DemoDataSeeder\n";
                return false;
            }
            echo "✅ {$count} {$type} trouvés\n";
        }

        echo "\n";
        return true;
    }

    /**
     * Créer l'environnement de cours complet
     */
    private function createCompleteLessonEnvironment()
    {
        echo "📚 Création de l'environnement de cours complet...\n";

        // Récupérer les données existantes
        $clubs = Club::all();
        $courseTypes = CourseType::all();
        $locations = Location::all();
        $teachers = Teacher::with(['user', 'user.clubs'])->get();
        $students = Student::with(['user', 'user.clubs'])->get();

        // Organiser par club
        $teachersByClub = $this->organizeByClub($teachers);
        $studentsByClub = $this->organizeByClub($students);

        $totalLessons = 0;
        $totalPayments = 0;

        foreach ($clubs as $club) {
            echo "🏇 Traitement du club : {$club->name}\n";
            
            $clubTeachers = $teachersByClub[$club->id] ?? collect();
            $clubStudents = $studentsByClub[$club->id] ?? collect();

            if ($clubTeachers->isEmpty() || $clubStudents->isEmpty()) {
                echo "   ⚠️ Pas assez d'enseignants ou d'étudiants pour ce club\n";
                continue;
            }

            // Créer différents types de cours pour ce club
            $clubStats = $this->createClubLessons($club, $clubTeachers, $clubStudents, $courseTypes, $locations);
            
            $totalLessons += $clubStats['lessons'];
            $totalPayments += $clubStats['payments'];

            echo "   ✅ {$clubStats['lessons']} cours créés, {$clubStats['payments']} paiements\n";
        }

        echo "\n📊 Résumé global :\n";
        echo "   - {$totalLessons} cours créés au total\n";
        echo "   - {$totalPayments} paiements générés\n";
        echo "   - " . Lesson::where('status', 'completed')->count() . " cours terminés\n";
        echo "   - " . Lesson::where('status', 'confirmed')->count() . " cours confirmés\n";
        echo "   - " . Lesson::where('status', 'pending')->count() . " cours en attente\n";
        echo "   - " . Lesson::where('status', 'cancelled')->count() . " cours annulés\n";
        echo "   - " . Lesson::where('status', 'no_show')->count() . " absences\n\n";
    }

    /**
     * Organiser les utilisateurs par club
     */
    private function organizeByClub($users)
    {
        $organized = collect();
        
        $users->each(function ($user) use ($organized) {
            $user->user->clubs->each(function ($club) use ($user, $organized) {
                if (!$organized->has($club->id)) {
                    $organized->put($club->id, collect());
                }
                $organized->get($club->id)->push($user);
            });
        });

        return $organized;
    }

    /**
     * Créer les cours pour un club spécifique
     */
    private function createClubLessons($club, $teachers, $students, $courseTypes, $locations)
    {
        $lessonCount = 0;
        $paymentCount = 0;

        // 1. Cours individuels (60% des cours)
        $individualCount = rand(15, 25);
        for ($i = 0; $i < $individualCount; $i++) {
            $lesson = $this->createIndividualLesson($teachers, $students, $courseTypes, $locations);
            if ($lesson) {
                $lessonCount++;
                if ($lesson->status === 'completed' && rand(0, 100) > 20) {
                    $this->createPaymentForLesson($lesson, $lesson->student);
                    $paymentCount++;
                }
            }
        }

        // 2. Cours de groupe (25% des cours)
        $groupCount = rand(8, 15);
        for ($i = 0; $i < $groupCount; $i++) {
            $lesson = $this->createGroupLesson($teachers, $students, $courseTypes, $locations);
            if ($lesson) {
                $lessonCount++;
            }
        }

        // 3. Cours récurrents (10% des cours)
        $recurringCount = rand(3, 8);
        for ($i = 0; $i < $recurringCount; $i++) {
            $lessons = $this->createRecurringSeries($teachers, $students, $courseTypes, $locations);
            $lessonCount += count($lessons);
        }

        // 4. Événements spéciaux (5% des cours)
        $eventCount = rand(2, 5);
        for ($i = 0; $i < $eventCount; $i++) {
            $lesson = $this->createSpecialEvent($teachers, $students, $courseTypes, $locations);
            if ($lesson) {
                $lessonCount++;
            }
        }

        return [
            'lessons' => $lessonCount,
            'payments' => $paymentCount
        ];
    }

    /**
     * Créer un cours individuel
     */
    private function createIndividualLesson($teachers, $students, $courseTypes, $locations)
    {
        $teacher = $teachers->random();
        $student = $students->random();
        $courseType = $courseTypes->random();
        $location = $locations->random();

        $isPast = rand(0, 1);
        $startTime = $isPast ? 
            Carbon::now()->subDays(rand(1, 180)) : 
            Carbon::now()->addDays(rand(1, 90));

        $startTime->setTime(rand(8, 19), rand(0, 3) * 15);
        $endTime = $startTime->copy()->addMinutes($courseType->duration ?? 60);

        $statuses = $isPast ? 
            ['completed', 'completed', 'completed', 'cancelled', 'no_show'] : 
            ['pending', 'confirmed', 'confirmed', 'confirmed'];

        $status = $statuses[array_rand($statuses)];
        $price = $courseType->price ?? rand(40, 80);

        $lesson = Lesson::create([
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'course_type_id' => $courseType->id,
            'location_id' => $location->id,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'status' => $status,
            'price' => $price,
            'notes' => $this->generateLessonNotes($status, $courseType->name, 'individuel'),
            'teacher_feedback' => $status === 'completed' ? $this->generateTeacherFeedback() : null,
            'rating' => $status === 'completed' ? rand(3, 5) : null,
            'review' => $status === 'completed' && rand(0, 100) > 30 ? $this->generateStudentReview() : null,
        ]);

        $lesson->students()->attach($student->id, [
            'status' => $this->mapLessonStatusToStudentStatus($status),
            'price' => $price,
            'notes' => $lesson->notes
        ]);

        return $lesson;
    }

    /**
     * Mapper le statut du cours vers le statut de l'étudiant
     */
    private function mapLessonStatusToStudentStatus($lessonStatus)
    {
        $mapping = [
            'completed' => 'confirmed',
            'confirmed' => 'confirmed',
            'pending' => 'pending',
            'cancelled' => 'cancelled',
            'no_show' => 'cancelled' // no_show devient cancelled pour l'étudiant
        ];

        return $mapping[$lessonStatus] ?? 'pending';
    }

    /**
     * Créer un cours de groupe
     */
    private function createGroupLesson($teachers, $students, $courseTypes, $locations)
    {
        $teacher = $teachers->random();
        $courseType = $courseTypes->random();
        $location = $locations->random();

        // Sélectionner 2-4 étudiants pour le cours de groupe
        $groupStudents = $students->random(rand(2, min(4, $students->count())));
        $primaryStudent = $groupStudents->first();

        $isPast = rand(0, 1);
        $startTime = $isPast ? 
            Carbon::now()->subDays(rand(1, 180)) : 
            Carbon::now()->addDays(rand(1, 90));

        $startTime->setTime(rand(9, 17), rand(0, 3) * 15);
        $endTime = $startTime->copy()->addMinutes($courseType->duration ?? 60);

        $statuses = $isPast ? 
            ['completed', 'completed', 'completed', 'cancelled'] : 
            ['pending', 'confirmed', 'confirmed', 'confirmed'];

        $status = $statuses[array_rand($statuses)];
        $price = ($courseType->price ?? rand(40, 80)) * 0.8; // Prix réduit pour les cours de groupe

        $lesson = Lesson::create([
            'teacher_id' => $teacher->id,
            'student_id' => $primaryStudent->id,
            'course_type_id' => $courseType->id,
            'location_id' => $location->id,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'status' => $status,
            'price' => $price,
            'notes' => $this->generateLessonNotes($status, $courseType->name, 'groupe'),
        ]);

        // Associer tous les étudiants du groupe au cours
        foreach ($groupStudents as $student) {
            $lesson->students()->attach($student->id, [
                'status' => $this->mapLessonStatusToStudentStatus($status),
                'price' => $price,
                'notes' => 'Cours de groupe - ' . $lesson->notes
            ]);
        }

        return $lesson;
    }

    /**
     * Créer une série de cours récurrents
     */
    private function createRecurringSeries($teachers, $students, $courseTypes, $locations)
    {
        $teacher = $teachers->random();
        $student = $students->random();
        $courseType = $courseTypes->random();
        $location = $locations->random();

        $seriesLength = rand(6, 10);
        $startDate = Carbon::now()->addDays(rand(1, 30));
        $startDate->setTime(rand(9, 17), rand(0, 3) * 15);

        $lessons = [];

        for ($week = 0; $week < $seriesLength; $week++) {
            $lessonDate = $startDate->copy()->addWeeks($week);
            $endDate = $lessonDate->copy()->addMinutes($courseType->duration ?? 60);

            $status = $week < 2 ? 'confirmed' : 'pending';

            $lesson = Lesson::create([
                'teacher_id' => $teacher->id,
                'student_id' => $student->id,
                'course_type_id' => $courseType->id,
                'location_id' => $location->id,
                'start_time' => $lessonDate,
                'end_time' => $endDate,
                'status' => $status,
                'price' => $courseType->price ?? 50,
                'notes' => "Cours récurrent - Séance " . ($week + 1) . "/{$seriesLength}",
            ]);

            $lesson->students()->attach($student->id, [
                'status' => $this->mapLessonStatusToStudentStatus($status),
                'price' => $lesson->price,
                'notes' => $lesson->notes
            ]);

            $lessons[] = $lesson;
        }

        return $lessons;
    }

    /**
     * Créer un événement spécial
     */
    private function createSpecialEvent($teachers, $students, $courseTypes, $locations)
    {
        $teacher = $teachers->random();
        $courseType = $courseTypes->random();
        $location = $locations->random();

        $specialEvents = [
            'Compétition de dressage',
            'Stage de saut d\'obstacles',
            'Cours de voltige',
            'Démonstration équestre',
            'Formation aux soins des chevaux',
            'Randonnée équestre',
            'Cours de maréchalerie',
            'Formation sécurité'
        ];

        $eventName = $specialEvents[array_rand($specialEvents)];
        $eventStudents = $students->random(rand(3, min(8, $students->count())));
        $primaryStudent = $eventStudents->first();

        $eventDate = Carbon::now()->addDays(rand(15, 90));
        $eventDate->setTime(10, 0);
        $eventEnd = $eventDate->copy()->addHours(3);

        $lesson = Lesson::create([
            'teacher_id' => $teacher->id,
            'student_id' => $primaryStudent->id,
            'course_type_id' => $courseType->id,
            'location_id' => $location->id,
            'start_time' => $eventDate,
            'end_time' => $eventEnd,
            'status' => 'confirmed',
            'price' => ($courseType->price ?? 50) * 2,
            'notes' => "Événement spécial : {$eventName}",
        ]);

        // Associer tous les étudiants à l'événement
        foreach ($eventStudents as $student) {
            $lesson->students()->attach($student->id, [
                'status' => $this->mapLessonStatusToStudentStatus('confirmed'),
                'price' => $lesson->price * 0.7,
                'notes' => "Participation à l'événement : {$eventName}"
            ]);
        }

        return $lesson;
    }

    /**
     * Créer des statistiques réalistes
     */
    private function createRealisticStatistics()
    {
        echo "📈 Génération de statistiques réalistes...\n";

        // Mettre à jour les statistiques des étudiants
        Student::all()->each(function ($student) {
            $completedLessons = $student->lessons()->where('status', 'completed')->count();
            $totalSpent = $student->lessons()->where('status', 'completed')->sum('price');
            
            $student->update([
                'total_lessons' => $completedLessons,
                'total_spent' => $totalSpent
            ]);
        });

        echo "✅ Statistiques des étudiants mises à jour\n";
    }

    /**
     * Générer un rapport final
     */
    private function generateFinalReport()
    {
        echo "📋 Génération du rapport final...\n\n";

        $stats = [
            'Clubs' => Club::count(),
            'Enseignants' => Teacher::count(),
            'Étudiants' => Student::count(),
            'Types de cours' => CourseType::count(),
            'Lieux' => Location::count(),
            'Cours totaux' => Lesson::count(),
            'Cours terminés' => Lesson::where('status', 'completed')->count(),
            'Cours confirmés' => Lesson::where('status', 'confirmed')->count(),
            'Cours en attente' => Lesson::where('status', 'pending')->count(),
            'Cours annulés' => Lesson::where('status', 'cancelled')->count(),
            'Absences' => Lesson::where('status', 'no_show')->count(),
            'Paiements' => Payment::count(),
            'Cours de groupe' => Lesson::has('students', '>', 1)->count(),
            'Cours individuels' => Lesson::has('students', '=', 1)->count(),
        ];

        echo "📊 STATISTIQUES FINALES :\n";
        echo str_repeat("=", 50) . "\n";
        foreach ($stats as $label => $value) {
            printf("%-20s : %d\n", $label, $value);
        }
        echo str_repeat("=", 50) . "\n\n";

        // Statistiques par club
        echo "🏇 STATISTIQUES PAR CLUB :\n";
        echo str_repeat("-", 50) . "\n";
        Club::all()->each(function ($club) {
            $teachers = $club->users()->wherePivot('role', 'teacher')->count();
            $students = $club->users()->wherePivot('role', 'student')->count();
            $lessons = Lesson::whereHas('teacher.user.clubs', function($query) use ($club) {
                $query->where('clubs.id', $club->id);
            })->count();
            
            printf("%-20s : %d enseignants, %d étudiants, %d cours\n", 
                   $club->name, $teachers, $students, $lessons);
        });
        echo str_repeat("-", 50) . "\n\n";

        echo "🎯 ENVIRONNEMENT PRÊT POUR LES TESTS !\n";
        echo "Vous pouvez maintenant tester :\n";
        echo "• Dashboard des clubs avec statistiques réalistes\n";
        echo "• Gestion des enseignants et étudiants\n";
        echo "• Historique des cours et paiements\n";
        echo "• Cours à venir et planification\n";
        echo "• Cours de groupe et événements spéciaux\n";
        echo "• Système de notation et avis\n";
        echo "• Rapports et analyses\n\n";
    }

    /**
     * Créer un paiement pour un cours
     */
    private function createPaymentForLesson($lesson, $student)
    {
        $paymentMethods = ['card', 'bank_transfer', 'cash', 'paypal'];
        $paymentStatuses = ['succeeded', 'succeeded', 'succeeded', 'pending'];

        Payment::create([
            'lesson_id' => $lesson->id,
            'student_id' => $student->id,
            'amount' => $lesson->price,
            'currency' => 'EUR',
            'payment_method' => $paymentMethods[array_rand($paymentMethods)],
            'status' => $paymentStatuses[array_rand($paymentStatuses)],
            'processed_at' => $lesson->start_time->addDays(rand(0, 7)),
            'notes' => 'Paiement pour cours de ' . $lesson->courseType->name,
        ]);
    }

    /**
     * Générer des notes de cours
     */
    private function generateLessonNotes($status, $courseType, $type = 'individuel')
    {
        $typePrefix = $type === 'groupe' ? 'Cours de groupe - ' : '';
        
        $notes = [
            'completed' => [
                'Excellent cours, progression visible',
                'Bon travail sur la technique',
                'Élève motivé et attentif',
                'Amélioration notable depuis la dernière séance',
                'Cours productif, objectifs atteints',
                'Très bon niveau aujourd\'hui',
                'Élève en confiance, excellente séance',
                'Travail technique approfondi',
                'Progression constante observée',
                'Séance très satisfaisante'
            ],
            'confirmed' => [
                'Cours confirmé',
                'Rendez-vous confirmé',
                'Séance programmée',
                'Cours planifié',
                'Rendez-vous établi'
            ],
            'pending' => [
                'En attente de confirmation',
                'Cours en cours de planification',
                'Rendez-vous à confirmer',
                'Séance en attente',
                'Cours programmé'
            ],
            'cancelled' => [
                'Cours annulé par l\'élève',
                'Annulation pour cause de maladie',
                'Cours reporté',
                'Annulation pour imprévu',
                'Séance annulée'
            ],
            'no_show' => [
                'Élève absent sans prévenir',
                'Absence non justifiée',
                'Élève ne s\'est pas présenté',
                'Absence sans excuse',
                'No-show'
            ]
        ];

        $statusNotes = $notes[$status] ?? ['Note générique'];
        return $typePrefix . $statusNotes[array_rand($statusNotes)];
    }

    /**
     * Générer un feedback d'enseignant
     */
    private function generateTeacherFeedback()
    {
        $feedbacks = [
            'Élève très motivé et appliqué. Progression excellente.',
            'Bon travail sur la technique de base. Continuer dans cette direction.',
            'Élève en confiance, très bonne séance aujourd\'hui.',
            'Amélioration notable depuis la dernière fois. Bravo !',
            'Technique en progression, attitude positive.',
            'Élève attentif aux conseils, très bon potentiel.',
            'Séance productive, objectifs atteints.',
            'Très bon niveau aujourd\'hui, continuer ainsi.',
            'Élève sérieux et motivé, excellente progression.',
            'Travail technique approfondi, très satisfait.'
        ];

        return $feedbacks[array_rand($feedbacks)];
    }

    /**
     * Générer un avis d'étudiant
     */
    private function generateStudentReview()
    {
        $reviews = [
            'Excellent cours ! L\'enseignant est très pédagogue.',
            'Très satisfait de cette séance, j\'ai beaucoup appris.',
            'Cours de qualité, je recommande vivement.',
            'Enseignant patient et compétent, très bon cours.',
            'Progression visible, merci pour les conseils.',
            'Cours adapté à mon niveau, parfait !',
            'Enseignant professionnel, séance très instructive.',
            'Très bon cours, technique et pédagogie au top.',
            'Satisfait de la progression, cours de qualité.',
            'Enseignant à l\'écoute, excellente approche pédagogique.'
        ];

        return $reviews[array_rand($reviews)];
    }
}
