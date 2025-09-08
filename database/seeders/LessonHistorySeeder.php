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

class LessonHistorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        echo "📚 Création de l'historique complet des cours...\n";

        // Récupérer les données existantes
        $clubs = Club::all();
        $courseTypes = CourseType::all();
        $locations = Location::all();
        
        if ($clubs->isEmpty() || $courseTypes->isEmpty() || $locations->isEmpty()) {
            echo "❌ Données manquantes. Veuillez d'abord exécuter les seeders de base.\n";
            return;
        }

        // Récupérer les enseignants et étudiants associés aux clubs
        $teachers = Teacher::whereHas('user', function($query) {
            $query->whereHas('clubs');
        })->with(['user', 'user.clubs'])->get();

        $students = Student::whereHas('user', function($query) {
            $query->whereHas('clubs');
        })->with(['user', 'user.clubs'])->get();

        if ($teachers->isEmpty() || $students->isEmpty()) {
            echo "❌ Aucun enseignant ou étudiant associé aux clubs trouvé.\n";
            return;
        }

        echo "📊 Données trouvées :\n";
        echo "- " . $clubs->count() . " clubs\n";
        echo "- " . $courseTypes->count() . " types de cours\n";
        echo "- " . $locations->count() . " lieux\n";
        echo "- " . $teachers->count() . " enseignants\n";
        echo "- " . $students->count() . " étudiants\n";

        // Créer l'historique des cours (6 derniers mois)
        $this->createHistoricalLessons($teachers, $students, $courseTypes, $locations, $clubs);

        // Créer les cours à venir (3 prochains mois)
        $this->createUpcomingLessons($teachers, $students, $courseTypes, $locations, $clubs);

        echo "✅ Historique des cours créé avec succès !\n";
    }

    /**
     * Créer l'historique des cours passés
     */
    private function createHistoricalLessons($teachers, $students, $courseTypes, $locations, $clubs)
    {
        echo "📅 Création de l'historique des cours (6 derniers mois)...\n";

        $startDate = Carbon::now()->subMonths(6);
        $endDate = Carbon::now()->subDay();

        $statuses = ['completed', 'completed', 'completed', 'cancelled', 'no_show']; // Plus de cours terminés
        $ratings = [4, 5, 5, 4, 5, 3, 4, 5]; // Notes généralement bonnes

        $lessonCount = 0;
        $paymentCount = 0;

        // Créer environ 200 cours historiques
        for ($i = 0; $i < 200; $i++) {
            $teacher = $teachers->random();
            $student = $students->random();
            $courseType = $courseTypes->random();
            $location = $locations->random();
            $club = $clubs->random();

            // Vérifier que l'enseignant et l'étudiant sont dans le même club
            if (!$this->areInSameClub($teacher->user, $student->user)) {
                continue;
            }

            $startTime = Carbon::createFromTimestamp(
                rand($startDate->timestamp, $endDate->timestamp)
            );

            // Ajuster l'heure pour des créneaux réalistes (8h-20h)
            $startTime->setTime(rand(8, 19), rand(0, 3) * 15); // Créneaux de 15min
            $endTime = $startTime->copy()->addMinutes($courseType->duration ?? 60);

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
                'notes' => $this->generateLessonNotes($status, $courseType->name),
                'teacher_feedback' => $status === 'completed' ? $this->generateTeacherFeedback() : null,
                'rating' => $status === 'completed' ? $ratings[array_rand($ratings)] : null,
                'review' => $status === 'completed' && rand(0, 100) > 30 ? $this->generateStudentReview() : null,
            ]);

            // Associer l'étudiant au cours via la table pivot
            $lesson->students()->attach($student->id, [
                'status' => $status,
                'price' => $price,
                'notes' => $lesson->notes
            ]);

            // Créer un paiement pour les cours terminés
            if ($status === 'completed' && rand(0, 100) > 20) { // 80% ont un paiement
                $this->createPaymentForLesson($lesson, $student);
                $paymentCount++;
            }

            $lessonCount++;
        }

        echo "✅ " . $lessonCount . " cours historiques créés\n";
        echo "💰 " . $paymentCount . " paiements créés\n";
    }

    /**
     * Créer les cours à venir
     */
    private function createUpcomingLessons($teachers, $students, $courseTypes, $locations, $clubs)
    {
        echo "🔮 Création des cours à venir (3 prochains mois)...\n";

        $startDate = Carbon::now()->addDay();
        $endDate = Carbon::now()->addMonths(3);

        $statuses = ['pending', 'confirmed', 'confirmed', 'confirmed']; // Plus de cours confirmés
        $lessonCount = 0;

        // Créer environ 150 cours à venir
        for ($i = 0; $i < 150; $i++) {
            $teacher = $teachers->random();
            $student = $students->random();
            $courseType = $courseTypes->random();
            $location = $locations->random();
            $club = $clubs->random();

            // Vérifier que l'enseignant et l'étudiant sont dans le même club
            if (!$this->areInSameClub($teacher->user, $student->user)) {
                continue;
            }

            $startTime = Carbon::createFromTimestamp(
                rand($startDate->timestamp, $endDate->timestamp)
            );

            // Ajuster l'heure pour des créneaux réalistes (8h-20h)
            $startTime->setTime(rand(8, 19), rand(0, 3) * 15); // Créneaux de 15min
            $endTime = $startTime->copy()->addMinutes($courseType->duration ?? 60);

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
                'notes' => $this->generateLessonNotes($status, $courseType->name),
            ]);

            // Associer l'étudiant au cours via la table pivot
            $lesson->students()->attach($student->id, [
                'status' => $status,
                'price' => $price,
                'notes' => $lesson->notes
            ]);

            $lessonCount++;
        }

        echo "✅ " . $lessonCount . " cours à venir créés\n";
    }

    /**
     * Vérifier si l'enseignant et l'étudiant sont dans le même club
     */
    private function areInSameClub($teacher, $student)
    {
        $teacherClubs = $teacher->clubs()->pluck('clubs.id')->toArray();
        $studentClubs = $student->clubs()->pluck('clubs.id')->toArray();
        
        return !empty(array_intersect($teacherClubs, $studentClubs));
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
            'payment_date' => $lesson->start_time->addDays(rand(0, 7)), // Paiement dans les 7 jours
            'transaction_id' => 'TXN_' . strtoupper(uniqid()),
            'notes' => 'Paiement pour cours de ' . $lesson->courseType->name,
        ]);
    }

    /**
     * Générer des notes de cours
     */
    private function generateLessonNotes($status, $courseType)
    {
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
        return $statusNotes[array_rand($statusNotes)];
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
