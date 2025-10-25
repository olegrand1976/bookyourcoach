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

class AdvancedLessonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        echo "🎯 Création d'un environnement de cours complet et réaliste...\n";

        // Récupérer les données existantes
        $clubs = Club::all();
        $courseTypes = CourseType::all();
        $locations = Location::all();
        
        if ($clubs->isEmpty() || $courseTypes->isEmpty() || $locations->isEmpty()) {
            echo "❌ Données manquantes. Veuillez d'abord exécuter les seeders de base.\n";
            return;
        }

        // Récupérer les enseignants et étudiants par club
        $teachersByClub = $this->getTeachersByClub();
        $studentsByClub = $this->getStudentsByClub();

        echo "📊 Données organisées par club :\n";
        foreach ($teachersByClub as $clubId => $teachers) {
            $club = $clubs->find($clubId);
            $students = $studentsByClub[$clubId] ?? collect();
            echo "- Club '{$club->name}': {$teachers->count()} enseignants, {$students->count()} étudiants\n";
        }

        // Créer des cours individuels
        $this->createIndividualLessons($teachersByClub, $studentsByClub, $courseTypes, $locations);

        // Créer des cours de groupe
        $this->createGroupLessons($teachersByClub, $studentsByClub, $courseTypes, $locations);

        // Créer des stages intensifs
        $this->createIntensiveCourses($teachersByClub, $studentsByClub, $courseTypes, $locations);

        // Créer des cours récurrents
        $this->createRecurringLessons($teachersByClub, $studentsByClub, $courseTypes, $locations);

        // Créer des événements spéciaux
        $this->createSpecialEvents($teachersByClub, $studentsByClub, $courseTypes, $locations);

        echo "✅ Environnement de cours complet créé avec succès !\n";
    }

    /**
     * Organiser les enseignants par club
     */
    private function getTeachersByClub()
    {
        $teachersByClub = collect();
        
        Teacher::with(['user', 'user.clubs'])->get()->each(function ($teacher) use ($teachersByClub) {
            $teacher->user->clubs->each(function ($club) use ($teacher, $teachersByClub) {
                if (!$teachersByClub->has($club->id)) {
                    $teachersByClub->put($club->id, collect());
                }
                $teachersByClub->get($club->id)->push($teacher);
            });
        });

        return $teachersByClub;
    }

    /**
     * Organiser les étudiants par club
     */
    private function getStudentsByClub()
    {
        $studentsByClub = collect();
        
        Student::with(['user', 'user.clubs'])->get()->each(function ($student) use ($studentsByClub) {
            $student->user->clubs->each(function ($club) use ($student, $studentsByClub) {
                if (!$studentsByClub->has($club->id)) {
                    $studentsByClub->put($club->id, collect());
                }
                $studentsByClub->get($club->id)->push($student);
            });
        });

        return $studentsByClub;
    }

    /**
     * Créer des cours individuels
     */
    private function createIndividualLessons($teachersByClub, $studentsByClub, $courseTypes, $locations)
    {
        echo "👤 Création des cours individuels...\n";

        $individualCourseTypes = $courseTypes->where('name', 'like', '%individuel%')
                                           ->orWhere('name', 'like', '%privé%')
                                           ->orWhere('name', 'like', '%personnel%');

        if ($individualCourseTypes->isEmpty()) {
            $individualCourseTypes = $courseTypes->take(2); // Prendre les 2 premiers si pas de cours individuels spécifiques
        }

        $lessonCount = 0;
        $paymentCount = 0;

        foreach ($teachersByClub as $clubId => $teachers) {
            $students = $studentsByClub[$clubId] ?? collect();
            
            if ($students->isEmpty()) continue;

            // Créer 20-30 cours individuels par club
            $lessonsToCreate = rand(20, 30);
            
            for ($i = 0; $i < $lessonsToCreate; $i++) {
                $teacher = $teachers->random();
                $student = $students->random();
                $courseType = $individualCourseTypes->random();
                $location = $locations->random();

                $lesson = $this->createLesson($teacher, $student, $courseType, $location, 'individual');
                
                if ($lesson) {
                    $lessonCount++;
                    
                    // Créer un paiement pour 85% des cours terminés
                    if ($lesson->status === 'completed' && rand(0, 100) > 15) {
                        $this->createPaymentForLesson($lesson, $student);
                        $paymentCount++;
                    }
                }
            }
        }

        echo "✅ {$lessonCount} cours individuels créés, {$paymentCount} paiements\n";
    }

    /**
     * Créer des cours de groupe
     */
    private function createGroupLessons($teachersByClub, $studentsByClub, $courseTypes, $locations)
    {
        echo "👥 Création des cours de groupe...\n";

        $groupCourseTypes = $courseTypes->where('name', 'like', '%groupe%')
                                      ->orWhere('name', 'like', '%collectif%')
                                      ->orWhere('name', 'like', '%équipe%');

        if ($groupCourseTypes->isEmpty()) {
            $groupCourseTypes = $courseTypes->skip(2)->take(2); // Prendre d'autres types si pas de cours de groupe spécifiques
        }

        $lessonCount = 0;

        foreach ($teachersByClub as $clubId => $teachers) {
            $students = $studentsByClub[$clubId] ?? collect();
            
            if ($students->count() < 2) continue; // Besoin d'au moins 2 étudiants pour un cours de groupe

            // Créer 10-15 cours de groupe par club
            $lessonsToCreate = rand(10, 15);
            
            for ($i = 0; $i < $lessonsToCreate; $i++) {
                $teacher = $teachers->random();
                $courseType = $groupCourseTypes->random();
                $location = $locations->random();

                // Sélectionner 2-4 étudiants pour le cours de groupe
                $groupStudents = $students->random(rand(2, min(4, $students->count())));
                $primaryStudent = $groupStudents->first();

                $lesson = $this->createLesson($teacher, $primaryStudent, $courseType, $location, 'group');
                
                if ($lesson) {
                    // Associer tous les étudiants du groupe au cours
                    foreach ($groupStudents as $student) {
                        $lesson->students()->attach($student->id, [
                            'status' => $lesson->status,
                            'price' => $lesson->price * 0.8, // Prix réduit pour les cours de groupe
                            'notes' => 'Cours de groupe - ' . $lesson->notes
                        ]);
                    }

                    $lessonCount++;
                }
            }
        }

        echo "✅ {$lessonCount} cours de groupe créés\n";
    }

    /**
     * Créer des stages intensifs
     */
    private function createIntensiveCourses($teachersByClub, $studentsByClub, $courseTypes, $locations)
    {
        echo "🏆 Création des stages intensifs...\n";

        $intensiveCourseTypes = $courseTypes->where('name', 'like', '%stage%')
                                          ->orWhere('name', 'like', '%intensif%')
                                          ->orWhere('name', 'like', '%weekend%');

        if ($intensiveCourseTypes->isEmpty()) {
            $intensiveCourseTypes = $courseTypes->take(1); // Prendre un type si pas de stages spécifiques
        }

        $lessonCount = 0;

        foreach ($teachersByClub as $clubId => $teachers) {
            $students = $studentsByClub[$clubId] ?? collect();
            
            if ($students->isEmpty()) continue;

            // Créer 3-5 stages par club
            $stagesToCreate = rand(3, 5);
            
            for ($i = 0; $i < $stagesToCreate; $i++) {
                $teacher = $teachers->random();
                $courseType = $intensiveCourseTypes->random();
                $location = $locations->random();

                // Stage sur 2-3 jours consécutifs
                $startDate = Carbon::now()->addDays(rand(10, 60));
                $startDate->setTime(9, 0); // Début à 9h

                for ($day = 0; $day < rand(2, 3); $day++) {
                    $currentDate = $startDate->copy()->addDays($day);
                    
                    // 2-3 sessions par jour
                    for ($session = 0; $session < rand(2, 3); $session++) {
                        $sessionStart = $currentDate->copy()->addHours(9 + $session * 3);
                        $sessionEnd = $sessionStart->copy()->addHours(2); // Sessions de 2h

                        $student = $students->random();
                        
                        $lesson = Lesson::create([
                            'teacher_id' => $teacher->id,
                            'student_id' => $student->id,
                            'course_type_id' => $courseType->id,
                            'location_id' => $location->id,
                            'start_time' => $sessionStart,
                            'end_time' => $sessionEnd,
                            'status' => 'confirmed',
                            'price' => ($courseType->price ?? 60) * 1.5, // Prix majoré pour les stages
                            'notes' => "Stage intensif - Jour " . ($day + 1) . " - Session " . ($session + 1),
                        ]);

                        $lesson->students()->attach($student->id, [
                            'status' => 'confirmed',
                            'price' => $lesson->price,
                            'notes' => $lesson->notes
                        ]);

                        $lessonCount++;
                    }
                }
            }
        }

        echo "✅ {$lessonCount} sessions de stages intensifs créées\n";
    }

    /**
     * Créer des cours récurrents
     */
    private function createRecurringLessons($teachersByClub, $studentsByClub, $courseTypes, $locations)
    {
        echo "🔄 Création des cours récurrents...\n";

        $lessonCount = 0;

        foreach ($teachersByClub as $clubId => $teachers) {
            $students = $studentsByClub[$clubId] ?? collect();
            
            if ($students->isEmpty()) continue;

            // Créer des séries de cours récurrents pour quelques étudiants
            $studentsForRecurring = $students->random(min(5, $students->count()));
            
            foreach ($studentsForRecurring as $student) {
                $teacher = $teachers->random();
                $courseType = $courseTypes->random();
                $location = $locations->random();

                // Créer une série de 8-12 cours hebdomadaires
                $seriesLength = rand(8, 12);
                $startDate = Carbon::now()->addDays(rand(1, 30));
                $startDate->setTime(rand(9, 17), rand(0, 3) * 15);

                for ($week = 0; $week < $seriesLength; $week++) {
                    $lessonDate = $startDate->copy()->addWeeks($week);
                    $endDate = $lessonDate->copy()->addMinutes($courseType->duration ?? 60);

                    $lesson = Lesson::create([
                        'teacher_id' => $teacher->id,
                        'student_id' => $student->id,
                        'course_type_id' => $courseType->id,
                        'location_id' => $location->id,
                        'start_time' => $lessonDate,
                        'end_time' => $endDate,
                        'status' => $week < 3 ? 'confirmed' : 'pending',
                        'price' => $courseType->price ?? 50,
                        'notes' => "Cours récurrent - Séance " . ($week + 1) . "/{$seriesLength}",
                    ]);

                    $lesson->students()->attach($student->id, [
                        'status' => $lesson->status,
                        'price' => $lesson->price,
                        'notes' => $lesson->notes
                    ]);

                    $lessonCount++;
                }
            }
        }

        echo "✅ {$lessonCount} cours récurrents créés\n";
    }

    /**
     * Créer des événements spéciaux
     */
    private function createSpecialEvents($teachersByClub, $studentsByClub, $courseTypes, $locations)
    {
        echo "🎉 Création des événements spéciaux...\n";

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

        $lessonCount = 0;

        foreach ($teachersByClub as $clubId => $teachers) {
            $students = $studentsByClub[$clubId] ?? collect();
            
            if ($students->isEmpty()) continue;

            // Créer 2-3 événements spéciaux par club
            $eventsToCreate = rand(2, 3);
            
            for ($i = 0; $i < $eventsToCreate; $i++) {
                $teacher = $teachers->random();
                $courseType = $courseTypes->random();
                $location = $locations->random();
                $eventName = $specialEvents[array_rand($specialEvents)];

                $eventDate = Carbon::now()->addDays(rand(15, 90));
                $eventDate->setTime(10, 0); // Événement à 10h
                $eventEnd = $eventDate->copy()->addHours(4); // Événement de 4h

                // Sélectionner plusieurs étudiants pour l'événement
                $eventStudents = $students->random(rand(3, min(8, $students->count())));
                $primaryStudent = $eventStudents->first();

                $lesson = Lesson::create([
                    'teacher_id' => $teacher->id,
                    'student_id' => $primaryStudent->id,
                    'course_type_id' => $courseType->id,
                    'location_id' => $location->id,
                    'start_time' => $eventDate,
                    'end_time' => $eventEnd,
                    'status' => 'confirmed',
                    'price' => ($courseType->price ?? 50) * 2, // Prix majoré pour les événements
                    'notes' => "Événement spécial : {$eventName}",
                ]);

                // Associer tous les étudiants à l'événement
                foreach ($eventStudents as $student) {
                    $lesson->students()->attach($student->id, [
                        'status' => 'confirmed',
                        'price' => $lesson->price * 0.7, // Prix réduit pour les événements de groupe
                        'notes' => "Participation à l'événement : {$eventName}"
                    ]);
                }

                $lessonCount++;
            }
        }

        echo "✅ {$lessonCount} événements spéciaux créés\n";
    }

    /**
     * Créer un cours individuel
     */
    private function createLesson($teacher, $student, $courseType, $location, $type = 'individual')
    {
        // Générer une date aléatoire dans les 6 derniers mois ou 3 prochains mois
        $isPast = rand(0, 1);
        if ($isPast) {
            $startTime = Carbon::now()->subDays(rand(1, 180));
        } else {
            $startTime = Carbon::now()->addDays(rand(1, 90));
        }

        $startTime->setTime(rand(8, 19), rand(0, 3) * 15);
        $endTime = $startTime->copy()->addMinutes($courseType->duration ?? 60);

        $statuses = $isPast ? 
            ['completed', 'completed', 'completed', 'cancelled', 'no_show'] : 
            ['pending', 'confirmed', 'confirmed', 'confirmed'];

        $status = $statuses[array_rand($statuses)];
        $price = $courseType->price ?? rand(40, 80);

        // Ajuster le prix selon le type
        if ($type === 'group') {
            $price *= 0.8; // Prix réduit pour les cours de groupe
        }

        $lesson = Lesson::create([
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'course_type_id' => $courseType->id,
            'location_id' => $location->id,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'status' => $status,
            'price' => $price,
            'notes' => $this->generateLessonNotes($status, $courseType->name, $type),
            'teacher_feedback' => $status === 'completed' ? $this->generateTeacherFeedback() : null,
            'rating' => $status === 'completed' ? rand(3, 5) : null,
            'review' => $status === 'completed' && rand(0, 100) > 30 ? $this->generateStudentReview() : null,
        ]);

        return $lesson;
    }

    /**
     * Créer un paiement pour un cours
     */
    private function createPaymentForLesson($lesson, $student)
    {
        $paymentMethods = ['card', 'cash', 'transfer', 'check'];
        $paymentStatuses = ['completed', 'completed', 'completed', 'pending'];

        Payment::create([
            'lesson_id' => $lesson->id,
            'student_id' => $student->id,
            'amount' => $lesson->price,
            'payment_method' => $paymentMethods[array_rand($paymentMethods)],
            'payment_status' => $paymentStatuses[array_rand($paymentStatuses)],
            'payment_date' => $lesson->start_time->addDays(rand(0, 7)),
            'transaction_id' => 'TXN_' . strtoupper(uniqid()),
            'notes' => 'Paiement pour cours de ' . $lesson->courseType->name,
        ]);
    }

    /**
     * Générer des notes de cours
     */
    private function generateLessonNotes($status, $courseType, $type = 'individual')
    {
        $typePrefix = $type === 'group' ? 'Cours de groupe - ' : '';
        
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
