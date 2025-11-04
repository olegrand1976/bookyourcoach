<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Club;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Models\SubscriptionTemplate;
use App\Models\Subscription;
use App\Models\SubscriptionInstance;
use App\Models\Lesson;
use App\Models\CourseType;
use App\Models\Location;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CreateTestSubscriptions extends Command
{
    protected $signature = 'test:subscriptions {club_email}';
    protected $description = 'Crée des abonnements de test avec des cours pour tester le recalcul';

    public function handle()
    {
        $clubEmail = $this->argument('club_email');
        
        // Trouver le club
        $user = User::where('email', $clubEmail)->where('role', 'club')->first();
        
        if (!$user) {
            $this->error("❌ Club non trouvé avec l'email: {$clubEmail}");
            return 1;
        }
        
        $club = $user->clubs()->first();
        
        if (!$club) {
            $this->error("❌ Aucun club associé à cet utilisateur");
            return 1;
        }
        
        $this->info("✅ Club trouvé: {$club->name} (ID: {$club->id})");
        
        DB::beginTransaction();
        
        try {
            // 1. Créer ou récupérer un élève de test
            $student = $this->getOrCreateStudent($club);
            $this->info("✅ Élève: {$student->user->name}");
            
            // 2. Créer ou récupérer un enseignant de test
            $teacher = $this->getOrCreateTeacher($club);
            $this->info("✅ Enseignant: {$teacher->user->name}");
            
            // 3. Créer ou récupérer un lieu de test
            $location = $this->getOrCreateLocation($club);
            $this->info("✅ Lieu: {$location->name}");
            
            // 4. Créer ou récupérer un type de cours
            $courseType = $this->getOrCreateCourseType($club);
            $this->info("✅ Type de cours: {$courseType->name}");
            
            // 5. Créer un modèle d'abonnement
            $template = SubscriptionTemplate::create([
                'club_id' => $club->id,
                'model_number' => 'TEST-' . uniqid(),
                'name' => 'Abonnement Test 10 cours',
                'total_lessons' => 10,
                'price' => 150.00,
                'validity_months' => 6,
                'is_active' => true,
            ]);
            
            // Associer le type de cours au template
            $template->courseTypes()->attach($courseType->id);
            
            $this->info("✅ Modèle d'abonnement créé: {$template->model_number}");
            
            // 6. Créer un abonnement
            $subscription = Subscription::create([
                'club_id' => $club->id,
                'subscription_template_id' => $template->id,
                'subscription_number' => 'SUB-TEST-' . uniqid(),
                'total_available_lessons' => $template->total_lessons,
                'validity_months' => $template->validity_months,
                'is_family_shared' => false,
                'max_family_members' => 1,
            ]);
            
            $this->info("✅ Abonnement créé: {$subscription->subscription_number}");
            
            // 7. Créer une instance d'abonnement
            $startedAt = Carbon::now()->subMonths(2);
            $expiresAt = Carbon::now()->addMonths(4);
            
            $instance = SubscriptionInstance::create([
                'subscription_id' => $subscription->id,
                'lessons_used' => 0, // On va mettre un mauvais compteur volontairement
                'started_at' => $startedAt,
                'expires_at' => $expiresAt,
                'status' => 'active',
            ]);
            
            // Associer l'élève à l'instance
            $instance->students()->attach($student->id);
            
            $this->info("✅ Instance créée (ID: {$instance->id}) - Début: {$startedAt->format('d/m/Y')} - Expire: {$expiresAt->format('d/m/Y')}");
            
            // 8. Créer des cours (certains confirmés, certains annulés)
            $this->info("\n📚 Création des cours...");
            
            $lessonsData = [
                ['status' => 'confirmed', 'days_ago' => 50],
                ['status' => 'confirmed', 'days_ago' => 43],
                ['status' => 'completed', 'days_ago' => 36],
                ['status' => 'confirmed', 'days_ago' => 29],
                ['status' => 'cancelled', 'days_ago' => 22], // Annulé - ne doit pas compter
                ['status' => 'completed', 'days_ago' => 15],
                ['status' => 'confirmed', 'days_ago' => 8],
                ['status' => 'confirmed', 'days_ago' => 1],
            ];
            
            $confirmedCount = 0;
            $cancelledCount = 0;
            
            foreach ($lessonsData as $lessonData) {
                $lessonDate = Carbon::now()->subDays($lessonData['days_ago']);
                
                $lesson = Lesson::create([
                    'club_id' => $club->id,
                    'teacher_id' => $teacher->id,
                    'student_id' => $student->id,
                    'course_type_id' => $courseType->id,
                    'location_id' => $location->id,
                    'start_time' => $lessonDate->setTime(10, 0),
                    'end_time' => $lessonDate->copy()->setTime(11, 0),
                    'status' => $lessonData['status'],
                    'price' => 15.00,
                    'notes' => 'Cours de test',
                ]);
                
                // Lier le cours à l'instance d'abonnement
                DB::table('subscription_lessons')->insert([
                    'subscription_instance_id' => $instance->id,
                    'lesson_id' => $lesson->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                
                if ($lessonData['status'] === 'cancelled') {
                    $cancelledCount++;
                    $this->info("   🔴 Cours {$lesson->id} - {$lessonDate->format('d/m/Y')} - ANNULÉ (ne doit pas compter)");
                } else {
                    $confirmedCount++;
                    $this->info("   ✅ Cours {$lesson->id} - {$lessonDate->format('d/m/Y')} - {$lessonData['status']}");
                }
            }
            
            // 9. Mettre volontairement un mauvais compteur pour tester le recalcul
            $wrongCount = 3; // On met 3 alors qu'il devrait être 7
            DB::table('subscription_instances')
                ->where('id', $instance->id)
                ->update(['lessons_used' => $wrongCount]);
            
            DB::commit();
            
            $this->info("\n" . str_repeat("=", 60));
            $this->info("✅ STRUCTURE DE TEST CRÉÉE AVEC SUCCÈS !");
            $this->info(str_repeat("=", 60));
            $this->info("📊 Résumé:");
            $this->info("   • Club: {$club->name}");
            $this->info("   • Élève: {$student->user->name}");
            $this->info("   • Abonnement: {$subscription->subscription_number}");
            $this->info("   • Instance ID: {$instance->id}");
            $this->info("   • Période: {$startedAt->format('d/m/Y')} → {$expiresAt->format('d/m/Y')}");
            $this->info("   • Total cours: {$template->total_lessons}");
            $this->info("   • Cours créés: " . count($lessonsData) . " ({$confirmedCount} comptés + {$cancelledCount} annulés)");
            $this->info("");
            $this->warn("⚠️  COMPTEUR VOLONTAIREMENT FAUX:");
            $this->info("   • Valeur actuelle dans la DB: {$wrongCount}");
            $this->info("   • Valeur attendue après recalcul: {$confirmedCount}");
            $this->info("   • Différence: +" . ($confirmedCount - $wrongCount));
            $this->info("");
            $this->info("🧪 Pour tester le recalcul:");
            $this->info("   1. Allez sur /club/subscriptions");
            $this->info("   2. Cliquez sur 'Recalculer les Cours Restants'");
            $this->info("   3. Vérifiez que le compteur passe de {$wrongCount} à {$confirmedCount}");
            $this->info(str_repeat("=", 60));
            
            return 0;
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("❌ Erreur: " . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
    }
    
    private function getOrCreateStudent(Club $club): Student
    {
        $user = User::where('email', 'test.student@example.com')->first();
        
        if (!$user) {
            $user = User::create([
                'name' => 'Test Student',
                'email' => 'test.student@example.com',
                'password' => bcrypt('password'),
                'role' => 'student',
            ]);
        }
        
        $student = Student::where('user_id', $user->id)->first();
        
        if (!$student) {
            $student = Student::create([
                'user_id' => $user->id,
                'club_id' => $club->id,
            ]);
        }
        
        return $student;
    }
    
    private function getOrCreateTeacher(Club $club): Teacher
    {
        $user = User::where('email', 'test.teacher@example.com')->first();
        
        if (!$user) {
            $user = User::create([
                'name' => 'Test Teacher',
                'email' => 'test.teacher@example.com',
                'password' => bcrypt('password'),
                'role' => 'teacher',
            ]);
        }
        
        $teacher = Teacher::where('user_id', $user->id)->first();
        
        if (!$teacher) {
            $teacher = Teacher::create([
                'user_id' => $user->id,
                'club_id' => $club->id,
                'specialties' => ['test'],
            ]);
        }
        
        return $teacher;
    }
    
    private function getOrCreateLocation(Club $club): Location
    {
        $location = Location::where('club_id', $club->id)->where('name', 'LIKE', 'Test%')->first();
        
        if (!$location) {
            $location = Location::create([
                'club_id' => $club->id,
                'name' => 'Test Location',
                'address' => '123 Test Street',
                'city' => 'Test City',
                'postal_code' => '12345',
                'is_active' => true,
            ]);
        }
        
        return $location;
    }
    
    private function getOrCreateCourseType(Club $club): CourseType
    {
        // Récupérer le premier type de cours actif du club ou un type générique
        $courseType = CourseType::where(function($q) use ($club) {
            $q->where('club_id', $club->id)
              ->orWhereNull('club_id');
        })
        ->where('is_active', true)
        ->first();
        
        if (!$courseType) {
            $this->warn("⚠️  Aucun type de cours trouvé. Création d'un type de test...");
            
            // Trouver une discipline
            $discipline = \App\Models\Discipline::first();
            
            if (!$discipline) {
                throw new \Exception("Aucune discipline trouvée dans la base de données. Créez-en une d'abord.");
            }
            
            $courseType = CourseType::create([
                'club_id' => $club->id,
                'discipline_id' => $discipline->id,
                'name' => 'Cours Test',
                'description' => 'Type de cours pour les tests',
                'duration_minutes' => 60,
                'price' => 15.00,
                'is_individual' => true,
                'max_participants' => 1,
                'is_active' => true,
            ]);
        }
        
        return $courseType;
    }
}

