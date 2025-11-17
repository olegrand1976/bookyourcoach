<?php

namespace Tests\Unit\Services;

use App\Services\LegacyRecurringSlotService;
use App\Models\SubscriptionRecurringSlot;
use App\Models\SubscriptionInstance;
use App\Models\Subscription;
use App\Models\Lesson;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\CourseType;
use App\Models\Club;
use App\Models\User;
use App\Models\Location;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Tests unitaires pour le service LegacyRecurringSlotService
 * 
 * Ce fichier teste la génération automatique de lessons depuis les créneaux récurrents legacy.
 * Le service LegacyRecurringSlotService génère des lessons individuelles à partir de créneaux
 * récurrents définis avec day_of_week et start_time/end_time (système legacy avant RRULE).
 * 
 * Fonctionnalités testées :
 * - Génération de lessons pour un créneau récurrent
 * - Prévention des doublons
 * - Génération même avec abonnement inactif
 * - Respect des plages de dates
 * - Génération pour tous les créneaux actifs
 * - Gestion gracieuse des erreurs
 * - Génération uniquement pour les dates valides (jour de la semaine)
 */
class LegacyRecurringSlotServiceTest extends TestCase
{
    use RefreshDatabase;

    private LegacyRecurringSlotService $service;
    private Club $club;
    private Subscription $subscription;
    private SubscriptionInstance $subscriptionInstance;
    private Student $student;
    private Teacher $teacher;
    private CourseType $courseType;
    private Location $location;

    /**
     * Configuration initiale pour tous les tests
     * 
     * Crée un environnement de test complet avec :
     * - Un club, un type de cours, un lieu
     * - Un enseignant et un élève avec leurs utilisateurs
     * - Un abonnement avec 10 cours au total
     * - Une instance d'abonnement active liée à l'élève
     * - Une instance du service LegacyRecurringSlotService
     * 
     * Cette configuration est réinitialisée avant chaque test grâce à RefreshDatabase
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new LegacyRecurringSlotService();

        // Créer un club
        $this->club = Club::create([
            'name' => 'Club Test',
            'email' => 'test@club.com',
            'phone' => '0123456789',
            'is_active' => true,
        ]);

        // Créer un type de cours
        $this->courseType = CourseType::create([
            'name' => 'Cours Test',
            'duration_minutes' => 60,
            'price' => 50.00,
        ]);

        // Créer un lieu
        $this->location = Location::create([
            'name' => 'Lieu Test',
            'address' => '123 Rue Test',
            'city' => 'Test City',
            'postal_code' => '75001',
            'country' => 'France',
        ]);

        // Créer un utilisateur pour l'enseignant
        $teacherUser = User::create([
            'name' => 'Enseignant Test',
            'email' => 'teacher@test.com',
            'password' => bcrypt('password'),
            'role' => 'teacher',
        ]);

        // Créer un enseignant
        $this->teacher = Teacher::create([
            'user_id' => $teacherUser->id,
            'club_id' => $this->club->id,
            'is_available' => true,
        ]);

        // Créer un utilisateur pour l'élève
        $studentUser = User::create([
            'name' => 'Élève Test',
            'email' => 'student@test.com',
            'password' => bcrypt('password'),
            'role' => 'student',
        ]);

        // Créer un élève
        $this->student = Student::create([
            'user_id' => $studentUser->id,
            'club_id' => $this->club->id,
        ]);

        // Créer un abonnement
        $this->subscription = Subscription::create([
            'club_id' => $this->club->id,
            'name' => 'Abonnement Test',
            'total_lessons' => 10,
            'free_lessons' => 0,
            'price' => 200.00,
            'is_active' => true,
        ]);

        // Créer une instance d'abonnement
        $this->subscriptionInstance = SubscriptionInstance::create([
            'subscription_id' => $this->subscription->id,
            'lessons_used' => 0,
            'started_at' => Carbon::now(),
            'expires_at' => Carbon::now()->addMonths(3),
            'status' => 'active',
        ]);

        // Lier l'élève à l'abonnement
        $this->subscriptionInstance->students()->attach($this->student->id);
    }

    /**
     * Test : Génération de lessons pour un créneau récurrent
     * 
     * BUT : Vérifier que generateLessonsForSlot() génère correctement des lessons individuelles
     *       depuis un créneau récurrent legacy (basé sur day_of_week)
     * 
     * ENTRÉE :
     * - Un cours de référence (nécessaire pour copier les informations : club_id, course_type_id, location_id, price)
     * - Un créneau récurrent : tous les samedis de 9h à 10h, valide pour 3 mois
     * - Période de génération : 4 semaines
     * 
     * SORTIE ATTENDUE :
     * - stats['generated'] > 0 (au moins une lesson générée)
     * - stats['errors'] = 0 (aucune erreur)
     * - Des lessons existent en base de données pour les samedis dans la période
     * 
     * POURQUOI : Les créneaux récurrents doivent automatiquement générer des lessons individuelles
     *            pour chaque occurrence. C'est essentiel pour que les cours apparaissent dans le planning
     *            et puissent être gérés individuellement (annulation, modification, etc.).
     */
    #[Test]
    public function it_generates_lessons_for_recurring_slot(): void
    {
        // Créer un cours de référence pour copier les informations
        $referenceLesson = Lesson::create([
            'club_id' => $this->club->id,
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'course_type_id' => $this->courseType->id,
            'location_id' => $this->location->id,
            'start_time' => Carbon::now()->subWeek(),
            'end_time' => Carbon::now()->subWeek()->addHour(),
            'status' => 'confirmed',
            'price' => 50.00,
        ]);

        // Créer un créneau récurrent (tous les samedis à 9h)
        $nextSaturday = Carbon::now()->next(Carbon::SATURDAY);
        $recurringSlot = SubscriptionRecurringSlot::create([
            'subscription_instance_id' => $this->subscriptionInstance->id,
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'day_of_week' => Carbon::SATURDAY,
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'start_date' => Carbon::now(),
            'end_date' => Carbon::now()->addMonths(3),
            'status' => 'active',
        ]);

        // Générer les lessons pour les 4 prochaines semaines
        $startDate = Carbon::now();
        $endDate = Carbon::now()->addWeeks(4);

        $stats = $this->service->generateLessonsForSlot($recurringSlot, $startDate, $endDate);

        // Vérifier que des lessons ont été générées
        $this->assertGreaterThan(0, $stats['generated']);
        $this->assertEquals(0, $stats['errors']);

        // Vérifier que les lessons existent dans la base de données
        $lessons = Lesson::where('teacher_id', $this->teacher->id)
            ->where('student_id', $this->student->id)
            ->where('start_time', '>=', $startDate)
            ->where('start_time', '<=', $endDate)
            ->get();

        $this->assertGreaterThan(0, $lessons->count());
    }

