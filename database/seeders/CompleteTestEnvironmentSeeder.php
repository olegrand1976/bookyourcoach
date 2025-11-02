<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use App\Models\Club;
use App\Models\User;
use App\Models\ClubOpenSlot;
use App\Models\Discipline;
use App\Models\CourseType;
use App\Models\Teacher;
use App\Models\Student;
use App\Models\Lesson;
use App\Models\SubscriptionTemplate;
use App\Models\Subscription;
use App\Models\SubscriptionInstance;
use App\Models\Location;

class CompleteTestEnvironmentSeeder extends Seeder
{
    /**
     * Créer un environnement de test complet avec :
     * - Créneaux horaires
     * - Cours liés aux créneaux
     * - Abonnements individuels et familiaux
     */
    public function run(): void
    {
        $this->command->info('🎯 Création d\'un environnement de test complet...');

        // 1. Récupérer ou créer le club "Centre Équestre des Étoiles"
        $club = Club::where('name', 'like', '%Centre Équestre des Étoiles%')
            ->orWhere('name', 'like', '%Étoiles%')
            ->first();

        if (!$club) {
            $this->command->warn('⚠️ Club "Centre Équestre des Étoiles" non trouvé, utilisation du premier club disponible...');
            $club = Club::first();
        }

        if (!$club) {
            $this->command->error('❌ Aucun club trouvé. Veuillez d\'abord créer un club.');
            return;
        }

        $this->command->info("✅ Club utilisé: {$club->name} (ID: {$club->id})");

        // 2. Récupérer les disciplines du club
        $clubDisciplines = [];
        if ($club->disciplines) {
            $disciplineIds = is_array($club->disciplines) ? $club->disciplines : json_decode($club->disciplines, true);
            if ($disciplineIds) {
                $clubDisciplines = Discipline::whereIn('id', $disciplineIds)->get();
            }
        }

        if ($clubDisciplines->isEmpty()) {
            $this->command->warn('⚠️ Aucune discipline configurée pour ce club, utilisation des premières disciplines disponibles...');
            $clubDisciplines = Discipline::limit(2)->get();
        }

        if ($clubDisciplines->isEmpty()) {
            $this->command->error('❌ Aucune discipline trouvée. Veuillez d\'abord créer des disciplines.');
            return;
        }

        $this->command->info("✅ Disciplines utilisées: " . $clubDisciplines->pluck('name')->join(', '));

        // 3. Créer des créneaux horaires
        $this->command->info('📅 Création des créneaux horaires...');
        $slots = $this->createOpenSlots($club, $clubDisciplines);
        $this->command->info("✅ {$slots->count()} créneaux créés");

        // 4. Récupérer ou créer des enseignants
        $teachers = $this->getOrCreateTeachers($club);
        $this->command->info("✅ {$teachers->count()} enseignants disponibles");

        // 5. Récupérer ou créer des élèves
        $students = $this->getOrCreateStudents($club);
        $this->command->info("✅ {$students->count()} élèves disponibles");

        // 6. Créer des cours liés aux créneaux
        $this->command->info('📚 Création des cours...');
        $lessons = $this->createLessons($club, $slots, $teachers, $students);
        $this->command->info("✅ {$lessons->count()} cours créés");

        // 7. Créer des modèles d'abonnements (individuels et familiaux)
        $this->command->info('💳 Création des modèles d\'abonnements...');
        $templates = $this->createSubscriptionTemplates($club, $clubDisciplines);
        $this->command->info("✅ {$templates->count()} modèles créés (individuels et familiaux)");

        // 8. Créer des abonnements pour les élèves
        $this->command->info('🎫 Création des abonnements...');
        $subscriptions = $this->createSubscriptions($club, $templates, $students);
        $this->command->info("✅ {$subscriptions->count()} abonnements créés");

        // 9. Lier certains cours aux abonnements
        $this->command->info('🔗 Liaison des cours aux abonnements...');
        $linkedLessons = $this->linkLessonsToSubscriptions($lessons, $subscriptions);
        $this->command->info("✅ {$linkedLessons} cours liés aux abonnements");

        $this->command->info('🎉 Environnement de test créé avec succès !');
    }

