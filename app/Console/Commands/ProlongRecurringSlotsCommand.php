<?php

namespace App\Console\Commands;

use App\Console\Concerns\SimulatesInTransaction;
use App\Services\ProlongRecurringSlotsService;
use Illuminate\Console\Command;

/**
 * Planifie l'avenir : prolonge les récurrences en cours des élèves ayant un abonnement actif jusqu'à
 * l'horizon de 26 semaines (borné à l'expiration de leurs abonnements) et génère les cours.
 * Une série terminée ou dont la fin a été voulue n'est jamais rouverte.
 * Exécuté chaque jour.
 */
class ProlongRecurringSlotsCommand extends Command
{
    use SimulatesInTransaction;

    protected $signature = 'recurring-slots:prolong
                            {--dry-run : Simuler (transaction annulée) et lister séries prolongées et cours qui seraient créés}';

    protected $description = 'Prolonge les récurrences en cours des élèves ayant un abonnement actif (horizon 26 semaines) et génère les cours';

    public function handle(ProlongRecurringSlotsService $service): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $this->info('🔄 Prolongation des récurrences (abonnements actifs, horizon 26 semaines)');
        $this->newLine();

        try {
            if ($dryRun) {
                $this->warn('⚠️  Mode DRY-RUN : simulation complète, aucune modification enregistrée');
                $this->newLine();
                [$result, $created] = $this->simulate(fn () => $service->prolongActiveRecurrences());
            } else {
                $result = $service->prolongActiveRecurrences();
                $created = null;
            }

            $this->table(
                ['Métrique', 'Valeur'],
                [
                    ['Récurrences prolongées', $result['prolonged']],
                    ['Récurrences arrêtées (dernier abonnement)', $result['trimmed']],
                    ['Lessons générées', $result['lessons_generated']],
                    ['Lessons ignorées', $result['lessons_skipped']],
                    ['Conflits (non générées)', $result['lessons_conflicts']],
                    ['Erreurs', $result['errors']],
                ]
            );

            if ($result['details'] !== []) {
                $this->table(
                    ['Série', 'Élève', 'Enseignant', 'Action', 'Fin avant', 'Fin après'],
                    array_map(fn (array $d) => [
                        $d['subscription_recurring_slot_id'],
                        $d['student_id'],
                        $d['teacher_id'],
                        $d['action'],
                        $d['previous_end'],
                        $d['new_end'],
                    ], $result['details'])
                );
            }

            $this->printConflicts($result['conflicts']);

            if ($created !== null) {
                $this->printSimulatedLessons($created);
            }

            return $result['errors'] > 0 ? Command::FAILURE : Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('❌ Erreur : '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
