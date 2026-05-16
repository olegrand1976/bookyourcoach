<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CommissionCalculationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Contrôleur pour la gestion des rapports de paie
 * 
 * Permet aux administrateurs de :
 * - Générer des rapports de paie mensuels
 * - Consulter les rapports existants
 * - Exporter les rapports en JSON ou CSV
 */
class PayrollController extends Controller
{
    protected $commissionCalculationService;

    public function __construct(CommissionCalculationService $commissionCalculationService)
    {
        $this->commissionCalculationService = $commissionCalculationService;
    }

    /**
     * Générer un rapport de paie pour une période donnée
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function generate(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'year' => 'required|integer|min:2020|max:2100',
                'month' => 'required|integer|min:1|max:12',
            ]);

            $year = (int) $request->input('year');
            $month = (int) $request->input('month');

            if (CommissionCalculationService::isPayrollPeriodInFuture($year, $month)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de générer un rapport pour une période future',
                ], 422);
            }

            // Générer le rapport
            $report = $this->commissionCalculationService->generatePayrollReport($year, $month);

            // Calculer les statistiques
            $stats = $this->calculateStats($report);

            // Sauvegarder le rapport dans le storage (optionnel)
            $filename = "payroll_{$year}_{$month}.json";
            Storage::put("payroll_reports/{$filename}", json_encode($report, JSON_PRETTY_PRINT));

            Log::info("Rapport de paie généré", [
                'year' => $year,
                'month' => $month,
                'teachers_count' => count($report),
                'total_amount' => $stats['total_a_payer']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Rapport de paie généré avec succès',
                'data' => [
                    'report' => $report,
                    'statistics' => $stats,
                    'period' => [
                        'year' => $year,
                        'month' => $month,
                        'month_name' => Carbon::create($year, $month, 1)->locale('fr')->monthName,
                    ],
                    'generated_at' => now()->toDateTimeString(),
                ]
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error("Erreur lors de la génération du rapport de paie", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération du rapport',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupérer les rapports de paie disponibles
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getReports(Request $request): JsonResponse
    {
        try {
            $year = $request->input('year');
            $month = $request->input('month');

            $reports = [];

            // Si une période spécifique est demandée, générer/récupérer ce rapport
            if ($year && $month) {
                $year = (int) $year;
                $month = (int) $month;
                if (CommissionCalculationService::isPayrollPeriodInFuture($year, $month)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Impossible d\'afficher un rapport pour une période future',
                    ], 422);
                }

                $report = $this->commissionCalculationService->generatePayrollReport($year, $month);
                $stats = $this->calculateStats($report);

                $reports[] = [
                    'year' => $year,
                    'month' => $month,
                    'month_name' => Carbon::create($year, $month, 1)->locale('fr')->monthName,
                    'statistics' => $stats,
                    'teachers_count' => count($report),
                    'generated_at' => now()->toDateTimeString(),
                ];
            } else {
                // Sinon, lister les rapports disponibles dans le storage
                $files = Storage::files('payroll_reports');
                
                foreach ($files as $file) {
                    if (preg_match('/payroll_(\d{4})_(\d{1,2})\.json/', $file, $matches)) {
                        $fileYear = (int) $matches[1];
                        $fileMonth = (int) $matches[2];

                        if (CommissionCalculationService::isPayrollPeriodInFuture($fileYear, $fileMonth)) {
                            continue;
                        }

                        $content = Storage::get($file);
                        $report = json_decode($content, true);
                        $stats = $this->calculateStats($report);

                        $reports[] = [
                            'year' => $fileYear,
                            'month' => $fileMonth,
                            'month_name' => Carbon::create($fileYear, $fileMonth, 1)->locale('fr')->monthName,
                            'statistics' => $stats,
                            'teachers_count' => count($report),
                            'generated_at' => Storage::lastModified($file) ? Carbon::createFromTimestamp(Storage::lastModified($file))->toDateTimeString() : null,
                        ];
                    }
                }

                // Trier par année et mois (plus récent en premier)
                usort($reports, function($a, $b) {
                    if ($a['year'] === $b['year']) {
                        return $b['month'] - $a['month'];
                    }
                    return $b['year'] - $a['year'];
                });
            }

            return response()->json([
                'success' => true,
                'data' => $reports
            ], 200);

        } catch (\Exception $e) {
            Log::error("Erreur lors de la récupération des rapports", [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des rapports',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupérer un rapport détaillé pour une période spécifique
     * 
     * @param Request $request
     * @param int $year
     * @param int $month
     * @return JsonResponse
     */
    public function getReportDetails(Request $request, int $year, int $month): JsonResponse
    {
        try {
            // Valider les paramètres
            if ($year < 2020 || $year > 2100 || $month < 1 || $month > 12) {
                return response()->json([
                    'success' => false,
                    'message' => 'Période invalide'
                ], 422);
            }

            if (CommissionCalculationService::isPayrollPeriodInFuture($year, $month)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible d\'afficher un rapport pour une période future',
                ], 422);
            }

