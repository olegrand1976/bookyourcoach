<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Student;
use App\Models\Club;
use App\Models\Teacher;
use App\Models\CourseType;
use App\Models\Location;
use App\Models\Lesson;
use App\Models\Subscription;
use App\Models\SubscriptionInstance;
use App\Models\SubscriptionTemplate;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class CreateTestStudent extends Command
{
    protected $signature = 'student:create-test 
                            {--email=student@test.com : Email de l\'étudiant}
                            {--password=password : Mot de passe}
                            {--name=Élève Test : Nom complet}';

    protected $description = 'Crée un compte étudiant avec abonnements et cours actifs pour tester l\'espace student';

    public function handle()
    {
        $email = $this->option('email');
        $password = $this->option('password');
        $name = $this->option('name');

        // Vérifier si l'utilisateur existe déjà
        $existingUser = User::where('email', $email)->first();
        if ($existingUser) {
            $this->info("✅ Utilisateur existant trouvé : {$email}");
            $user = $existingUser;
            $student = Student::where('user_id', $user->id)->first();
            
            if (!$student) {
                $this->error("❌ L'utilisateur existe mais n'a pas de profil étudiant");
                return 1;
            }
        } else {
            // Créer l'utilisateur
            $this->info("📝 Création de l'utilisateur...");
            $user = User::create([
                'name' => $name,
                'first_name' => explode(' ', $name)[0] ?? $name,
                'last_name' => explode(' ', $name)[1] ?? 'Test',
                'email' => $email,
                'password' => Hash::make($password),
                'role' => 'student',
                'email_verified_at' => now(),
                'status' => 'active',
                'is_active' => true,
            ]);

            // Récupérer ou créer un club
            $club = Club::first();
            if (!$club) {
                $this->error("❌ Aucun club trouvé. Veuillez d'abord créer un club.");
                return 1;
            }

            // Créer le profil étudiant
            $this->info("👤 Création du profil étudiant...");
            $student = Student::create([
                'user_id' => $user->id,
                'club_id' => $club->id,
                'date_of_birth' => Carbon::now()->subYears(15),
                'level' => 'intermediaire',
            ]);

            // Lier l'étudiant au club
            DB::table('club_students')->insert([
                'club_id' => $club->id,
                'student_id' => $student->id,
                'is_active' => true,
                'joined_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $club = $student->club ?? Club::first();
        if (!$club) {
            $this->error("❌ Aucun club trouvé pour l'étudiant.");
            return 1;
        }

        // Vérifier et créer des abonnements actifs
        $this->info("💳 Vérification des abonnements actifs...");
        $activeSubscriptions = SubscriptionInstance::whereHas('students', function($query) use ($student) {
            $query->where('students.id', $student->id);
        })->where('status', 'active')
          ->where('expires_at', '>', now())
          ->get();

        if ($activeSubscriptions->isEmpty()) {
            $this->info("📦 Création d'un abonnement actif...");
            
            // Récupérer ou créer un template d'abonnement
            $template = SubscriptionTemplate::where('club_id', $club->id)->first();
            if (!$template) {
                // Créer un template simple
                $template = SubscriptionTemplate::create([
                    'club_id' => $club->id,
                    'model_number' => 'TEMPLATE-DEV-001',
                    'total_lessons' => 10,
                    'free_lessons' => 0,
                    'validity_months' => 3,
                    'price' => 300.00,
                    'is_active' => true,
                ]);
            }

            // Créer l'abonnement
            DB::beginTransaction();
            try {
                $subscription = Subscription::createSafe([
                    'club_id' => $club->id,
                    'subscription_template_id' => $template->id,
                ]);

                // Créer l'instance d'abonnement
                $instance = SubscriptionInstance::create([
                    'subscription_id' => $subscription->id,
                    'lessons_used' => 2, // Quelques cours déjà utilisés
                    'started_at' => Carbon::now()->subDays(15),
                    'expires_at' => Carbon::now()->addMonths($template->validity_months ?? 3),
                    'status' => 'active',
                ]);
                
                // Calculer expires_at si non fourni
                if (!$instance->expires_at && method_exists($instance, 'calculateExpiresAt')) {
                    $instance->calculateExpiresAt();
                    $instance->save();
                }

                // Lier l'étudiant à l'instance
                DB::table('subscription_instance_students')->insert([
                    'subscription_instance_id' => $instance->id,
                    'student_id' => $student->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::commit();
                $this->info("✅ Abonnement créé : {$subscription->subscription_number}");
            } catch (\Exception $e) {
                DB::rollBack();
                $this->error("❌ Erreur lors de la création de l'abonnement: " . $e->getMessage());
            }
        } else {
            $this->info("✅ {$activeSubscriptions->count()} abonnement(s) actif(s) trouvé(s)");
        }

        // Vérifier et créer des cours actifs
        $this->info("📚 Vérification des cours actifs...");
        $activeLessons = Lesson::where('student_id', $student->id)
            ->where('status', '!=', 'cancelled')
            ->where('start_time', '>=', now())
            ->count();

        if ($activeLessons < 3) {
            $this->info("📅 Création de cours actifs...");
            
            // Récupérer un enseignant
            $teacher = Teacher::whereHas('clubs', function($query) use ($club) {
                $query->where('clubs.id', $club->id);
            })->first();

            if (!$teacher) {
                // Créer un enseignant de test
                $teacherUser = User::create([
                    'name' => 'Enseignant Test',
                    'first_name' => 'Enseignant',
                    'last_name' => 'Test',
                    'email' => 'teacher@test.com',
                    'password' => Hash::make('password'),
                    'role' => 'teacher',
                    'email_verified_at' => now(),
                    'status' => 'active',
                    'is_active' => true,
                ]);

                $teacher = Teacher::create([
                    'user_id' => $teacherUser->id,
                    'experience_years' => 5,
                    'hourly_rate' => 50,
                    'is_available' => true,
                ]);

                // Lier l'enseignant au club
                DB::table('club_teachers')->insert([
                    'club_id' => $club->id,
                    'teacher_id' => $teacher->id,
                    'is_active' => true,
                    'joined_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Récupérer un type de cours
            $courseType = CourseType::first();
            
            if (!$courseType) {
                // Créer un type de cours simple
                $discipline = \App\Models\Discipline::first();
                $courseType = CourseType::create([
                    'discipline_id' => $discipline?->id,
                    'name' => 'Cours individuel',
                    'duration_minutes' => 60,
                    'is_individual' => true,
                    'max_participants' => 1,
                    'is_active' => true,
                ]);
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

            // Créer plusieurs cours à venir
            for ($i = 1; $i <= 5; $i++) {
                $startTime = Carbon::now()->addDays($i * 2)->setTime(14, 0);
                
                Lesson::create([
                    'club_id' => $club->id,
                    'student_id' => $student->id,
                    'teacher_id' => $teacher->id,
                    'course_type_id' => $courseType->id,
                    'location_id' => $location->id,
                    'start_time' => $startTime,
                    'end_time' => $startTime->copy()->addMinutes($courseType->duration_minutes ?? 60),
                    'status' => $i <= 2 ? 'confirmed' : 'pending',
                    'price' => $courseType->price ?? 50.00,
                ]);
            }

            $this->info("✅ 5 cours créés");
        } else {
            $this->info("✅ {$activeLessons} cours actifs trouvés");
        }

        // Afficher le résumé
        $this->newLine();
        $this->info("═══════════════════════════════════════════════════════");
        $this->info("✅ Compte étudiant créé avec succès !");
        $this->info("═══════════════════════════════════════════════════════");
        $this->table(
            ['Champ', 'Valeur'],
            [
                ['Email', $user->email],
                ['Mot de passe', $password],
                ['Nom', $user->name],
                ['Club', $club->name ?? 'N/A'],
                ['Abonnements actifs', SubscriptionInstance::whereHas('students', function($q) use ($student) {
                    $q->where('students.id', $student->id);
                })->where('status', 'active')->where('expires_at', '>', now())->count()],
                ['Cours à venir', Lesson::where('student_id', $student->id)
                    ->where('status', '!=', 'cancelled')
                    ->where('start_time', '>=', now())->count()],
            ]
        );

        return 0;
    }

    private function generateUniqueSubscriptionNumber($clubId): string
    {
        $prefix = str_pad($clubId, 3, '0', STR_PAD_LEFT);
        $maxAttempts = 100;
        $attempt = 0;

        do {
            $number = $prefix . '-' . str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT);
            $exists = Subscription::where('subscription_number', $number)->exists();
            $attempt++;
        } while ($exists && $attempt < $maxAttempts);

        if ($attempt >= $maxAttempts) {
            throw new \Exception('Impossible de générer un numéro d\'abonnement unique');
        }

        return $number;
    }
}
