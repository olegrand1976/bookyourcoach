<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SubscriptionRecurringSlot;
use App\Models\RecurringSlot;
use App\Models\RecurringSlotSubscription;
use App\Models\LessonRecurringSlot;
use App\Models\Lesson;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MigrateRecurringSlotsToRrule extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'recurring-slots:migrate 
                            {--dry-run : Exécuter sans sauvegarder les données}
                            {--force : Forcer la migration même si des données existent déjà}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migre les SubscriptionRecurringSlot existants vers le nouveau modèle RecurringSlot avec RRULE';

    /**
     * Mapping des jours de la semaine vers les codes RRULE
     */
    private const DAY_OF_WEEK_TO_RRULE = [
        0 => 'SU', // Dimanche
        1 => 'MO', // Lundi
        2 => 'TU', // Mardi
        3 => 'WE', // Mercredi
        4 => 'TH', // Jeudi
        5 => 'FR', // Vendredi
        6 => 'SA', // Samedi
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        $this->info('🔄 Migration des SubscriptionRecurringSlot vers RecurringSlot avec RRULE');
        $this->newLine();

        // Vérifier si des données existent déjà
        if (!$force && RecurringSlot::count() > 0) {
            $this->warn('⚠️  Des RecurringSlot existent déjà dans la base de données.');
            if (!$this->confirm('Voulez-vous continuer ? Cela pourrait créer des doublons.', false)) {
                $this->info('Migration annulée.');
                return Command::FAILURE;
            }
        }

        // Compter les SubscriptionRecurringSlot à migrer
        // Récupérer tous les créneaux (la table legacy n'a peut-être pas de colonne status)
        $legacySlots = SubscriptionRecurringSlot::query()
            ->with(['subscriptionInstance', 'student', 'teacher'])
            ->get();

        $total = $legacySlots->count();
        $this->info("📊 {$total} SubscriptionRecurringSlot à migrer");
        $this->newLine();

        if ($total === 0) {
            $this->info('✅ Aucun créneau à migrer.');
            return Command::SUCCESS;
        }

        if ($dryRun) {
            $this->warn('🔍 Mode DRY-RUN : Aucune donnée ne sera sauvegardée');
            $this->newLine();
        }

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $stats = [
            'created' => 0,
            'skipped' => 0,
            'errors' => 0,
            'subscriptions_linked' => 0,
            'lessons_linked' => 0,
        ];

        DB::beginTransaction();

        try {
            foreach ($legacySlots as $legacySlot) {
                try {
                    $result = $this->migrateSlot($legacySlot, $dryRun);
                    $stats['created'] += $result['created'] ? 1 : 0;
                    $stats['skipped'] += $result['skipped'] ? 1 : 0;
                    $stats['subscriptions_linked'] += $result['subscriptions_linked'];
                    $stats['lessons_linked'] += $result['lessons_linked'];
                } catch (\Exception $e) {
                    $stats['errors']++;
                    $this->newLine();
                    $this->error("❌ Erreur lors de la migration du slot #{$legacySlot->id}: {$e->getMessage()}");
                    if (!$dryRun) {
                        \Log::error("Erreur migration slot #{$legacySlot->id}", [
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                        ]);
                    }
                }

                $bar->advance();
            }

            if ($dryRun) {
                DB::rollBack();
                $this->newLine(2);
                $this->info('🔍 Mode DRY-RUN : Transaction annulée');
            } else {
                DB::commit();
                $this->newLine(2);
                $this->info('✅ Migration terminée avec succès');
            }
        } catch (\Exception $e) {
            DB::rollBack();
            $this->newLine(2);
            $this->error("❌ Erreur fatale : {$e->getMessage()}");
            return Command::FAILURE;
        }

        $bar->finish();
        $this->newLine(2);

        // Afficher les statistiques
        $this->displayStats($stats);

        return Command::SUCCESS;
    }

    /**
     * Migre un SubscriptionRecurringSlot vers RecurringSlot
     */
    private function migrateSlot(SubscriptionRecurringSlot $legacySlot, bool $dryRun): array
    {
        $result = [
            'created' => false,
            'skipped' => false,
            'subscriptions_linked' => 0,
            'lessons_linked' => 0,
        ];

        // Vérifier que les relations nécessaires existent
        if (!$legacySlot->student_id || !$legacySlot->teacher_id) {
            $result['skipped'] = true;
            return $result;
        }

        // Récupérer le club_id
        $clubId = $this->getClubId($legacySlot);
        if (!$clubId) {
            $result['skipped'] = true;
            return $result;
        }

        // Convertir day_of_week en RRULE
        $rrule = $this->convertToRrule($legacySlot);
        if (!$rrule) {
            $result['skipped'] = true;
            return $result;
        }

        // Calculer la durée en minutes
        $durationMinutes = $this->calculateDuration($legacySlot);

        // Créer la date de référence (première occurrence)
        $referenceStartTime = $this->getReferenceStartTime($legacySlot);

        // Vérifier si un RecurringSlot existe déjà pour cet élève/enseignant/jour/heure
        $existingSlot = RecurringSlot::where('student_id', $legacySlot->student_id)
            ->where('teacher_id', $legacySlot->teacher_id)
            ->where('rrule', $rrule)
            ->where('reference_start_time', $referenceStartTime)
            ->first();

        if ($existingSlot) {
            // Utiliser le slot existant
            $recurringSlot = $existingSlot;
        } else {
            // Créer un nouveau RecurringSlot
            $recurringSlot = new RecurringSlot();
            $recurringSlot->student_id = $legacySlot->student_id;
            $recurringSlot->teacher_id = $legacySlot->teacher_id;
            $recurringSlot->club_id = $clubId;
            $recurringSlot->course_type_id = $legacySlot->openSlot?->course_type_id ?? null;
            $recurringSlot->rrule = $rrule;
            $recurringSlot->reference_start_time = $referenceStartTime;
            $recurringSlot->duration_minutes = $durationMinutes;
            $recurringSlot->status = $this->convertStatus($legacySlot->status);
            $recurringSlot->notes = $this->buildNotes($legacySlot);

            if (!$dryRun) {
                $recurringSlot->save();
            }
            $result['created'] = true;
        }

        // Créer la liaison avec l'abonnement
        if ($legacySlot->subscription_instance_id) {
            $subscriptionLink = $this->createSubscriptionLink(
                $recurringSlot,
                $legacySlot,
                $dryRun
            );
            if ($subscriptionLink) {
                $result['subscriptions_linked'] = 1;
            }
        }

        // Lier les lessons existantes (si elles correspondent au créneau)
        $lessonsLinked = $this->linkExistingLessons($recurringSlot, $legacySlot, $dryRun);
        $result['lessons_linked'] = $lessonsLinked;

        return $result;
    }

    /**
     * Convertit day_of_week en RRULE
     */
    private function convertToRrule(SubscriptionRecurringSlot $legacySlot): ?string
    {
        if (!isset(self::DAY_OF_WEEK_TO_RRULE[$legacySlot->day_of_week])) {
            return null;
        }

        $dayCode = self::DAY_OF_WEEK_TO_RRULE[$legacySlot->day_of_week];
        return "FREQ=WEEKLY;BYDAY={$dayCode}";
    }

    /**
     * Calcule la durée en minutes
     */
    private function calculateDuration(SubscriptionRecurringSlot $legacySlot): int
    {
        if ($legacySlot->start_time && $legacySlot->end_time) {
            $start = Carbon::parse($legacySlot->start_time);
            $end = Carbon::parse($legacySlot->end_time);
            return $start->diffInMinutes($end);
        }

        // Durée par défaut : 60 minutes
        return 60;
    }

    /**
     * Obtient la date/heure de référence (première occurrence)
     */
    private function getReferenceStartTime(SubscriptionRecurringSlot $legacySlot): Carbon
    {
        if ($legacySlot->start_date) {
            $date = Carbon::parse($legacySlot->start_date);
            
            // Ajouter l'heure de début
            if ($legacySlot->start_time) {
                $time = Carbon::parse($legacySlot->start_time);
                $date->setTime($time->hour, $time->minute, $time->second);
            } else {
                $date->setTime(9, 0, 0); // Heure par défaut : 9h
            }

            return $date;
        }

        // Par défaut : prochaine occurrence du jour de la semaine
        $dayOfWeek = $legacySlot->day_of_week;
        $nextOccurrence = Carbon::now()->next($dayOfWeek);
        
        if ($legacySlot->start_time) {
            $time = Carbon::parse($legacySlot->start_time);
            $nextOccurrence->setTime($time->hour, $time->minute, $time->second);
        } else {
            $nextOccurrence->setTime(9, 0, 0);
        }

        return $nextOccurrence;
    }

    /**
     * Obtient le club_id depuis le slot legacy
     */
    private function getClubId(SubscriptionRecurringSlot $legacySlot): ?int
    {
        // Essayer via subscription_instance -> subscription -> club_id
        if ($legacySlot->subscriptionInstance && $legacySlot->subscriptionInstance->subscription) {
            $subscription = $legacySlot->subscriptionInstance->subscription;
            if (isset($subscription->club_id)) {
                return $subscription->club_id;
            }
        }

        // Essayer via student -> club_id (si la colonne existe)
        if ($legacySlot->student && isset($legacySlot->student->club_id)) {
            return $legacySlot->student->club_id;
        }

        // Essayer via teacher -> club_id
        if ($legacySlot->teacher && isset($legacySlot->teacher->club_id)) {
            return $legacySlot->teacher->club_id;
        }

        // Essayer via open_slot -> club_id
        if ($legacySlot->openSlot && isset($legacySlot->openSlot->club_id)) {
            return $legacySlot->openSlot->club_id;
        }

        return null;
    }

    /**
     * Convertit le statut legacy vers le nouveau statut
     */
    private function convertStatus(string $legacyStatus): string
    {
        return match ($legacyStatus) {
            'active' => 'active',
            'cancelled' => 'cancelled',
            'expired' => 'expired',
            'completed' => 'expired',
            default => 'active',
        };
    }

    /**
     * Construit les notes pour le nouveau slot
     */
    private function buildNotes(SubscriptionRecurringSlot $legacySlot): string
    {
        $notes = [];
        
        if ($legacySlot->notes) {
            $notes[] = "Notes originales: {$legacySlot->notes}";
        }

        $notes[] = "Migré depuis SubscriptionRecurringSlot #{$legacySlot->id}";
        $notes[] = "Date de migration: " . Carbon::now()->format('Y-m-d H:i:s');

        return implode("\n", $notes);
    }

    /**
     * Crée la liaison avec l'abonnement
     */
    private function createSubscriptionLink(
        RecurringSlot $recurringSlot,
        SubscriptionRecurringSlot $legacySlot,
        bool $dryRun
    ): ?RecurringSlotSubscription {
        if (!$legacySlot->subscription_instance_id) {
            return null;
        }

        // Vérifier si la liaison existe déjà
        $existingLink = RecurringSlotSubscription::where('recurring_slot_id', $recurringSlot->id)
            ->where('subscription_instance_id', $legacySlot->subscription_instance_id)
            ->first();

        if ($existingLink) {
            return $existingLink;
        }

        $link = new RecurringSlotSubscription();
        $link->recurring_slot_id = $recurringSlot->id;
        $link->subscription_instance_id = $legacySlot->subscription_instance_id;
        $link->start_date = $legacySlot->start_date ?? Carbon::now();
        $link->end_date = $legacySlot->end_date ?? Carbon::now()->addMonths(3);
        $link->status = $this->convertStatus($legacySlot->status);

        if (!$dryRun) {
            $link->save();
        }

        return $link;
    }

    /**
     * Lie les lessons existantes qui correspondent au créneau
     */
    private function linkExistingLessons(
        RecurringSlot $recurringSlot,
        SubscriptionRecurringSlot $legacySlot,
        bool $dryRun
    ): int {
        if (!$legacySlot->subscription_instance_id) {
            return 0;
        }

        // Trouver les lessons liées à cette subscription_instance
        // qui correspondent au jour de la semaine et à l'heure approximative
        $lessons = Lesson::whereHas('subscriptionInstances', function ($query) use ($legacySlot) {
            $query->where('subscription_instances.id', $legacySlot->subscription_instance_id);
        })
        ->where('student_id', $legacySlot->student_id)
        ->where('teacher_id', $legacySlot->teacher_id)
        ->get()
        ->filter(function ($lesson) use ($legacySlot) {
            // Filtrer par jour de la semaine (Carbon utilise 0=dimanche, 6=samedi)
            $lessonDayOfWeek = $lesson->start_time->dayOfWeek;
            return $lessonDayOfWeek === $legacySlot->day_of_week;
        });

        $linked = 0;

        foreach ($lessons as $lesson) {
            // Vérifier si la liaison existe déjà
            $existingLink = LessonRecurringSlot::where('lesson_id', $lesson->id)
                ->where('recurring_slot_id', $recurringSlot->id)
                ->first();

            if ($existingLink) {
                continue;
            }

            $link = new LessonRecurringSlot();
            $link->lesson_id = $lesson->id;
            $link->recurring_slot_id = $recurringSlot->id;
            $link->subscription_instance_id = $legacySlot->subscription_instance_id;
            $link->generated_at = $lesson->created_at;
            $link->generated_by = 'manual'; // Lessons existantes = manuelles

            if (!$dryRun) {
                $link->save();
            }

            $linked++;
        }

        return $linked;
    }

    /**
     * Affiche les statistiques de migration
     */
    private function displayStats(array $stats): void
    {
        $this->newLine();
        $this->info('📊 Statistiques de migration :');
        $this->table(
            ['Métrique', 'Valeur'],
            [
                ['Créneaux créés', $stats['created']],
                ['Créneaux ignorés', $stats['skipped']],
                ['Erreurs', $stats['errors']],
                ['Liaisons abonnements créées', $stats['subscriptions_linked']],
                ['Lessons liées', $stats['lessons_linked']],
            ]
        );
    }
}