    /**
     * Créer des créneaux horaires variés
     */
    private function createOpenSlots($club, $disciplines)
    {
        // Supprimer les anciens créneaux pour ce club
        ClubOpenSlot::where('club_id', $club->id)->delete();

        $slots = collect();
        $disciplineIds = $disciplines->pluck('id')->toArray();

        // Créer des créneaux pour chaque jour de la semaine (sauf dimanche)
        $daysOfWeek = [1, 2, 3, 4, 5, 6]; // Lundi à Samedi
        $timeSlots = [
            ['09:00', '10:30'],
            ['10:30', '12:00'],
            ['14:00', '15:30'],
            ['15:30', '17:00'],
            ['17:00', '18:30'],
        ];

        $slotIndex = 0;
        foreach ($daysOfWeek as $day) {
            foreach ($timeSlots as $timeSlot) {
                // Alterner les disciplines
                $disciplineId = $disciplineIds[$slotIndex % count($disciplineIds)] ?? null;
                $discipline = $disciplines[$slotIndex % count($disciplines)];

                $slot = ClubOpenSlot::create([
                    'club_id' => $club->id,
                    'day_of_week' => $day,
                    'start_time' => $timeSlot[0],
                    'end_time' => $timeSlot[1],
                    'discipline_id' => $disciplineId,
                    'max_capacity' => 8,
                    'max_slots' => 1,
                    'duration' => 90,
                    'price' => 25.00,
                    'is_active' => true,
                ]);

                // Associer les types de cours à ce créneau
                $courseTypes = CourseType::where(function($query) use ($disciplineId) {
                        $query->whereNull('discipline_id')
                              ->orWhere('discipline_id', $disciplineId);
                    })
                    ->where('is_active', true)
                    ->limit(3)
                    ->pluck('id')
                    ->toArray();

                if (!empty($courseTypes)) {
                    $slot->courseTypes()->sync($courseTypes);
                }

                $slots->push($slot);
                $slotIndex++;
            }
        }

        return $slots;
    }