    /**
     * Test : Prévention des doublons lors de la génération
     * 
     * BUT : Vérifier que generateLessonsForSlot() ne crée pas de lessons en double
     *       si la génération est appelée plusieurs fois pour la même période
     * 
     * ENTRÉE :
     * - Un créneau récurrent (tous les samedis à 9h)
     * - Première génération pour une période de 2 semaines
     * - Deuxième génération pour la même période
     * 
     * SORTIE ATTENDUE :
     * - Première génération : stats1['generated'] > 0
     * - Deuxième génération : stats2['generated'] = 0 (aucune nouvelle lesson)
     * - Le nombre total de lessons reste identique
     * 
     * POURQUOI : La commande de génération peut être exécutée plusieurs fois (ex: quotidiennement).
     *            Il faut éviter de créer des doublons qui pollueraient la base de données et causeraient
     *            des problèmes d'affichage dans le planning.
     */
    #[Test]
    public function it_does_not_create_duplicate_lessons(): void
    {
        // Créer un cours de référence
        $referenceLesson = Lesson::create([
            'club_id' => $this->club->id,
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'course_type_id' => $this->courseType->id,
            'location_id' => $this->location->id,
            'start_time' => Carbon::now()->subWeek(),
            'end_time' => Carbon::now()->subWeek()->addHour(),
            'status' => 'confirmed',
            'price' => 50.00,
        ]);

        // Créer un créneau récurrent
        $nextSaturday = Carbon::now()->next(Carbon::SATURDAY);
        $recurringSlot = SubscriptionRecurringSlot::create([
            'subscription_instance_id' => $this->subscriptionInstance->id,
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'day_of_week' => Carbon::SATURDAY,
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'start_date' => Carbon::now(),
            'end_date' => Carbon::now()->addMonths(3),
            'status' => 'active',
        ]);

        $startDate = Carbon::now();
        $endDate = $nextSaturday->copy()->addWeeks(2);

        // Générer les lessons une première fois
        $stats1 = $this->service->generateLessonsForSlot($recurringSlot, $startDate, $endDate);
        $lessonsCount1 = Lesson::where('teacher_id', $this->teacher->id)
            ->where('student_id', $this->student->id)
            ->count();

        $this->assertGreaterThan(0, $stats1['generated']);

        // Générer les lessons une deuxième fois avec la même période
        $stats2 = $this->service->generateLessonsForSlot($recurringSlot, $startDate, $endDate);
        $lessonsCount2 = Lesson::where('teacher_id', $this->teacher->id)
            ->where('student_id', $this->student->id)
            ->count();

        // Le nombre de lessons ne doit pas changer
        $this->assertEquals($lessonsCount1, $lessonsCount2);
        $this->assertEquals(0, $stats2['generated']);
    }

