<?php

namespace Tests\Unit\Console\Commands;

use App\Models\SubscriptionInstance;
use App\Models\Subscription;
use App\Models\Lesson;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\CourseType;
use App\Models\Discipline;
use App\Models\Club;
use App\Models\User;
use App\Models\Location;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Tests unitaires pour la commande ConsumePastLessonsCommand
 * 
 * Ce fichier teste la commande qui consomme automatiquement les cours passés des abonnements.
 * La commande subscriptions:consume-past-lessons est exécutée toutes les heures via le scheduler.
 * 
 * Fonctionnalités testées :
 * - Consommation des cours passés uniquement
 * - Non-consommation des cours futurs
 * - Traitement de plusieurs instances d'abonnement
 * - Exclusion des cours annulés
 * - Traitement uniquement des abonnements actifs
 * - Gestion des abonnements sans cours passés
 * - Mise à jour automatique du statut quand l'abonnement devient complet
 */
class ConsumePastLessonsCommandTest extends TestCase
{
    use RefreshDatabase;

    private Club $club;
    private Subscription $subscription;
    private SubscriptionInstance $subscriptionInstance;
    private Student $student;
    private Teacher $teacher;
    private CourseType $courseType;
    private Location $location;

    protected function setUp(): void
    {
        parent::setUp();

        // Créer un club
        $this->club = Club::create([
            'name' => 'Club Test',
            'email' => 'test@club.com',
            'phone' => '0123456789',
            'is_active' => true,
        ]);

        // Créer une discipline
        $discipline = Discipline::create([
            'name' => 'Discipline Test',
            'slug' => 'discipline-test',
            'is_active' => true,
        ]);

        // Créer un type de cours
        $this->courseType = CourseType::create([
            'name' => 'Cours Test',
            'duration_minutes' => 60,
            'price' => 50.00,
            'discipline_id' => $discipline->id,
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
     * Test : Consommation des cours passés uniquement
     * 
     * BUT : Vérifier que la commande consomme uniquement les cours dont la date/heure est passée
     * 
     * ENTRÉE :
     * - 2 cours passés (il y a 2 jours et hier)
     * - 1 cours futur (demain)
     * - Tous les cours attachés à l'abonnement
     * - lessons_used initial = 0
     * 
     * SORTIE ATTENDUE :
     * - La commande s'exécute avec succès (code de retour = 0)
     * - lessons_used = 2 (seulement les 2 cours passés sont consommés)
     * - Le cours futur n'est pas consommé
     * 
     * POURQUOI : Les cours futurs ne doivent pas consommer l'abonnement tant qu'ils ne sont pas passés.
     *            Cette commande permet de mettre à jour automatiquement lessons_used pour les cours
     *            dont la date/heure est passée, garantissant que seuls les cours réellement effectués
     *            consomment l'abonnement.
     */
    #[Test]
    public function it_consumes_past_lessons(): void
    {
        // Créer des cours passés et futurs attachés à l'abonnement
        $pastLesson1 = Lesson::create([
            'club_id' => $this->club->id,
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'course_type_id' => $this->courseType->id,
            'location_id' => $this->location->id,
            'start_time' => Carbon::now()->subDays(2),
            'end_time' => Carbon::now()->subDays(2)->addHour(),
            'status' => 'confirmed',
            'price' => 50.00,
        ]);

        $pastLesson2 = Lesson::create([
            'club_id' => $this->club->id,
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'course_type_id' => $this->courseType->id,
            'location_id' => $this->location->id,
            'start_time' => Carbon::now()->subDay(),
            'end_time' => Carbon::now()->subDay()->addHour(),
            'status' => 'confirmed',
            'price' => 50.00,
        ]);

        $futureLesson = Lesson::create([
            'club_id' => $this->club->id,
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'course_type_id' => $this->courseType->id,
            'location_id' => $this->location->id,
            'start_time' => Carbon::now()->addDay(),
            'end_time' => Carbon::now()->addDay()->addHour(),
            'status' => 'confirmed',
            'price' => 50.00,
        ]);

        // Attacher tous les cours à l'abonnement
        $this->subscriptionInstance->lessons()->attach([
            $pastLesson1->id,
            $pastLesson2->id,
            $futureLesson->id,
        ]);

        // Initialement, lessons_used doit être 0 (cours futurs non consommés)
        $this->assertEquals(0, $this->subscriptionInstance->lessons_used);

        // Exécuter la commande via Artisan::call pour avoir la sortie configurée
        $result = Artisan::call('subscriptions:consume-past-lessons');

        // Vérifier que la commande s'est exécutée avec succès
        $this->assertEquals(0, $result); // Command::SUCCESS = 0

        // Vérifier que lessons_used a été mis à jour avec seulement les cours passés
        $this->subscriptionInstance->refresh();
        $this->assertEquals(2, $this->subscriptionInstance->lessons_used);
    }

    /**
     * Test : Non-consommation des cours futurs
     * 
     * BUT : Vérifier que la commande ne consomme pas les cours futurs
     * 
     * ENTRÉE :
     * - 2 cours futurs (demain et après-demain)
     * - Les cours attachés à l'abonnement
     * - lessons_used initial = 0
     * 
     * SORTIE ATTENDUE :
     * - lessons_used reste à 0 (aucun cours consommé)
     * 
     * POURQUOI : Les cours futurs ne doivent pas consommer l'abonnement tant qu'ils ne sont pas passés.
     *            Cela permet de réserver plusieurs créneaux à l'avance sans épuiser l'abonnement immédiatement.
     *            La commande ne doit traiter que les cours dont la date/heure est dans le passé.
     */
    #[Test]
    public function it_does_not_consume_future_lessons(): void
    {
        // Créer uniquement des cours futurs
        $futureLesson1 = Lesson::create([
            'club_id' => $this->club->id,
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'course_type_id' => $this->courseType->id,
            'location_id' => $this->location->id,
            'start_time' => Carbon::now()->addDay(),
            'end_time' => Carbon::now()->addDay()->addHour(),
            'status' => 'confirmed',
            'price' => 50.00,
        ]);

        $futureLesson2 = Lesson::create([
            'club_id' => $this->club->id,
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'course_type_id' => $this->courseType->id,
            'location_id' => $this->location->id,
            'start_time' => Carbon::now()->addDays(2),
            'end_time' => Carbon::now()->addDays(2)->addHour(),
            'status' => 'confirmed',
            'price' => 50.00,
        ]);

        // Attacher les cours futurs à l'abonnement
        $this->subscriptionInstance->lessons()->attach([
            $futureLesson1->id,
            $futureLesson2->id,
        ]);

        // Exécuter la commande via Artisan::call pour avoir la sortie configurée
        Artisan::call('subscriptions:consume-past-lessons');

        // Vérifier que lessons_used n'a pas changé (cours futurs non consommés)
        $this->subscriptionInstance->refresh();
        $this->assertEquals(0, $this->subscriptionInstance->lessons_used);
    }

    #[Test]
    public function it_handles_multiple_subscription_instances(): void
    {
        // Créer une discipline pour le deuxième abonnement
        $discipline2 = Discipline::create([
            'name' => 'Discipline Test 2',
            'slug' => 'discipline-test-2',
            'is_active' => true,
        ]);

        // Créer un type de cours pour le deuxième abonnement
        $courseType2 = CourseType::create([
            'name' => 'Cours Test 2',
            'duration_minutes' => 60,
            'price' => 50.00,
            'discipline_id' => $discipline2->id,
        ]);

        // Créer un deuxième abonnement
        $subscription2 = Subscription::create([
            'club_id' => $this->club->id,
            'name' => 'Abonnement Test 2',
            'total_lessons' => 10,
            'free_lessons' => 0,
            'price' => 200.00,
            'is_active' => true,
        ]);

        // Lier la discipline au deuxième abonnement
        $subscription2->courseTypes()->attach($discipline2->id);

        $instance2 = SubscriptionInstance::create([
            'subscription_id' => $subscription2->id,
            'lessons_used' => 0,
            'started_at' => Carbon::now(),
            'expires_at' => Carbon::now()->addMonths(3),
            'status' => 'active',
        ]);

        $instance2->students()->attach($this->student->id);

        // Créer des cours passés pour chaque abonnement
        $pastLesson1 = Lesson::create([
            'club_id' => $this->club->id,
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'course_type_id' => $this->courseType->id,
            'location_id' => $this->location->id,
            'start_time' => Carbon::now()->subDay(),
            'end_time' => Carbon::now()->subDay()->addHour(),
            'status' => 'confirmed',
            'price' => 50.00,
        ]);

        $pastLesson2 = Lesson::create([
            'club_id' => $this->club->id,
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'course_type_id' => $courseType2->id,
            'location_id' => $this->location->id,
            'start_time' => Carbon::now()->subDays(2),
            'end_time' => Carbon::now()->subDays(2)->addHour(),
            'status' => 'confirmed',
            'price' => 50.00,
        ]);

        // Attacher les cours aux abonnements
        $this->subscriptionInstance->lessons()->attach($pastLesson1->id);
        $instance2->lessons()->attach($pastLesson2->id);

        // Exécuter la commande via Artisan::call pour avoir la sortie configurée
        Artisan::call('subscriptions:consume-past-lessons');

        // Vérifier que les deux abonnements ont été mis à jour
        $this->subscriptionInstance->refresh();
        $instance2->refresh();

        $this->assertEquals(1, $this->subscriptionInstance->lessons_used);
        $this->assertEquals(1, $instance2->lessons_used);
    }

    #[Test]
    public function it_does_not_consume_cancelled_lessons(): void
    {
        // Créer un cours passé annulé
        $cancelledLesson = Lesson::create([
            'club_id' => $this->club->id,
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'course_type_id' => $this->courseType->id,
            'location_id' => $this->location->id,
            'start_time' => Carbon::now()->subDay(),
            'end_time' => Carbon::now()->subDay()->addHour(),
            'status' => 'cancelled',
            'price' => 50.00,
        ]);

        // Créer un cours passé confirmé
        $confirmedLesson = Lesson::create([
            'club_id' => $this->club->id,
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'course_type_id' => $this->courseType->id,
            'location_id' => $this->location->id,
            'start_time' => Carbon::now()->subDays(2),
            'end_time' => Carbon::now()->subDays(2)->addHour(),
            'status' => 'confirmed',
            'price' => 50.00,
        ]);

        // Attacher les deux cours à l'abonnement
        $this->subscriptionInstance->lessons()->attach([
            $cancelledLesson->id,
            $confirmedLesson->id,
        ]);

        // Exécuter la commande via Artisan::call pour avoir la sortie configurée
        Artisan::call('subscriptions:consume-past-lessons');

        // Vérifier que seul le cours confirmé a été consommé
        $this->subscriptionInstance->refresh();
        $this->assertEquals(1, $this->subscriptionInstance->lessons_used);
    }

    #[Test]
    public function it_only_processes_active_subscriptions(): void
    {
        // Créer un abonnement expiré
        $expiredSubscription = Subscription::create([
            'club_id' => $this->club->id,
            'name' => 'Abonnement Expiré',
            'total_lessons' => 10,
            'free_lessons' => 0,
            'price' => 200.00,
            'is_active' => true,
        ]);

        $expiredInstance = SubscriptionInstance::create([
            'subscription_id' => $expiredSubscription->id,
            'lessons_used' => 0,
            'started_at' => Carbon::now()->subMonths(4),
            'expires_at' => Carbon::now()->subMonth(),
            'status' => 'expired',
        ]);

        $expiredInstance->students()->attach($this->student->id);

        // Créer un cours passé pour l'abonnement expiré
        $pastLesson = Lesson::create([
            'club_id' => $this->club->id,
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'course_type_id' => $this->courseType->id,
            'location_id' => $this->location->id,
            'start_time' => Carbon::now()->subDay(),
            'end_time' => Carbon::now()->subDay()->addHour(),
            'status' => 'confirmed',
            'price' => 50.00,
        ]);

        $expiredInstance->lessons()->attach($pastLesson->id);

        // Exécuter la commande via Artisan::call pour avoir la sortie configurée
        Artisan::call('subscriptions:consume-past-lessons');

        // Vérifier que l'abonnement expiré n'a pas été traité
        $expiredInstance->refresh();
        $this->assertEquals(0, $expiredInstance->lessons_used);
    }

    #[Test]
    public function it_handles_subscriptions_with_no_past_lessons(): void
    {
        // Ne pas créer de cours

        // Exécuter la commande via Artisan::call pour avoir la sortie configurée
        $result = Artisan::call('subscriptions:consume-past-lessons');

        // La commande doit s'exécuter sans erreur
        $this->assertEquals(0, $result);

        // lessons_used doit rester à 0
        $this->subscriptionInstance->refresh();
        $this->assertEquals(0, $this->subscriptionInstance->lessons_used);
    }

    #[Test]
    public function it_updates_status_when_subscription_becomes_full(): void
    {
        // Utiliser presque tous les cours
        $this->subscriptionInstance->lessons_used = 9;
        $this->subscriptionInstance->save();

        // Créer un cours passé
        $pastLesson = Lesson::create([
            'club_id' => $this->club->id,
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'course_type_id' => $this->courseType->id,
            'location_id' => $this->location->id,
            'start_time' => Carbon::now()->subDay(),
            'end_time' => Carbon::now()->subDay()->addHour(),
            'status' => 'confirmed',
            'price' => 50.00,
        ]);

        $this->subscriptionInstance->lessons()->attach($pastLesson->id);

        // Exécuter la commande via Artisan::call pour avoir la sortie configurée
        Artisan::call('subscriptions:consume-past-lessons');

        // Vérifier que l'abonnement est maintenant complet
        $this->subscriptionInstance->refresh();
        $this->assertEquals(10, $this->subscriptionInstance->lessons_used);
        $this->assertEquals('completed', $this->subscriptionInstance->status);
    }
}

