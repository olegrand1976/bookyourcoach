<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CommissionCalculationService;
use Carbon\Carbon;

/**
 * Commande pour générer le rapport de paie mensuel des enseignants
 * 
 * Cette commande génère un rapport détaillé des commissions dues aux enseignants
 * pour une période donnée, en distinguant les abonnements Type 1 et Type 2.
 * 
 * Usage:
 *   php artisan payroll:generate 2025 11
 *   php artisan payroll:generate --month=11 --year=2025
 *   php artisan payroll:generate (utilise le mois/année courants)
 */
class GenerateMonthlyPayrollReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payroll:generate 
                            {--year= : Année (ex: 2025)}
                            {--month= : Mois (1-12)}
                            {--output=json : Format de sortie (json|table|csv)}
                            {--file= : Chemin du fichier de sortie (optionnel)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Génère un rapport de paie mensuel pour les enseignants (commissions Type 1 et Type 2)';

    /**
     * Service de calcul des commissions
     */
    private CommissionCalculationService $commissionService;

    /**
     * Create a new command instance.
     */
    public function __construct(CommissionCalculationService $commissionService)
    {
        parent::__construct();
        $this->commissionService = $commissionService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Récupérer les paramètres
        $year = (int) ($this->option('year') ?? Carbon::now()->year);
        $month = (int) ($this->option('month') ?? Carbon::now()->month);
        $outputFormat = $this->option('output') ?? 'json';
        $outputFile = $this->option('file');

        // Valider les paramètres
        if ($month < 1 || $month > 12) {
            $this->error("Le mois doit être entre 1 et 12.");
            return Command::FAILURE;
        }

        if ($year < 2000 || $year > 2100) {
            $this->error("L'année doit être entre 2000 et 2100.");
            return Command::FAILURE;
        }

        $this->info("Génération du rapport de paie pour {$month}/{$year}...");

        try {
            // Générer le rapport
            $report = $this->commissionService->generatePayrollReport($year, $month);

            if (empty($report)) {
                $this->warn("Aucun abonnement payé trouvé pour la période {$month}/{$year}.");
                return Command::SUCCESS;
            }

            // Afficher les statistiques
            $this->displayStatistics($report, $year, $month);

            // Générer la sortie selon le format demandé
            $output = $this->formatOutput($report, $outputFormat);

            // Afficher ou sauvegarder
            if ($outputFile) {
                file_put_contents($outputFile, $output);
                $this->info("Rapport sauvegardé dans : {$outputFile}");
            } else {
                $this->line($output);
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Erreur lors de la génération du rapport : " . $e->getMessage());
            $this->error($e->getTraceAsString());
            return Command::FAILURE;
        }
    }

    /**
     * Afficher les statistiques du rapport
     */
    private function displayStatistics(array $report, int $year, int $month): void
    {
        $this->info("\n=== Statistiques du rapport {$month}/{$year} ===");
        
        $totalTeachers = count($report);
        $totalType1 = array_sum(array_column($report, 'total_commissions_type1'));
        $totalType2 = array_sum(array_column($report, 'total_commissions_type2'));
        $totalGlobal = array_sum(array_column($report, 'total_a_payer'));

        $this->table(
            ['Métrique', 'Valeur'],
            [
                ['Nombre d\'enseignants', $totalTeachers],
                ['Total commissions Type 1', number_format($totalType1, 2, ',', ' ') . ' €'],
                ['Total commissions Type 2', number_format($totalType2, 2, ',', ' ') . ' €'],
                ['Total à payer', number_format($totalGlobal, 2, ',', ' ') . ' €'],
            ]
        );
    }

    /**
     * Formater la sortie selon le format demandé
     */
    private function formatOutput(array $report, string $format): string
    {
        return match ($format) {
            'json' => $this->formatJson($report),
            'table' => $this->formatTable($report),
            'csv' => $this->formatCsv($report),
            default => $this->formatJson($report),
        };
    }

    /**
     * Formater en JSON
     */
    private function formatJson(array $report): string
    {
        // Réorganiser pour avoir les IDs comme clés (comme demandé dans les specs)
        $formattedReport = [];
        foreach ($report as $teacherId => $data) {
            $formattedReport["enseignant_id_{$teacherId}"] = $data;
        }

        return json_encode($formattedReport, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Formater en tableau (pour affichage console)
     */
    private function formatTable(array $report): string
    {
        $output = "\n=== Rapport détaillé par enseignant ===\n\n";
        
        $tableData = [];
        foreach ($report as $teacherId => $data) {
            $tableData[] = [
                $data['nom_enseignant'],
                number_format($data['total_commissions_type1'], 2, ',', ' ') . ' €',
                number_format($data['total_commissions_type2'], 2, ',', ' ') . ' €',
                number_format($data['total_a_payer'], 2, ',', ' ') . ' €',
            ];
        }

        $this->table(
            ['Enseignant', 'Commissions Type 1', 'Commissions Type 2', 'Total à payer'],
            $tableData
        );

        return '';
    }

    /**
     * Formater en CSV
     */
    private function formatCsv(array $report): string
    {
        $output = "Enseignant ID,Nom Enseignant,Total Commissions Type 1,Total Commissions Type 2,Total à payer\n";
        
        foreach ($report as $teacherId => $data) {
            $output .= sprintf(
                "%d,%s,%.2f,%.2f,%.2f\n",
                $data['enseignant_id'],
                $data['nom_enseignant'],
                $data['total_commissions_type1'],
                $data['total_commissions_type2'],
                $data['total_a_payer']
            );
        }

        return $output;
    }
}