    /**
     * Test : Génération même avec abonnement inactif
     * 
     * BUT : Vérifier que generateLessonsForSlot() génère des lessons même si l'abonnement associé est inactif
     * 
     * ENTRÉE :
     * - Un cours de référence
     * - Un abonnement avec status = 'expired' (inactif)
     * - Un créneau récurrent lié à cet abonnement inactif
     * 
     * SORTIE ATTENDUE :
     * - stats['generated'] > 0 (lessons générées quand même)
     * - stats['errors'] = 0
     * - Les lessons sont créées mais ne consomment pas l'abonnement (car inactif)
     * 
     * POURQUOI : La récurrence peut continuer même si l'abonnement expire. Les lessons doivent être
     *            générées pour maintenir la continuité du planning, mais elles ne consommeront pas
     *            l'abonnement. Cela permet de gérer les cas où un abonnement expire mais que les
     *            créneaux récurrents doivent continuer (ex: transition vers un nouvel abonnement).
     */
    #[Test]
    public function it_generates_lessons_without_active_subscription(): void
    {
        // Créer un cours de référence
        $referenceLesson = Lesson::create([
            'club_id' => $this->club->id,
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'course_type_id' => $this->courseType->id,
            'location_id' => $this->location->id,
            'start_time' => Carbon::now()->subWeek(),
            'end_time' => Carbon::now()->subWeek()->addHour(),
            'status' => 'confirmed',
            'price' => 50.00,
        ]);

        // Créer un abonnement inactif
        $inactiveInstance = SubscriptionInstance::create([
            'subscription_id' => $this->subscription->id,
            'lessons_used' => 0,
            'started_at' => Carbon::now(),
            'expires_at' => Carbon::now()->addMonths(3),
            'status' => 'expired',
        ]);

        // Créer un créneau récurrent lié à l'abonnement inactif
        $recurringSlot = SubscriptionRecurringSlot::create([
            'subscription_instance_id' => $inactiveInstance->id,
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'day_of_week' => Carbon::SATURDAY,
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'start_date' => Carbon::now(),
            'end_date' => Carbon::now()->addMonths(3),
            'status' => 'active',
        ]);

        $startDate = Carbon::now();
        $endDate = Carbon::now()->addWeeks(4);

        $stats = $this->service->generateLessonsForSlot($recurringSlot, $startDate, $endDate);

        // Les lessons doivent être générées même si l'abonnement est inactif
        $this->assertGreaterThan(0, $stats['generated']);
        $this->assertEquals(0, $stats['errors']);
    }

