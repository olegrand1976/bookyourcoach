<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Club;
use App\Models\Teacher;
use App\Models\Student;
use App\Models\Lesson;
use App\Models\CourseType;
use App\Models\Location;
use App\Models\SubscriptionInstance;
use App\Models\SubscriptionTemplate;
use App\Models\Subscription;

class CreateRecurringLessonsForTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🔄 Création d\'un cours récurrent avec séances futures pour test...');

        // 1. Trouver le club de l'admin
        $adminEmail = 'b.murgo1976@gmail.com';
        $admin = User::where('email', $adminEmail)->first();

        if (!$admin) {
            $this->command->error("❌ Utilisateur admin avec l'email {$adminEmail} introuvable");
            return;
        }

        $clubUser = DB::table('club_user')
            ->where('user_id', $admin->id)
            ->where(function ($query) {
                $query->where('role', 'owner')
                      ->orWhere('role', 'manager')
                      ->orWhere('is_admin', true);
            })
            ->first();

        if (!$clubUser) {
            $this->command->error("❌ Aucun club trouvé pour l'admin");
            return;
        }

        $club = Club::find($clubUser->club_id);
        if (!$club) {
            $this->command->error("❌ Club introuvable (ID: {$clubUser->club_id})");
            return;
        }

        $this->command->info("✅ Club trouvé: {$club->name} (ID: {$club->id})");

        // 2. Récupérer ou créer un élève
        $student = $this->getOrCreateTestStudent($club);
        $student->load('user');
        $studentName = ($student->user && $student->user->name) 
            ? $student->user->name 
            : (($student->first_name && $student->last_name) 
                ? ($student->first_name . ' ' . $student->last_name) 
                : 'Élève #' . $student->id);
        $this->command->info("✅ Élève: {$studentName} (ID: {$student->id})");

        // 3. Récupérer un enseignant
        $teacher = Teacher::where('club_id', $club->id)->with('user')->first();
        if (!$teacher) {
            $this->command->error("❌ Aucun enseignant trouvé pour ce club");
            return;
        }
        $teacherName = $teacher->user ? $teacher->user->name : 'Enseignant #' . $teacher->id;
        $this->command->info("✅ Enseignant: {$teacherName} (ID: {$teacher->id})");

        // 4. Récupérer un course type et une location
        $courseType = CourseType::first();
        
        if (!$courseType) {
            $this->command->error("❌ Aucun course_type trouvé");
            return;
        }
        
        // Récupérer ou créer une location (la table locations n'a peut-être pas club_id)
        $location = Location::first();
        if (!$location) {
            $location = Location::firstOrCreate(
                ['name' => 'Salle principale'],
                [
                    'address' => $club->address ?? 'Adresse du club',
                    'city' => $club->city ?? 'Bruxelles',
                    'postal_code' => $club->postal_code ?? '1000',
                    'country' => $club->country ?? 'Belgium',
                ]
            );
        }

        // 5. Créer un abonnement pour l'élève
        $subscriptionInstance = $this->createSubscriptionForStudent($club, $student);
        $this->command->info("✅ Abonnement créé (ID: {$subscriptionInstance->id})");

        // 6. Créer un cours aujourd'hui/ce soir et plusieurs cours futurs
        $lessonsCreated = $this->createRecurringLessonsForTest($club, $teacher, $student, $courseType, $location, $subscriptionInstance);
        $this->command->info("✅ {$lessonsCreated} cours créés (1 cours actuel + cours futurs)");

        $this->command->info('');
        $this->command->info('🎉 Cours récurrent créé avec succès !');
        $student->load('user');
        $teacher->load('user');
        $studentName = ($student->user && $student->user->name) 
            ? $student->user->name 
            : (($student->first_name && $student->last_name) 
                ? ($student->first_name . ' ' . $student->last_name) 
                : 'Élève #' . $student->id);
        $studentEmail = ($student->user && $student->user->email) ? $student->user->email : 'N/A';
        $teacherName = ($teacher->user && $teacher->user->name) ? $teacher->user->name : 'Enseignant #' . $teacher->id;
        
        $this->command->info('');
        $this->command->info('📋 Détails pour tester:');
        $this->command->info("   - Élève: {$studentName} ({$studentEmail})");
        $this->command->info("   - Enseignant: {$teacherName}");
        $this->command->info("   - Abonnement ID: {$subscriptionInstance->id}");
        $this->command->info("   - Total cours créés: {$lessonsCreated}");
        $this->command->info('');
        $this->command->info('💡 Pour tester la suppression des cours futurs:');
        $this->command->info('   1. Allez sur /club/planning');
        $this->command->info('   2. Trouvez un cours de ' . $studentName);
        $this->command->info('   3. Cliquez sur "Supprimer"');
        $this->command->info('   4. Vous devriez voir l\'option "Supprimer tous les cours futurs"');
    }

    private function getOrCreateTestStudent(Club $club): Student
    {
        // Chercher un élève existant dans le club
        $existingStudent = Student::where('club_id', $club->id)->first();

        if ($existingStudent) {
            return $existingStudent;
        }

        // Sinon, créer un nouvel élève de test
        $user = User::create([
            'name' => 'Élève Test Récurrent',
            'first_name' => 'Élève',
            'last_name' => 'Test Récurrent',
            'email' => 'eleve.recurrent' . $club->id . '@test.com',
            'password' => Hash::make('password'),
            'role' => 'student',
            'phone' => '+32 470 123456',
            'city' => $club->city ?? 'Bruxelles',
            'country' => $club->country ?? 'Belgium',
            'status' => 'active',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $student = Student::create([
            'user_id' => $user->id,
            'club_id' => $club->id,
            'first_name' => 'Élève',
            'last_name' => 'Test Récurrent',
            'phone' => $user->phone,
            'level' => 'intermediaire',
            'goals' => 'Progresser en équitation',
        ]);

        DB::table('club_students')->insert([
            'club_id' => $club->id,
            'student_id' => $student->id,
            'level' => 'intermediaire',
            'goals' => 'Progresser en équitation',
            'is_active' => true,
            'joined_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $student;
    }

    private function createSubscriptionForStudent(Club $club, Student $student): SubscriptionInstance
    {
        // Vérifier si l'élève a déjà un abonnement actif
        $existingInstance = SubscriptionInstance::where('status', 'active')
            ->whereHas('students', function ($query) use ($student) {
                $query->where('students.id', $student->id);
            })
            ->first();

        if ($existingInstance) {
            return $existingInstance;
        }

        // Créer un template d'abonnement simple si nécessaire
        $template = SubscriptionTemplate::where('club_id', $club->id)->first();
        
        if (!$template) {
            $template = SubscriptionTemplate::create([
                'club_id' => $club->id,
                'name' => 'Abonnement Test',
                'description' => 'Abonnement pour test de cours récurrents',
                'total_lessons' => 20,
                'validity_months' => 6,
                'price' => 300.00,
                'is_active' => true,
            ]);
        }

        // Créer la subscription
        $subscriptionNumber = 'SUB-' . $club->id . '-' . time();
        $subscription = Subscription::create([
            'club_id' => $club->id,
            'subscription_template_id' => $template->id,
            'subscription_number' => $subscriptionNumber,
        ]);

        // Créer l'instance d'abonnement
        $instance = SubscriptionInstance::create([
            'subscription_id' => $subscription->id,
            'lessons_used' => 0,
            'started_at' => Carbon::now(),
            'expires_at' => Carbon::now()->addMonths(6),
            'status' => 'active',
        ]);

        // Lier l'élève à l'instance
        DB::table('subscription_instance_students')->insert([
            'subscription_instance_id' => $instance->id,
            'student_id' => $student->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $instance;
    }

    private function createRecurringLessonsForTest(
        Club $club,
        Teacher $teacher,
        Student $student,
        CourseType $courseType,
        Location $location,
        SubscriptionInstance $subscriptionInstance
    ): int {
        $lessonsCreated = 0;
        
        // Créer un cours aujourd'hui ou ce soir (pour avoir un cours "actuel")
        $baseDate = Carbon::now();
        if ($baseDate->hour >= 18) {
            // Si on est après 18h, créer le cours pour demain
            $baseDate = Carbon::tomorrow();
        }
        
        // Heure de début (ex: 14h00)
        $baseDate->setTime(14, 0);
        
        // Créer 1 cours "actuel" (aujourd'hui ou demain) et 8 cours futurs (hebdomadaire)
        for ($week = 0; $week < 9; $week++) {
            $lessonDate = $baseDate->copy()->addWeeks($week);
            $endDate = $lessonDate->copy()->addHour(); // Durée: 1 heure

            // Créer le cours
            $lesson = Lesson::create([
                'club_id' => $club->id,
                'teacher_id' => $teacher->id,
                'student_id' => $student->id,
                'course_type_id' => $courseType->id,
                'location_id' => $location->id,
                'start_time' => $lessonDate,
                'end_time' => $endDate,
                'status' => 'confirmed',
                'price' => 50.00,
                'notes' => $week === 0 
                    ? 'Cours récurrent - Séance 1 (test suppression)' 
                    : "Cours récurrent - Séance " . ($week + 1) . " (futur)",
                'deduct_from_subscription' => true,
                'est_legacy' => false,
            ]);

            // Lier le cours à l'abonnement via la table pivot subscription_lessons
            // Cette table est utilisée par la relation subscriptionInstances() dans Lesson
            DB::table('subscription_lessons')->insert([
                'subscription_instance_id' => $subscriptionInstance->id,
                'lesson_id' => $lesson->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Lier l'étudiant au cours via lesson_student
            if (DB::getSchemaBuilder()->hasTable('lesson_student')) {
                DB::table('lesson_student')->insert([
                    'lesson_id' => $lesson->id,
                    'student_id' => $student->id,
                    'status' => 'confirmed',
                    'price' => 50.00,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $lessonsCreated++;
        }

        // Recharger l'instance pour mettre à jour les statistiques
        $subscriptionInstance->refresh();

        return $lessonsCreated;
    }
}