            // Générer le rapport
            $report = $this->commissionCalculationService->generatePayrollReport($year, $month);
            $stats = $this->calculateStats($report);

            return response()->json([
                'success' => true,
                'data' => [
                    'report' => $report,
                    'statistics' => $stats,
                    'period' => [
                        'year' => $year,
                        'month' => $month,
                        'month_name' => Carbon::create($year, $month, 1)->locale('fr')->monthName,
                    ],
                    'generated_at' => now()->toDateTimeString(),
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error("Erreur lors de la récupération du rapport détaillé", [
                'error' => $e->getMessage(),
                'year' => $year,
                'month' => $month
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du rapport',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Exporter un rapport en CSV
     * 
     * @param Request $request
     * @param int $year
     * @param int $month
     * @return \Symfony\Component\HttpFoundation\StreamedResponse|JsonResponse
     */
    public function exportCsv(Request $request, int $year, int $month)
    {
        try {
            // Valider les paramètres
            if ($year < 2020 || $year > 2100 || $month < 1 || $month > 12) {
                return response()->json([
                    'success' => false,
                    'message' => 'Période invalide'
                ], 422);
            }

            if (CommissionCalculationService::isPayrollPeriodInFuture($year, $month)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible d\'exporter un rapport pour une période future',
                ], 422);
            }

            $report = $this->commissionCalculationService->generatePayrollReport($year, $month);
            $monthName = Carbon::create($year, $month, 1)->locale('fr')->monthName;

            $filename = "rapport_paie_{$monthName}_{$year}.csv";

            // Ajouter BOM UTF-8 pour Excel
            $bom = "\xEF\xBB\xBF";

            return response()->streamDownload(function () use ($report, $bom) {
                $handle = fopen('php://output', 'w');
                
                // BOM UTF-8
                fwrite($handle, $bom);
                
                // En-têtes CSV
                fputcsv($handle, [
                    'ID Enseignant',
                    'Nom Enseignant',
                    'Minutes cours (cumul)',
                    'Heures cours (VH = Σ min ÷ 60)',
                    'Commissions DCL (€)',
                    'Commissions NDCL (€)',
                    'Total à Payer (€)',
                ], ';');

                // Données
                foreach ($report as $teacherId => $data) {
                    $dcl = $data['total_commissions_dcl'] ?? 0;
                    $ndcl = $data['total_commissions_ndcl'] ?? 0;
                    $vh = isset($data['total_heures_cours']) ? (float) $data['total_heures_cours'] : 0.0;
                    $minutes = (int) ($data['total_duree_cours_minutes'] ?? 0);

                    fputcsv($handle, [
                        $data['enseignant_id'],
                        $data['nom_enseignant'],
                        (string) $minutes,
                        number_format($vh, 2, ',', ' '),
                        number_format($dcl, 2, ',', ' '),
                        number_format($ndcl, 2, ',', ' '),
                        number_format($data['total_a_payer'], 2, ',', ' '),
                    ], ';');
                }

                fclose($handle);
            }, $filename, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ]);

        } catch (\Exception $e) {
            Log::error("Erreur lors de l'export CSV", [
                'error' => $e->getMessage(),
                'year' => $year,
                'month' => $month
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'export CSV',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Exporter un rapport détaillé en PDF (lignes par jour, durée si connue, segment, montant).
     *
     * @return \Symfony\Component\HttpFoundation\Response|JsonResponse
     */
    public function exportPdf(Request $request, int $year, int $month)
    {
        try {
            if ($year < 2020 || $year > 2100 || $month < 1 || $month > 12) {
                return response()->json([
                    'success' => false,
                    'message' => 'Période invalide',
                ], 422);
            }

            if (CommissionCalculationService::isPayrollPeriodInFuture($year, $month)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible d\'exporter un rapport pour une période future',
                ], 422);
            }

            $bundle = $this->commissionCalculationService->generatePayrollReportWithLines($year, $month);
            $report = $bundle['report'];
            $linesByTeacher = $bundle['lines_by_teacher'];
            $stats = $this->calculateStats($report);

            $periodStart = Carbon::create($year, $month, 1)->locale('fr');
            $periodEnd = $periodStart->copy()->endOfMonth();
            $periodRangeLabel = sprintf(
                'Du %s au %s',
                $periodStart->copy()->startOfMonth()->translatedFormat('j F Y'),
                $periodEnd->translatedFormat('j F Y')
            );
            $tz = config('app.timezone', 'Europe/Paris');

            $teachersOrdered = [];
            foreach ($report as $teacherId => $row) {
                $tid = (int) $teacherId;
                $lineRows = $linesByTeacher[$tid]
                    ?? $linesByTeacher[$teacherId]
                    ?? [];

                $vh = round((float) ($row['total_heures_cours'] ?? 0), 2);
                $totalMinTeacher = (int) ($row['total_duree_cours_minutes'] ?? 0);
                $waitingMinTeacher = (int) ($row['total_duree_attente_minutes'] ?? 0);
                $totalPrestedMin = $totalMinTeacher + $waitingMinTeacher;
                $waitingSharePercent = $totalPrestedMin > 0
                    ? round(($waitingMinTeacher / $totalPrestedMin) * 1000) / 10
                    : ($waitingMinTeacher > 0 ? 100.0 : null);
                $teachersOrdered[] = [
                    'id' => $tid,
                    'name' => $row['nom_enseignant'],
                    'total_dcl' => $row['total_commissions_dcl'] ?? 0,
                    'total_ndcl' => $row['total_commissions_ndcl'] ?? 0,
                    'total' => $row['total_a_payer'] ?? 0,
                    'total_heures_cours' => $vh,
                    'total_heures_cours_display' => CommissionCalculationService::formatTotalMinutesAsFrenchHourLabel($totalMinTeacher),
                    'total_duree_cours_minutes' => $totalMinTeacher,
                    'total_duree_cours_display' => $totalMinTeacher > 0 ? $totalMinTeacher.' min cumul séances' : '—',
                    'total_duree_attente_minutes' => $waitingMinTeacher,
                    'total_duree_attente_display' => CommissionCalculationService::formatTotalMinutesAsFrenchHourLabel($waitingMinTeacher),
                    'waiting_share_percent' => $waitingSharePercent,
                    'waiting_share_display' => $waitingSharePercent !== null
                        ? number_format($waitingSharePercent, 1, ',', ' ').' %'
                        : '—',
                    'lines' => $lineRows,
                ];
            }

            usort($teachersOrdered, static function ($a, $b) {
                $minutesA = (int) ($a['total_duree_cours_minutes'] ?? 0);
                $minutesB = (int) ($b['total_duree_cours_minutes'] ?? 0);
                if ($minutesA !== $minutesB) {
                    return $minutesB <=> $minutesA;
                }

                return strcasecmp($a['name'] ?? '', $b['name'] ?? '');
            });

            $html = view('pdf.payroll-detail', [
                'year' => $year,
                'month' => $month,
                'month_name' => $periodStart->monthName,
                'period_range_label' => $periodRangeLabel,
                'statistics' => $stats,
                'teachers' => $teachersOrdered,
                'generated_at' => now()->timezone($tz)->format('d/m/Y H:i'),
            ])->render();

            $pdf = Pdf::loadHTML($html)
                ->setPaper('a4', 'landscape')
                ->setOption('margin-top', 8)
                ->setOption('margin-bottom', 8)
                ->setOption('margin-left', 8)
                ->setOption('margin-right', 8);

            $filename = sprintf('rapport_paie_detail_%d_%02d.pdf', $year, $month);

            return $pdf->download($filename);
        } catch (\Exception $e) {
            Log::error("Erreur lors de l'export PDF paie", [
                'error' => $e->getMessage(),
                'year' => $year,
                'month' => $month,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'export PDF',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Calculer les statistiques globales d'un rapport
     * 
     * @param array $report
     * @return array
     */
    private function calculateStats(array $report): array
    {
        $totalDcl = 0;
        $totalNdcl = 0;
        $totalAPayer = 0;
        $totalMinutesCours = 0;
        $totalMinutesAttente = 0;

        foreach ($report as $data) {
            $totalDcl += $data['total_commissions_dcl'] ?? 0;
            $totalNdcl += $data['total_commissions_ndcl'] ?? 0;
            $totalAPayer += $data['total_a_payer'];
            $totalMinutesCours += (int) ($data['total_duree_cours_minutes'] ?? 0);
            $totalMinutesAttente += (int) ($data['total_duree_attente_minutes'] ?? 0);
        }

        $totalHeuresCoursFromMinutes = $totalMinutesCours > 0 ? round($totalMinutesCours / 60.0, 2) : 0.0;
        $vhHuman = CommissionCalculationService::formatTotalMinutesAsFrenchHourLabel($totalMinutesCours);
        $attenteHuman = CommissionCalculationService::formatTotalMinutesAsFrenchHourLabel($totalMinutesAttente);

        return [
            'nombre_enseignants' => count($report),
            'total_commissions_dcl' => round($totalDcl, 2),
            'total_commissions_ndcl' => round($totalNdcl, 2),
            'total_a_payer' => round($totalAPayer, 2),
            'total_duree_cours_minutes' => $totalMinutesCours,
            'total_duree_cours_display' => $totalMinutesCours > 0 ? $totalMinutesCours.' min (séances)' : '0 min',
            'total_duree_attente_minutes' => $totalMinutesAttente,
            'total_duree_attente_display' => $totalMinutesAttente > 0
                ? $attenteHuman.' (= '.$totalMinutesAttente.' min)'
                : '0 h',
            'total_heures_cours' => $totalHeuresCoursFromMinutes,
            'total_heures_cours_display' => $totalHeuresCoursFromMinutes >= 0.01
                ? $vhHuman.' (= '.$totalMinutesCours.' min)'
                : '0 h',
        ];
    }
}