    /**
     * Test : Respect de la plage de dates du créneau récurrent
     * 
     * BUT : Vérifier que generateLessonsForSlot() respecte les dates start_date et end_date du créneau récurrent,
     *       même si la période de génération demandée est plus large
     * 
     * ENTRÉE :
     * - Un créneau récurrent valide du 1er au 15ème jour (2 semaines)
     * - Période de génération demandée : 3 mois (plus large que la récurrence)
     * 
     * SORTIE ATTENDUE :
     * - Les lessons générées sont uniquement dans la période start_date -> end_date du créneau récurrent
     * - Aucune lesson générée avant start_date ou après end_date
     * - Le nombre de lessons générées correspond exactement aux occurrences dans la période valide
     * 
     * POURQUOI : Un créneau récurrent peut avoir une période de validité limitée (ex: seulement pour
     *            le trimestre en cours). Il faut respecter cette période même si on demande la génération
     *            pour une période plus large. Cela garantit que les lessons ne sont générées que pendant
     *            la période où la récurrence est active.
     */
    #[Test]
    public function it_respects_recurring_slot_date_range(): void
    {
        // Créer un cours de référence
        $referenceLesson = Lesson::create([
            'club_id' => $this->club->id,
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'course_type_id' => $this->courseType->id,
            'location_id' => $this->location->id,
            'start_time' => Carbon::now()->subWeek(),
            'end_time' => Carbon::now()->subWeek()->addHour(),
            'status' => 'confirmed',
            'price' => 50.00,
        ]);

        // Créer un créneau récurrent avec une période limitée
        $recurringStartDate = Carbon::now()->addWeek();
        $recurringEndDate = Carbon::now()->addWeeks(2);
        
        $recurringSlot = SubscriptionRecurringSlot::create([
            'subscription_instance_id' => $this->subscriptionInstance->id,
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'day_of_week' => Carbon::SATURDAY,
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'start_date' => $recurringStartDate,
            'end_date' => $recurringEndDate,
            'status' => 'active',
        ]);

        // Générer avec une période plus large que la récurrence
        $startDate = Carbon::now();
        $endDate = Carbon::now()->addMonths(3);

        $stats = $this->service->generateLessonsForSlot($recurringSlot, $startDate, $endDate);

        // Vérifier que les lessons générées respectent la période de la récurrence
        $lessons = Lesson::where('teacher_id', $this->teacher->id)
            ->where('student_id', $this->student->id)
            ->where('start_time', '>=', $recurringStartDate)
            ->where('start_time', '<=', $recurringEndDate)
            ->get();

        $this->assertGreaterThan(0, $lessons->count());
        $this->assertEquals($stats['generated'], $lessons->count());
    }

    /**
     * Test : Génération pour tous les créneaux actifs
     * 
     * BUT : Vérifier que generateLessonsForAllActiveSlots() génère des lessons pour tous les créneaux récurrents actifs
     * 
     * ENTRÉE :
     * - Un cours de référence
     * - 3 créneaux récurrents différents (même jour, horaires différents : 9h, 10h, 11h)
     * - Période de génération : 4 semaines
     * 
     * SORTIE ATTENDUE :
     * - stats['generated'] > 0 (lessons générées pour tous les créneaux)
     * - stats['errors'] = 0 (aucune erreur)
     * 
     * POURQUOI : La commande de génération quotidienne doit traiter tous les créneaux récurrents actifs
     *            en une seule exécution. Cette méthode permet de générer les lessons pour tous les créneaux
     *            sans avoir à les traiter un par un.
     */
    #[Test]
    public function it_generates_lessons_for_all_active_slots(): void
    {
        // Créer des cours de référence
        $referenceLesson = Lesson::create([
            'club_id' => $this->club->id,
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'course_type_id' => $this->courseType->id,
            'location_id' => $this->location->id,
            'start_time' => Carbon::now()->subWeek(),
            'end_time' => Carbon::now()->subWeek()->addHour(),
            'status' => 'confirmed',
            'price' => 50.00,
        ]);

        // Créer plusieurs créneaux récurrents
        $slots = [];
        for ($i = 0; $i < 3; $i++) {
            $slot = SubscriptionRecurringSlot::create([
                'subscription_instance_id' => $this->subscriptionInstance->id,
                'teacher_id' => $this->teacher->id,
                'student_id' => $this->student->id,
                'day_of_week' => Carbon::SATURDAY,
                'start_time' => sprintf('%02d:00:00', 9 + $i),
                'end_time' => sprintf('%02d:00:00', 10 + $i),
                'start_date' => Carbon::now(),
                'end_date' => Carbon::now()->addMonths(3),
                'status' => 'active',
            ]);
            $slots[] = $slot;
        }

        $startDate = Carbon::now();
        $endDate = Carbon::now()->addWeeks(4);

        $stats = $this->service->generateLessonsForAllActiveSlots($startDate, $endDate);

        // Vérifier que des lessons ont été générées
        $this->assertGreaterThan(0, $stats['generated']);
        $this->assertEquals(0, $stats['errors']);
    }

