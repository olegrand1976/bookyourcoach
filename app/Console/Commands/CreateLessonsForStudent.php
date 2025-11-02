<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Lesson;
use App\Models\Teacher;
use App\Models\CourseType;
use App\Models\Location;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CreateLessonsForStudent extends Command
{
    protected $signature = 'student:create-lessons {email} {count=10}';
    protected $description = 'Crée des cours pour un élève spécifique';

    public function handle()
    {
        $email = $this->argument('email');
        $count = (int) $this->argument('count');

        $user = User::where('email', $email)->first();
        
        if (!$user || !$user->student) {
            $this->error("Élève non trouvé : {$email}");
            return 1;
        }

        $student = $user->student;
        $this->info("✅ Élève trouvé : {$user->name} (ID: {$student->id})");

        // Récupérer le club de l'élève
        $clubId = $student->club_id;
        if (!$clubId) {
            $this->error("Aucun club associé à cet élève");
            return 1;
        }

        // Récupérer les enseignants du club
        $teachers = DB::table('club_teachers')
            ->where('club_teachers.club_id', $clubId)
            ->join('teachers', 'club_teachers.teacher_id', '=', 'teachers.id')
            ->join('users', 'teachers.user_id', '=', 'users.id')
            ->select('teachers.id', 'users.name')
            ->get();

        if ($teachers->isEmpty()) {
            $this->error("Aucun enseignant trouvé pour ce club");
            return 1;
        }

        // Récupérer les types de cours
        $courseTypes = CourseType::whereHas('discipline', function($q) use ($clubId) {
            // Vérifier si le club a cette discipline
        })->limit(5)->get();

        if ($courseTypes->isEmpty()) {
            $courseTypes = CourseType::limit(5)->get();
        }

        // Récupérer ou créer des locations
        $location = Location::first();
        if (!$location) {
            $location = Location::create([
                'name' => 'Manège principal',
                'address' => 'Adresse principale',
                'city' => 'Ville',
                'postal_code' => '00000',
                'country' => 'France'
            ]);
        }

        // Créer les cours
        $created = 0;
        $startDate = Carbon::now()->addDay();

        for ($i = 0; $i < $count; $i++) {
            $teacher = $teachers->random();
            $courseType = $courseTypes->random();
            
            $hour = 9 + ($i % 8); // Entre 9h et 16h
            $dayOffset = floor($i / 8); // Répartir sur plusieurs jours
            
            $startTime = $startDate->copy()->addDays($dayOffset)->setHour($hour)->setMinute(0);
            $endTime = $startTime->copy()->addMinutes($courseType->duration ?? 60);

            // Vérifier les conflits
            $hasConflict = Lesson::where('teacher_id', $teacher->id)
                ->whereBetween('start_time', [$startTime, $endTime])
                ->exists();

            if ($hasConflict) {
                continue;
            }

            $lesson = Lesson::create([
                'club_id' => $clubId,
                'teacher_id' => $teacher->id,
                'student_id' => $student->id,
                'course_type_id' => $courseType->id,
                'location_id' => $location->id,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'status' => $i < 3 ? 'confirmed' : 'pending',
                'price' => $courseType->price ?? 35.00,
                'payment_status' => 'pending',
            ]);

            $created++;
            $this->line("  ✓ Cours créé : {$startTime->format('d/m/Y H:i')} - {$teacher->name}");
        }

        $this->info("✅ {$created} cours créés pour {$user->name}");
        return 0;
    }
}

