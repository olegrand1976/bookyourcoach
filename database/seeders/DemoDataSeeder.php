<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Teacher;
use App\Models\Student;
use App\Models\CourseType;
use App\Models\Location;
use App\Models\Lesson;
use App\Models\Availability;
use App\Models\Payment;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class DemoDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $teachers = Teacher::with('user')->get();
        $students = Student::with('user')->get();
        $courseTypes = CourseType::all();
        $locations = Location::all();

        if ($teachers->isEmpty() || $students->isEmpty() || $courseTypes->isEmpty() || $locations->isEmpty()) {
            $this->command->warn('Veuillez d\'abord exécuter les autres seeders (Users, CourseTypes, Locations)');
            return;
        }

        // Créer des disponibilités pour les enseignants (2 semaines à venir)
        foreach ($teachers as $teacher) {
            $this->createAvailabilitiesForTeacher($teacher, $locations);
        }

        // Créer des leçons de démonstration
        $this->createDemoLessons($teachers, $students, $courseTypes, $locations);

        $this->command->info('Données de démonstration créées avec succès !');
    }

    private function createAvailabilitiesForTeacher(Teacher $teacher, $locations)
    {
        $startDate = Carbon::now()->startOfWeek();

        for ($week = 0; $week < 2; $week++) {
            for ($day = 1; $day <= 6; $day++) { // Lundi à Samedi
                $date = $startDate->copy()->addWeeks($week)->addDays($day - 1);

                // Matinée (9h-12h)
                if (rand(0, 100) > 30) { // 70% de chance d'être disponible
                    Availability::create([
                        'teacher_id' => $teacher->id,
                        'location_id' => $locations->random()->id,
                        'start_time' => $date->copy()->setTime(9, 0),
                        'end_time' => $date->copy()->setTime(12, 0),
                        'is_available' => true,
                    ]);
                }

                // Après-midi (14h-18h)
                if (rand(0, 100) > 25) { // 75% de chance d'être disponible
                    Availability::create([
                        'teacher_id' => $teacher->id,
                        'location_id' => $locations->random()->id,
                        'start_time' => $date->copy()->setTime(14, 0),
                        'end_time' => $date->copy()->setTime(18, 0),
                        'is_available' => true,
                    ]);
                }

                // Soirée (18h-20h) - moins fréquent
                if ($day <= 5 && rand(0, 100) > 60) { // 40% de chance, du lundi au vendredi seulement
                    Availability::create([
                        'teacher_id' => $teacher->id,
                        'location_id' => $locations->random()->id,
                        'start_time' => $date->copy()->setTime(18, 0),
                        'end_time' => $date->copy()->setTime(20, 0),
                        'is_available' => true,
                    ]);
                }
            }
        }
    }

    private function createDemoLessons($teachers, $students, $courseTypes, $locations)
    {
        $statuses = ['confirmed', 'completed', 'cancelled'];
        $lessonCount = 0;

        // Créer des leçons pour chaque étudiant
        foreach ($students as $student) {
            $teacherCount = rand(1, 3); // Chaque étudiant travaille avec 1 à 3 enseignants
            $selectedTeachers = $teachers->random($teacherCount);

            foreach ($selectedTeachers as $teacher) {
                $lessonCountForTeacher = rand(2, 8); // 2 à 8 leçons par enseignant

                for ($i = 0; $i < $lessonCountForTeacher; $i++) {
                    $courseType = $courseTypes->random();
                    $location = $locations->random();

                    // Date aléatoire dans les 30 derniers jours ou 15 prochains jours
                    $daysOffset = rand(-30, 15);
                    $lessonDate = Carbon::now()->addDays($daysOffset);

                    // Heure aléatoire entre 9h et 18h
                    $hour = rand(9, 17);
                    $minute = rand(0, 1) * 30; // 0 ou 30 minutes
                    $startTime = $lessonDate->copy()->setTime($hour, $minute);
                    $endTime = $startTime->copy()->addMinutes($courseType->duration);

                    // Statut basé sur la date
                    if ($lessonDate->isPast()) {
                        $status = rand(0, 100) > 10 ? 'completed' : 'cancelled'; // 90% completed, 10% cancelled
                    } else {
                        $status = 'confirmed';
                    }

                    $lesson = Lesson::create([
                        'club_id' => $teacher->club_id ?? \App\Models\Club::inRandomOrder()->first()?->id ?? 1,
                        'teacher_id' => $teacher->id,
                        'student_id' => $student->id,
                        'course_type_id' => $courseType->id,
                        'location_id' => $location->id,
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'status' => $status,
                        'price' => $courseType->price ?? 50.00,
                        'notes' => $this->generateLessonNotes($status, $courseType->name),
                    ]);

                    // Créer un paiement pour les leçons confirmées ou terminées
                    if (in_array($status, ['confirmed', 'completed']) && rand(0, 100) > 20) { // 80% ont un paiement
                        Payment::create([
                            'lesson_id' => $lesson->id,
                            'student_id' => $student->id,
                            'amount' => $lesson->price,
                            'stripe_payment_intent_id' => 'pi_demo_' . uniqid(),
                            'status' => $status === 'completed' ? 'succeeded' : 'pending',
                            'payment_method' => 'card',
                            'currency' => 'eur',
                        ]);
                    }

                    $lessonCount++;
                }
            }
        }

        $this->command->info("$lessonCount leçons de démonstration créées");
    }

    private function generateLessonNotes($status, $courseTypeName)
    {
        $notes = [
            'confirmed' => [
                "Leçon de $courseTypeName prévue. Venir 15 minutes avant le début.",
                "N'oubliez pas votre casque et vos bottes.",
                "Matériel fourni sur place.",
                "Prévoir des vêtements adaptés à la météo.",
            ],
            'completed' => [
                "Excellente progression en $courseTypeName. Continuer les exercices travaillés.",
                "Bonne séance de $courseTypeName. Travailler la position à la maison.",
                "Leçon productive. L'élève maîtrise mieux les transitions.",
                "Très bon travail aujourd'hui. Élève motivé et attentif.",
                "Séance concentrée sur la technique. Amélioration notable.",
            ],
            'cancelled' => [
                "Annulé pour cause de météo défavorable.",
                "Report demandé par l'élève - maladie.",
                "Annulation de dernière minute - urgence familiale.",
                "Report dû à un problème de transport.",
            ],
        ];

        return $notes[$status][array_rand($notes[$status])] ?? "Leçon de $courseTypeName";
    }
}