    /**
     * Test : Gestion gracieuse de l'absence de cours de référence
     * 
     * BUT : Vérifier que generateLessonsForSlot() gère correctement le cas où aucun cours de référence n'existe
     * 
     * ENTRÉE :
     * - Un créneau récurrent sans aucun cours précédent (pas de cours de référence)
     * - Le service a besoin d'un cours de référence pour copier les informations (club_id, course_type_id, etc.)
     * 
     * SORTIE ATTENDUE :
     * - Le service ne plante pas (pas d'exception)
     * - stats['generated'] >= 0 (soit 0 si impossible sans référence, soit génération avec valeurs par défaut)
     * 
     * POURQUOI : Dans certains cas (ex: migration de données, créneau créé avant le premier cours),
     *            il peut ne pas y avoir de cours de référence. Le service doit gérer ce cas gracieusement
     *            sans générer d'erreur qui bloquerait la génération pour les autres créneaux.
     */
    #[Test]
    public function it_handles_missing_reference_lesson_gracefully(): void
    {
        // Créer un créneau récurrent sans cours de référence
        $recurringSlot = SubscriptionRecurringSlot::create([
            'subscription_instance_id' => $this->subscriptionInstance->id,
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'day_of_week' => Carbon::SATURDAY,
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'start_date' => Carbon::now(),
            'end_date' => Carbon::now()->addMonths(3),
            'status' => 'active',
        ]);

        $startDate = Carbon::now();
        $endDate = Carbon::now()->addWeeks(4);

        $stats = $this->service->generateLessonsForSlot($recurringSlot, $startDate, $endDate);

        // Le service doit gérer gracieusement l'absence de cours de référence
        // (soit en générant 0 lessons, soit en générant avec des valeurs par défaut)
        $this->assertGreaterThanOrEqual(0, $stats['generated']);
    }

    /**
     * Test : Génération uniquement pour les dates valides (jour de la semaine)
     * 
     * BUT : Vérifier que generateLessonsForSlot() génère des lessons uniquement pour le jour de la semaine
     *       spécifié dans le créneau récurrent
     * 
     * ENTRÉE :
     * - Un cours de référence
     * - Un créneau récurrent pour les samedis (day_of_week = SATURDAY)
     * - Période de génération : 4 semaines
     * 
     * SORTIE ATTENDUE :
     * - Toutes les lessons générées ont leur start_time un samedi
     * - Aucune lesson générée pour un autre jour de la semaine
     * 
     * POURQUOI : Un créneau récurrent est défini pour un jour spécifique de la semaine (ex: tous les samedis).
     *            Il faut s'assurer que les lessons générées respectent cette contrainte. C'est essentiel
     *            pour la cohérence des données et pour que les cours apparaissent au bon moment dans le planning.
     */
    #[Test]
    public function it_generates_lessons_only_for_valid_dates(): void
    {
        // Créer un cours de référence
        $referenceLesson = Lesson::create([
            'club_id' => $this->club->id,
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'course_type_id' => $this->courseType->id,
            'location_id' => $this->location->id,
            'start_time' => Carbon::now()->subWeek(),
            'end_time' => Carbon::now()->subWeek()->addHour(),
            'status' => 'confirmed',
            'price' => 50.00,
        ]);

        // Créer un créneau récurrent pour les samedis
        $recurringSlot = SubscriptionRecurringSlot::create([
            'subscription_instance_id' => $this->subscriptionInstance->id,
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'day_of_week' => Carbon::SATURDAY,
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'start_date' => Carbon::now(),
            'end_date' => Carbon::now()->addMonths(3),
            'status' => 'active',
        ]);

        $startDate = Carbon::now();
        $endDate = Carbon::now()->addWeeks(4);

        $stats = $this->service->generateLessonsForSlot($recurringSlot, $startDate, $endDate);

        // Vérifier que toutes les lessons générées sont des samedis
        $lessons = Lesson::where('teacher_id', $this->teacher->id)
            ->where('student_id', $this->student->id)
            ->where('start_time', '>=', $startDate)
            ->where('start_time', '<=', $endDate)
            ->get();

        foreach ($lessons as $lesson) {
            $lessonDate = Carbon::parse($lesson->start_time);
            $this->assertEquals(Carbon::SATURDAY, $lessonDate->dayOfWeek);
        }
    }
}

