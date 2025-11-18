<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CommissionCalculationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Contrôleur pour la gestion des rapports de paie pour les clubs
 * 
 * Permet aux clubs de :
 * - Générer des rapports de paie mensuels pour leurs enseignants
 * - Consulter les rapports existants
 * - Exporter les rapports en JSON ou CSV
 */
class ClubPayrollController extends Controller
{
    protected $commissionCalculationService;

    public function __construct(CommissionCalculationService $commissionCalculationService)
    {
        $this->commissionCalculationService = $commissionCalculationService;
    }

    /**
     * Récupérer le club_id de l'utilisateur connecté
     */
    private function getClubId(Request $request): ?int
    {
        $user = $request->user();
        
        if (!$user || $user->role !== 'club') {
            return null;
        }

        $clubUser = DB::table('club_user')
            ->where('user_id', $user->id)
            ->where('is_admin', true)
            ->first();

        return $clubUser ? $clubUser->club_id : null;
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
            $clubId = $this->getClubId($request);
            
            if (!$clubId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun club associé à cet utilisateur'
                ], 403);
            }

            $request->validate([
                'year' => 'required|integer|min:2020|max:2100',
                'month' => 'required|integer|min:1|max:12',
            ]);

            $year = $request->input('year');
            $month = $request->input('month');

            // Générer le rapport pour ce club uniquement
            $report = $this->commissionCalculationService->generatePayrollReport($year, $month, $clubId);

            // Calculer les statistiques
            $stats = $this->calculateStats($report);

            // Sauvegarder le rapport dans le storage (optionnel)
            $filename = "payroll_club_{$clubId}_{$year}_{$month}.json";
            Storage::put("payroll_reports/clubs/{$filename}", json_encode($report, JSON_PRETTY_PRINT));

            Log::info("Rapport de paie généré pour le club", [
                'club_id' => $clubId,
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
     * Récupérer les rapports de paie disponibles pour le club
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getReports(Request $request): JsonResponse
    {
        try {
            $clubId = $this->getClubId($request);
            
            if (!$clubId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun club associé à cet utilisateur'
                ], 403);
            }

            $year = $request->input('year');
            $month = $request->input('month');

            $reports = [];

            // Si une période spécifique est demandée, générer/récupérer ce rapport
            if ($year && $month) {
                $report = $this->commissionCalculationService->generatePayrollReport($year, $month, $clubId);
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
                // Sinon, lister les rapports disponibles dans le storage pour ce club
                $files = Storage::files("payroll_reports/clubs");
                
                foreach ($files as $file) {
                    if (preg_match('/payroll_club_' . $clubId . '_(\d{4})_(\d{1,2})\.json/', $file, $matches)) {
                        $fileYear = (int)$matches[1];
                        $fileMonth = (int)$matches[2];
                        
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
            $clubId = $this->getClubId($request);
            
            if (!$clubId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun club associé à cet utilisateur'
                ], 403);
            }

            // Valider les paramètres
            if ($year < 2020 || $year > 2100 || $month < 1 || $month > 12) {
                return response()->json([
                    'success' => false,
                    'message' => 'Période invalide'
                ], 422);
            }

            // Générer le rapport pour ce club uniquement
            $report = $this->commissionCalculationService->generatePayrollReport($year, $month, $clubId);
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
            $clubId = $this->getClubId($request);
            
            if (!$clubId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun club associé à cet utilisateur'
                ], 403);
            }

            // Valider les paramètres
            if ($year < 2020 || $year > 2100 || $month < 1 || $month > 12) {
                return response()->json([
                    'success' => false,
                    'message' => 'Période invalide'
                ], 422);
            }

            $report = $this->commissionCalculationService->generatePayrollReport($year, $month, $clubId);
            $monthName = Carbon::create($year, $month, 1)->locale('fr')->monthName;

            $filename = "rapport_paie_club_{$monthName}_{$year}.csv";

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
                    'Commissions DCL (€)',
                    'Commissions NDCL (€)',
                    'Total à Payer (€)'
                ], ';');

                // Données
                foreach ($report as $teacherId => $data) {
                    $dcl = $data['total_commissions_dcl'] ?? 0;
                    $ndcl = $data['total_commissions_ndcl'] ?? 0;
                    
                    fputcsv($handle, [
                        $data['enseignant_id'],
                        $data['nom_enseignant'],
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

        foreach ($report as $data) {
            $totalDcl += $data['total_commissions_dcl'] ?? 0;
            $totalNdcl += $data['total_commissions_ndcl'] ?? 0;
            $totalAPayer += $data['total_a_payer'];
        }

        return [
            'nombre_enseignants' => count($report),
            'total_commissions_dcl' => round($totalDcl, 2),
            'total_commissions_ndcl' => round($totalNdcl, 2),
            'total_a_payer' => round($totalAPayer, 2),
        ];
    }
}

