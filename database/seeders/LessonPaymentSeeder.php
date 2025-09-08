<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Lesson;
use App\Models\Payment;
use App\Models\Teacher;
use App\Models\Student;
use App\Models\Club;
use App\Models\CourseType;
use App\Models\Location;
use Carbon\Carbon;

class LessonPaymentSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('📚 Création des cours et paiements de test...');

        // Récupérer les données existantes
        $club = Club::where('name', 'Club Équestre de Test')->first();
        if (!$club) {
            $this->command->error('Club de test non trouvé. Exécutez d\'abord ClubTestDataSeeder.');
            return;
        }

        $teachers = Teacher::where('club_id', $club->id)->with('user')->get();
        $students = Student::where('club_id', $club->id)->with('user')->get();

        if ($teachers->isEmpty() || $students->isEmpty()) {
            $this->command->error('Enseignants ou étudiants non trouvés. Exécutez d\'abord ClubTestDataSeeder.');
            return;
        }

        // Créer des types de cours s'ils n'existent pas
        $courseTypes = [
            ['name' => 'Dressage', 'description' => 'Cours de dressage'],
            ['name' => 'Saut d\'obstacles', 'description' => 'Cours de saut d\'obstacles'],
            ['name' => 'Cross', 'description' => 'Cours de cross'],
            ['name' => 'Équitation de loisir', 'description' => 'Équitation de loisir'],
            ['name' => 'Initiation', 'description' => 'Cours d\'initiation']
        ];

        foreach ($courseTypes as $typeData) {
            CourseType::firstOrCreate(['name' => $typeData['name']], $typeData);
        }

        // Créer des lieux s'ils n'existent pas
        $locations = [
            [
                'name' => 'Manège couvert', 
                'address' => '123 Rue de l\'Équitation',
                'city' => 'Paris',
                'postal_code' => '75001',
                'country' => 'France'
            ],
            [
                'name' => 'Carrière extérieure', 
                'address' => '123 Rue de l\'Équitation',
                'city' => 'Paris',
                'postal_code' => '75001',
                'country' => 'France'
            ],
            [
                'name' => 'Paddock', 
                'address' => '123 Rue de l\'Équitation',
                'city' => 'Paris',
                'postal_code' => '75001',
                'country' => 'France'
            ]
        ];

        foreach ($locations as $locationData) {
            Location::firstOrCreate(['name' => $locationData['name']], $locationData);
        }

        $courseTypes = CourseType::all();
        $locations = Location::all();

        // Générer des cours sur les 3 derniers mois
        $lessonsCreated = 0;
        $paymentsCreated = 0;

        for ($i = 0; $i < 50; $i++) {
            $teacher = $teachers->random();
            $student = $students->random();
            $courseType = $courseTypes->random();
            $location = $locations->random();

            // Date aléatoire dans les 3 derniers mois
            $startTime = Carbon::now()->subMonths(3)->addDays(rand(0, 90))->addHours(rand(8, 18))->addMinutes(rand(0, 59));
            $endTime = $startTime->copy()->addHour();

            // Prix basé sur l'enseignant
            $price = $teacher->hourly_rate + rand(-10, 20);

            // Statut aléatoire avec plus de cours terminés
            $statuses = ['completed', 'completed', 'completed', 'confirmed', 'pending', 'cancelled'];
            $status = $statuses[array_rand($statuses)];

            $lesson = Lesson::create([
                'teacher_id' => $teacher->id,
                'student_id' => $student->id,
                'course_type_id' => $courseType->id,
                'location_id' => $location->id,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'price' => $price,
                'status' => $status,
                'notes' => 'Cours généré automatiquement - ' . $courseType->name . ' avec ' . $student->user->name,
                'rating' => $status === 'completed' ? rand(3, 5) : null,
                'created_at' => $startTime->subDays(rand(1, 30))
            ]);

            $lessonsCreated++;

            // Créer un paiement pour les cours terminés
            if ($status === 'completed') {
                $paymentStatuses = ['succeeded', 'succeeded', 'succeeded', 'pending', 'failed'];
                $paymentStatus = $paymentStatuses[array_rand($paymentStatuses)];

                Payment::create([
                    'lesson_id' => $lesson->id,
                    'student_id' => $student->id,
                    'amount' => $price,
                    'currency' => 'EUR',
                    'status' => $paymentStatus,
                    'payment_method' => 'card',
                    'stripe_payment_intent_id' => 'pi_test_' . rand(100000, 999999),
                    'processed_at' => $paymentStatus === 'succeeded' ? $startTime->addHour() : null,
                    'created_at' => $lesson->created_at
                ]);

                $paymentsCreated++;
            }
        }

        $this->command->info("✅ Cours et paiements créés avec succès !");
        $this->command->info("📊 Résumé :");
        $this->command->info("- {$lessonsCreated} cours créés");
        $this->command->info("- {$paymentsCreated} paiements créés");
        $this->command->info("- Répartis sur les 3 derniers mois");
    }
}