<?php

namespace App\Console\Commands;

use App\Jobs\GenerateRecurringLessonsJob;
use App\Services\RecurringSlotService;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Commande pour générer automatiquement les lessons à partir des créneaux récurrents
 * 
 * Cette commande peut être exécutée :
 * - Manuellement via artisan
 * - Via un cron job quotidien
 * - Après la création d'un nouveau créneau récurrent
 */
class GenerateRecurringLessonsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'recurring-slots:generate-lessons
                            {--slot= : ID du créneau récurrent spécifique (optionnel)}
                            {--start-date= : Date de début (format: Y-m-d, par défaut: aujourd\'hui)}
                            {--end-date= : Date de fin (format: Y-m-d, par défaut: +3 mois)}
                            {--async : Exécuter en mode asynchrone via queue}
                            {--dry-run : Afficher ce qui serait généré sans le faire}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Génère automatiquement les lessons à partir des créneaux récurrents actifs';

    /**
     * Execute the console command.
     */
    public function handle(RecurringSlotService $service): int
    {
        $slotId = $this->option('slot');
        $startDateStr = $this->option('start-date');
        $endDateStr = $this->option('end-date');
        $async = $this->option('async');
        $dryRun = $this->option('dry-run');

        $this->info('🔄 Génération des lessons à partir des créneaux récurrents');
        $this->newLine();

        // Parser les dates
        $startDate = $startDateStr ? Carbon::parse($startDateStr) : Carbon::now();
        $endDate = $endDateStr ? Carbon::parse($endDateStr) : Carbon::now()->addMonths(3);

        if ($dryRun) {
            $this->warn('⚠️  Mode DRY-RUN activé - Aucune lesson ne sera créée');
            $this->newLine();
            $this->info("📅 Période : {$startDate->format('Y-m-d')} → {$endDate->format('Y-m-d')}");
            
            if ($slotId) {
                $this->info("🎯 Créneau spécifique : #{$slotId}");
            } else {
                $this->info("🎯 Tous les créneaux actifs");
            }
            
            return Command::SUCCESS;
        }

        try {
            if ($async) {
                // Exécuter en mode asynchrone
                $this->info('⏳ Envoi du job en queue...');
                
                GenerateRecurringLessonsJob::dispatch(
                    $startDate,
                    $endDate,
                    $slotId ? (int) $slotId : null
                );

                $this->info('✅ Job envoyé en queue avec succès');
            } else {
                // Exécuter en mode synchrone
                $this->info("📅 Période : {$startDate->format('Y-m-d')} → {$endDate->format('Y-m-d')}");
                $this->newLine();

                if ($slotId) {
                    $recurringSlot = \App\Models\RecurringSlot::find($slotId);
                    
                    if (!$recurringSlot) {
                        $this->error("❌ Créneau récurrent #{$slotId} introuvable");
                        return Command::FAILURE;
                    }

                    $this->info("🎯 Génération pour créneau #{$slotId}...");
                    $this->newLine();

                    $stats = $service->generateLessonsForSlot($recurringSlot, $startDate, $endDate);

                    $this->displayStats($stats);
                } else {
                    $this->info("🎯 Génération pour tous les créneaux actifs...");
                    $this->newLine();

                    $stats = $service->generateLessonsForAllActiveSlots($startDate, $endDate);

                    $this->displayStats($stats, true);
                }
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("❌ Erreur lors de la génération : {$e->getMessage()}");
            $this->error($e->getTraceAsString());
            return Command::FAILURE;
        }
    }

    /**
     * Affiche les statistiques de génération
     */
    private function displayStats(array $stats, bool $isGlobal = false): void
    {
        if ($isGlobal) {
            $this->table(
                ['Métrique', 'Valeur'],
                [
                    ['Créneaux traités', $stats['slots_processed']],
                    ['Lessons générées', $stats['lessons_generated']],
                    ['Lessons ignorées', $stats['lessons_skipped']],
                    ['Erreurs', $stats['errors']],
                ]
            );
        } else {
            $this->table(
                ['Métrique', 'Valeur'],
                [
                    ['Lessons générées', $stats['generated']],
                    ['Lessons ignorées', $stats['skipped']],
                    ['Erreurs', $stats['errors']],
                ]
            );
        }

        $this->newLine();
        
        if (($isGlobal ? $stats['lessons_generated'] : $stats['generated']) > 0) {
            $this->info('✅ Génération terminée avec succès');
        } else {
            $this->warn('ℹ️  Aucune lesson générée');
        }
    }
}