    /**
     * Récupérer ou créer des enseignants pour le club
     */
    private function getOrCreateTeachers($club)
    {
        $teachers = Teacher::whereHas('clubs', function($query) use ($club) {
            $query->where('clubs.id', $club->id);
        })->get();

        if ($teachers->isEmpty()) {
            $this->command->warn('⚠️ Aucun enseignant trouvé, création d\'enseignants de test...');
            
            // Créer 2 enseignants
            for ($i = 1; $i <= 2; $i++) {
                $user = User::create([
                    'first_name' => 'Enseignant',
                    'last_name' => "Test {$i}",
                    'name' => "Enseignant Test {$i}",
                    'email' => "enseignant{$i}@{$club->id}.test",
                    'password' => Hash::make('password'),
                    'role' => 'teacher',
                    'phone' => '06' . str_pad($i, 8, '0', STR_PAD_LEFT),
                ]);

                $teacher = Teacher::create([
                    'user_id' => $user->id,
                    'club_id' => $club->id,
                    'hourly_rate' => 24.00,
                    'experience_years' => 5 + $i,
                    'contract_type' => 'bénévole',
                    'is_active' => true,
                ]);

                // Lier au club
                DB::table('club_teachers')->insert([
                    'club_id' => $club->id,
                    'teacher_id' => $teacher->id,
                    'is_active' => true,
                    'joined_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $teachers->push($teacher);
            }
        }

        return $teachers;
    }

    /**
     * Récupérer ou créer des élèves pour le club
     */
    private function getOrCreateStudents($club)
    {
        $students = Student::whereHas('clubs', function($query) use ($club) {
            $query->where('clubs.id', $club->id);
        })->get();

        if ($students->isEmpty()) {
            $this->command->warn('⚠️ Aucun élève trouvé, création d\'élèves de test...');
            
            // Créer 5 élèves
            for ($i = 1; $i <= 5; $i++) {
                $user = User::create([
                    'first_name' => 'Élève',
                    'last_name' => "Test {$i}",
                    'name' => "Élève Test {$i}",
                    'email' => "eleve{$i}@{$club->id}.test",
                    'password' => Hash::make('password123'),
                    'role' => 'student',
                    'phone' => '06' . str_pad($i + 10, 8, '0', STR_PAD_LEFT),
                ]);

                $student = Student::create([
                    'user_id' => $user->id,
                    'club_id' => $club->id,
                    'date_of_birth' => Carbon::now()->subYears(10 + $i),
                ]);

                // Lier au club
                DB::table('club_students')->insert([
                    'club_id' => $club->id,
                    'student_id' => $student->id,
                    'is_active' => true,
                    'joined_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $students->push($student);
            }
        }

        return $students;
    }

    /**
     * Créer des cours liés aux créneaux
     */
    private function createLessons($club, $slots, $teachers, $students)
    {
        $lessons = collect();
        $teacherArray = $teachers->toArray();
        $studentArray = $students->toArray();

        if (empty($teacherArray) || empty($studentArray)) {
            $this->command->warn('⚠️ Pas assez d\'enseignants ou d\'élèves pour créer des cours');
            return collect();
        }

        // Créer un location si nécessaire (Location n'a pas de club_id, utiliser le premier disponible ou en créer un)
        $location = Location::first();
        if (!$location) {
            $location = Location::create([
                'name' => $club->name . ' - Piste principale',
                'address' => $club->address ?? $club->street ?? 'Adresse du club',
                'city' => $club->city ?? 'Ville',
                'postal_code' => $club->postal_code ?? '0000',
                'country' => $club->country ?? 'France',
            ]);
        }

        // Créer des cours pour les 4 prochaines semaines
        $startDate = Carbon::now()->startOfWeek();
        $weeksToCreate = 4;

        foreach ($slots as $slot) {
            for ($week = 0; $week < $weeksToCreate; $week++) {
                $date = $startDate->copy()->addWeeks($week);
                
                // Trouver le jour correspondant au day_of_week
                // day_of_week: 0=dimanche, 1=lundi, etc.
                $targetDay = $slot->day_of_week;
                $currentDay = $date->dayOfWeek; // 0=dimanche, 1=lundi, etc.
                
                // Ajuster la date pour correspondre au jour de la semaine
                $lessonDate = $date->copy()->addDays($targetDay - $currentDay);
                
                // Récupérer les types de cours associés au créneau
                $slotCourseTypes = $slot->courseTypes;
                if ($slotCourseTypes->isEmpty()) {
                    continue;
                }
                
                $courseType = $slotCourseTypes->random();

                // Calculer start_time et end_time
                $startTime = $slot->start_time;
                $endTime = $slot->end_time;
                
                // Extraire les heures et minutes du time string
                list($startHour, $startMinute) = explode(':', $startTime);
                list($endHour, $endMinute) = explode(':', $endTime);
                
                $startDateTime = $lessonDate->copy()->setTime((int)$startHour, (int)$startMinute, 0);
                $endDateTime = $lessonDate->copy()->setTime((int)$endHour, (int)$endMinute, 0);

                // Ne créer que si la date est dans le futur ou aujourd'hui
                if ($startDateTime->isPast() && !$startDateTime->isToday()) {
                    continue;
                }

                $teacher = $teachers->random();
                $student = $students->random();

                $lesson = Lesson::create([
                    'club_id' => $club->id,
                    'teacher_id' => $teacher->id,
                    'student_id' => $student->id,
                    'course_type_id' => $courseType->id,
                    'location_id' => $location->id,
                    'start_time' => $startDateTime,
                    'end_time' => $endDateTime,
                    'status' => $startDateTime->isPast() ? 'completed' : 'confirmed',
                    'price' => $slot->price ?? 25.00,
                    'notes' => "Cours lié au créneau {$slot->id} - " . ($slot->discipline ? $slot->discipline->name : 'N/A'),
                ]);

                $lessons->push($lesson);
            }
        }

        return $lessons;
    }

    /**
     * Créer des modèles d'abonnements (individuels et familiaux)
     */
    private function createSubscriptionTemplates($club, $disciplines)
    {
        $templates = collect();

        // Vérifier si les modèles existent déjà
        $existingIndividual = SubscriptionTemplate::where('club_id', $club->id)
            ->where('model_number', 'MOD-INDIV-001')
            ->first();
        
        $existingFamily = SubscriptionTemplate::where('club_id', $club->id)
            ->where('model_number', 'MOD-FAM-001')
            ->first();

        // Modèle individuel - 10 cours, 3 mois
        if ($existingIndividual) {
            $individualTemplate = $existingIndividual;
        } else {
            $individualTemplate = SubscriptionTemplate::create([
                'club_id' => $club->id,
                'model_number' => 'MOD-INDIV-001',
                'total_lessons' => 10,
                'free_lessons' => 0,
                'price' => 200.00,
                'validity_months' => 3,
                'validity_value' => 3,
                'validity_unit' => 'months',
                'is_active' => true,
            ]);
        }

        // Associer les types de cours
        $courseTypes = CourseType::where(function($query) use ($disciplines) {
                $disciplineIds = $disciplines->pluck('id')->toArray();
                $query->whereNull('discipline_id')
                      ->orWhereIn('discipline_id', $disciplineIds);
            })
            ->where('is_active', true)
            ->limit(5)
            ->pluck('id')
            ->toArray();

        if (!empty($courseTypes)) {
            $individualTemplate->courseTypes()->sync($courseTypes);
        }

        $templates->push($individualTemplate);

        // Modèle familial - 20 cours, 6 mois
        if ($existingFamily) {
            $familyTemplate = $existingFamily;
        } else {
            $familyTemplate = SubscriptionTemplate::create([
                'club_id' => $club->id,
                'model_number' => 'MOD-FAM-001',
                'total_lessons' => 20,
                'free_lessons' => 2, // 2 cours gratuits
                'price' => 350.00,
                'validity_months' => 6,
                'validity_value' => 6,
                'validity_unit' => 'months',
                'is_active' => true,
            ]);
        }

        if (!empty($courseTypes)) {
            $familyTemplate->courseTypes()->sync($courseTypes);
        }

        $templates->push($familyTemplate);

        return $templates;
    }

    /**
     * Créer des abonnements pour les élèves
     */
    private function createSubscriptions($club, $templates, $students)
    {
        $subscriptions = collect();
        
        if ($students->isEmpty() || $templates->isEmpty()) {
            return $subscriptions;
        }

        // Créer des abonnements individuels pour les 3 premiers élèves
        $individualTemplate = $templates->first();
        foreach ($students->take(3) as $index => $student) {
            DB::beginTransaction();
            try {
                // Générer un numéro d'abonnement unique avec verrou
                $subscriptionNumber = $this->generateUniqueSubscriptionNumber($club->id);
                
                $subscription = Subscription::create([
                    'club_id' => $club->id,
                    'subscription_template_id' => $individualTemplate->id,
                    'subscription_number' => $subscriptionNumber,
                ]);

                // Créer une instance d'abonnement
                $instance = SubscriptionInstance::create([
                    'subscription_id' => $subscription->id,
                    'lessons_used' => 0,
                    'started_at' => Carbon::now(),
                    'expires_at' => Carbon::now()->addMonths($individualTemplate->validity_months),
                    'status' => 'active',
                ]);

                // Lier l'élève à l'instance
                DB::table('subscription_instance_students')->insert([
                    'subscription_instance_id' => $instance->id,
                    'student_id' => $student->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::commit();
                $subscriptions->push($instance);
            } catch (\Exception $e) {
                DB::rollBack();
                $this->command->warn("⚠️ Erreur lors de la création de l'abonnement: " . $e->getMessage());
                continue;
            }
        }

        // Créer un abonnement familial pour 2 élèves
        if ($students->count() >= 4) {
            DB::beginTransaction();
            try {
                $familyTemplate = $templates->last();
                $familyStudents = $students->slice(3, 2);

                // Générer un numéro d'abonnement unique avec verrou
                $subscriptionNumber = $this->generateUniqueSubscriptionNumber($club->id);

                $subscription = Subscription::create([
                    'club_id' => $club->id,
                    'subscription_template_id' => $familyTemplate->id,
                    'subscription_number' => $subscriptionNumber,
                ]);

                $instance = SubscriptionInstance::create([
                    'subscription_id' => $subscription->id,
                    'lessons_used' => 0,
                    'started_at' => Carbon::now(),
                    'expires_at' => Carbon::now()->addMonths($familyTemplate->validity_months),
                    'status' => 'active',
                ]);

                // Lier les élèves à l'instance (abonnement familial)
                foreach ($familyStudents as $student) {
                    DB::table('subscription_instance_students')->insert([
                        'subscription_instance_id' => $instance->id,
                        'student_id' => $student->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                DB::commit();
                $subscriptions->push($instance);
            } catch (\Exception $e) {
                DB::rollBack();
                $this->command->warn("⚠️ Erreur lors de la création de l'abonnement familial: " . $e->getMessage());
            }
        }

        return $subscriptions;
    }

    /**
     * Lier certains cours aux abonnements
     */
    private function linkLessonsToSubscriptions($lessons, $subscriptions)
    {
        $linkedCount = 0;

        foreach ($subscriptions as $instance) {
            // Récupérer les élèves de cette instance
            $instanceStudents = DB::table('subscription_instance_students')
                ->where('subscription_instance_id', $instance->id)
                ->pluck('student_id')
                ->toArray();

            if (empty($instanceStudents)) {
                continue;
            }

            // Trouver les cours pour ces élèves
            $lessonsToLink = $lessons->filter(function($lesson) use ($instanceStudents) {
                return in_array($lesson->student_id, $instanceStudents);
            })->take(3); // Lier maximum 3 cours par abonnement

            foreach ($lessonsToLink as $lesson) {
                // Vérifier si déjà lié
                $exists = DB::table('subscription_lessons')
                    ->where('subscription_instance_id', $instance->id)
                    ->where('lesson_id', $lesson->id)
                    ->exists();

                if (!$exists) {
                    DB::table('subscription_lessons')->insert([
                        'subscription_instance_id' => $instance->id,
                        'lesson_id' => $lesson->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    // Mettre à jour lessons_used
                    $instance->increment('lessons_used');
                    $linkedCount++;
                }
            }
        }

        return $linkedCount;
    }

    /**
     * Générer un numéro d'abonnement unique pour un club
     */
    private function generateUniqueSubscriptionNumber($clubId): string
    {
        $now = Carbon::now();
        $yearMonth = $now->format('ym'); // Format AAMM (ex: 2511)
        
        // Trouver le dernier numéro pour ce mois et ce club
        $lastSubscription = Subscription::where('club_id', $clubId)
            ->where('subscription_number', 'like', $yearMonth . '-%')
            ->orderBy('subscription_number', 'desc')
            ->lockForUpdate()
            ->first();
        
        // Si un numéro existe, incrémenter
        if ($lastSubscription && $lastSubscription->subscription_number) {
            $parts = explode('-', $lastSubscription->subscription_number);
            if (count($parts) === 2) {
                $increment = (int) $parts[1];
                $increment++;
            } else {
                $increment = 1;
            }
        } else {
            $increment = 1;
        }
        
        $yearMonth .= '-' . str_pad($increment, 3, '0', STR_PAD_LEFT);
        
        // Vérifier l'unicité et réessayer si nécessaire
        $attempts = 0;
        $maxAttempts = 100;
        
        while (Subscription::where('subscription_number', $yearMonth)->exists() && $attempts < $maxAttempts) {
            $parts = explode('-', $yearMonth);
            $increment = (int) $parts[1];
            $increment++;
            $yearMonth = $parts[0] . '-' . str_pad($increment, 3, '0', STR_PAD_LEFT);
            $attempts++;
        }
        
        if ($attempts >= $maxAttempts) {
            throw new \Exception('Impossible de générer un numéro d\'abonnement unique');
        }
        
        return $yearMonth;
    }
}

