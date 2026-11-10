<?php

namespace App\Console\Commands;

use App\Services\ProlongRecurringSlotsService;
use Illuminate\Console\Command;

/**
 * Planifie l'avenir : prolonge les récurrences des élèves ayant un abonnement actif jusqu'à
 * l'horizon de 26 semaines (borné à l'expiration de leurs abonnements) et génère les cours.
 * Exécuté chaque jour.
 */
class ProlongRecurringSlotsCommand extends Command
{
    protected $signature = 'recurring-slots:prolong
                            {--dry-run : Afficher ce qui serait prolongé sans le faire}';

    protected $description = 'Prolonge les récurrences des élèves ayant un abonnement actif (horizon 26 semaines) et génère les cours';

    public function handle(ProlongRecurringSlotsService $service): int
    {
        $dryRun = $this->option('dry-run');

        $this->info('🔄 Prolongation des récurrences (abonnements actifs, horizon 26 semaines)');
        $this->newLine();

        if ($dryRun) {
            $this->warn('⚠️  Mode DRY-RUN activé - Aucune modification ne sera effectuée');
            $this->newLine();
            $result = $service->prolongActiveRecurrences(null, null, true);
            $this->info("📊 Récurrences qui seraient prolongées : {$result['prolonged']}");

            return Command::SUCCESS;
        }

        try {
            $result = $service->prolongActiveRecurrences();
            $this->table(
                ['Métrique', 'Valeur'],
                [
                    ['Récurrences prolongées', $result['prolonged']],
                    ['Lessons générées', $result['lessons_generated']],
                    ['Lessons ignorées', $result['lessons_skipped']],
                    ['Erreurs', $result['errors']],
                ]
            );
            $this->newLine();
            if ($result['prolonged'] > 0 || $result['lessons_generated'] > 0) {
                $this->info('✅ Prolongation terminée');
            } else {
                $this->info('ℹ️  Aucune récurrence à prolonger');
            }

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('❌ Erreur : '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
