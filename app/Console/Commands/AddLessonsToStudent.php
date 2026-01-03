<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Student;
use App\Models\Club;
use App\Models\Teacher;
use App\Models\CourseType;
use App\Models\Location;
use App\Models\Lesson;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AddLessonsToStudent extends Command
{
    protected $signature = 'student:add-lessons 
                            {email=student@test.com : Email de l\'étudiant}
                            {count=10 : Nombre de cours à ajouter}';

    protected $description = 'Ajoute des cours supplémentaires à un étudiant existant';

    public function handle()
    {
        $email = $this->argument('email');
        $count = (int) $this->argument('count');

        // Trouver l'utilisateur
        $user = User::where('email', $email)->first();
        if (!$user) {
            $this->error("❌ Utilisateur non trouvé : {$email}");
            return 1;
        }

        $student = Student::where('user_id', $user->id)->first();
        if (!$student) {
            $this->error("❌ Profil étudiant non trouvé pour {$email}");
            return 1;
        }

        $this->info("✅ Étudiant trouvé : {$user->name} ({$email})");

        // Récupérer le club de l'étudiant
        $club = $student->club ?? Club::first();
        if (!$club) {
            $this->error("❌ Aucun club trouvé pour l'étudiant.");
            return 1;
        }

        // Récupérer un enseignant
        $teacher = Teacher::whereHas('clubs', function($query) use ($club) {
            $query->where('clubs.id', $club->id);
        })->first();

        if (!$teacher) {
            $this->error("❌ Aucun enseignant trouvé pour le club.");
            return 1;
        }

        // Récupérer un type de cours
        $courseType = CourseType::first();
        if (!$courseType) {
            $this->error("❌ Aucun type de cours trouvé.");
            return 1;
        }

        // Récupérer ou créer un lieu
        $location = Location::first();
        if (!$location) {
            $location = Location::create([
                'name' => 'Manège principal',
                'address' => '1 Rue du Test',
                'city' => 'Test',
                'postal_code' => '75000',
                'country' => 'France',
            ]);
        }

        // Créer les cours
        $this->info("📅 Création de {$count} cours...");
        $created = 0;
        
        for ($i = 1; $i <= $count; $i++) {
            // Créer des cours sur les prochaines semaines (2-3 cours par semaine)
            $weekOffset = floor(($i - 1) / 3); // Nouvelle semaine tous les 3 cours
            $dayInWeek = (($i - 1) % 3) * 2 + 1; // Jours espacés (1, 3, 5)
            $hour = 14 + (($i - 1) % 3); // Heures différentes (14h, 15h, 16h)
            
            $startTime = Carbon::now()
                ->addWeeks($weekOffset)
                ->next(Carbon::MONDAY)
                ->addDays($dayInWeek)
                ->setTime($hour, 0);
            
            try {
                Lesson::create([
                    'club_id' => $club->id,
                    'student_id' => $student->id,
                    'teacher_id' => $teacher->id,
                    'course_type_id' => $courseType->id,
                    'location_id' => $location->id,
                    'start_time' => $startTime,
                    'end_time' => $startTime->copy()->addMinutes($courseType->duration_minutes ?? 60),
                    'status' => $i <= 3 ? 'confirmed' : 'pending',
                    'price' => $courseType->price ?? 50.00,
                ]);
                $created++;
            } catch (\Exception $e) {
                $this->warn("⚠️ Erreur lors de la création du cours {$i}: " . $e->getMessage());
            }
        }

        $this->info("✅ {$created} cours créés avec succès !");
        
        // Afficher le résumé
        $totalLessons = Lesson::where('student_id', $student->id)
            ->where('status', '!=', 'cancelled')
            ->where('start_time', '>=', now())
            ->count();

        $this->newLine();
        $this->info("═══════════════════════════════════════════════════════");
        $this->info("✅ Cours ajoutés avec succès !");
        $this->info("═══════════════════════════════════════════════════════");
        $this->table(
            ['Champ', 'Valeur'],
            [
                ['Email', $user->email],
                ['Cours créés', $created],
                ['Total cours à venir', $totalLessons],
            ]
        );

        return 0;
    }
}
