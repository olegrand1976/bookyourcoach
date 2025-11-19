<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CommissionCalculationService;
use App\Models\SubscriptionInstance;
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
            
            // Empêcher la génération de rapports dans le futur
            $currentYear = (int) date('Y');
            $currentMonth = (int) date('m');
            
            if ($year > $currentYear || ($year === $currentYear && $month > $currentMonth)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de générer un rapport pour une période future'
                ], 422);
            }

            // Générer le rapport pour ce club uniquement
            $report = $this->commissionCalculationService->generatePayrollReport($year, $month, $clubId);

            // Calculer les statistiques
            $stats = $this->calculateStats($report, $year, $month, $clubId);

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
                $stats = $this->calculateStats($report, $year, $month, $clubId);

                $reports[] = [
                    'year' => $year,
                    'month' => $month,
                    'month_name' => Carbon::create($year, $month, 1)->locale('fr')->monthName,
                    'statistics' => $stats,
                    'teachers_count' => count($report),
                    'generated_at' => now()->toDateTimeString(),
                ];
            } else {
                // Sinon, déterminer les périodes disponibles à partir des données en base ET des rapports générés
                // 1. Obtenir les périodes avec des cours
                $availablePeriods = $this->commissionCalculationService->getAvailableReportPeriods($clubId);
                
                // 2. Obtenir aussi les périodes des rapports générés dans le storage (même sans cours)
                $files = Storage::files("payroll_reports/clubs");
                $generatedPeriods = [];
                
                foreach ($files as $file) {
                    if (preg_match('/payroll_club_' . $clubId . '_(\d{4})_(\d{1,2})\.json/', $file, $matches)) {
                        $fileYear = (int)$matches[1];
                        $fileMonth = (int)$matches[2];
                        $key = $fileYear . '-' . $fileMonth;
                        
                        // Vérifier si cette période n'est pas déjà dans availablePeriods
                        $exists = false;
                        foreach ($availablePeriods as $period) {
                            if ($period['year'] === $fileYear && $period['month'] === $fileMonth) {
                                $exists = true;
                                break;
                            }
                        }
                        
                        if (!$exists) {
                            $generatedPeriods[$key] = [
                                'year' => $fileYear,
                                'month' => $fileMonth,
                            ];
                        }
                    }
                }
                
                // Combiner les deux listes
                $allPeriods = array_merge($availablePeriods, array_values($generatedPeriods));
                
                // Pour chaque période, régénérer le rapport avec les données actuelles
                foreach ($allPeriods as $period) {
                    $periodYear = $period['year'];
                    $periodMonth = $period['month'];
                    
                    // Régénérer le rapport avec les données actuelles (pas depuis les fichiers JSON)
                    $report = $this->commissionCalculationService->generatePayrollReport($periodYear, $periodMonth, $clubId);
                    $stats = $this->calculateStats($report, $periodYear, $periodMonth, $clubId);

                    $reports[] = [
                        'year' => $periodYear,
                        'month' => $periodMonth,
                        'month_name' => Carbon::create($periodYear, $periodMonth, 1)->locale('fr')->monthName,
                        'statistics' => $stats,
                        'teachers_count' => count($report),
                        'generated_at' => now()->toDateTimeString(), // Toujours à jour car régénéré
                    ];
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
            $stats = $this->calculateStats($report, $year, $month, $clubId);

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
     * Récupérer les détails des paiements pour un enseignant dans une période
     * Inclut les cours individuels et les abonnements avec leurs détails
     * 
     * @param Request $request
     * @param int $year
     * @param int $month
     * @param int $teacherId
     * @return JsonResponse
     */
    public function getTeacherPaymentsDetails(Request $request, int $year, int $month, int $teacherId): JsonResponse
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

            $startDate = Carbon::create($year, $month, 1)->startOfMonth();
            $endDate = Carbon::create($year, $month, 1)->endOfMonth();

            // Récupérer les cours individuels payés durant la période
            $lessons = \App\Models\Lesson::where('teacher_id', $teacherId)
                ->where('club_id', $clubId)
                ->whereBetween('date_paiement', [$startDate, $endDate])
                ->whereNotNull('date_paiement')
                ->whereIn('status', ['confirmed', 'completed'])
                ->whereDoesntHave('subscriptionInstances')
                ->where(function($query) {
                    $query->where(function($q) {
                        $q->whereNotNull('montant')->where('montant', '>', 0);
                    })->orWhere(function($q) {
                        $q->whereNull('montant')->orWhere('montant', '<=', 0);
                        $q->whereNotNull('price')->where('price', '>', 0);
                    });
                })
                ->with(['courseType', 'student.user', 'students.user'])
                ->orderBy('date_paiement')
                ->orderBy('start_time')
                ->get()
                ->map(function($lesson) {
                    $amount = $lesson->montant ?? $lesson->price ?? 0;
                    // RÈGLE : est_legacy === false → DCL, sinon (true ou null) → NDCL
                    // Utiliser le même taux pour les deux (1.00 = 100%)
                    $commission = round($amount * 1.00, 2);
                    
                    return [
                        'id' => $lesson->id,
                        'type' => 'lesson',
                        'date' => $lesson->start_time->format('Y-m-d'),
                        'date_paiement' => $lesson->date_paiement ? $lesson->date_paiement->format('Y-m-d') : null,
                        'time' => $lesson->start_time->format('H:i'),
                        'course_type' => $lesson->courseType ? $lesson->courseType->name : 'Non défini',
                        'student_name' => $lesson->student?->user?->name ?? ($lesson->students->first()?->user?->name ?? 'Non défini'),
                        'price' => (float) $lesson->price,
                        'montant' => $lesson->montant ? (float) $lesson->montant : null,
                        'montant_initial' => (float) $lesson->price,
                        'commission' => $commission,
                        'est_legacy' => $lesson->est_legacy ?? null, // Garder null pour l'affichage (null = NDCL)
                        'status' => $lesson->status,
                        'is_manual_override' => $lesson->montant !== null && $lesson->montant != $lesson->price,
                    ];
                });

            // Récupérer les abonnements payés durant la période
            $subscriptions = SubscriptionInstance::whereHas('subscription', function($q) use ($clubId) {
                    $q->where('club_id', $clubId);
                })
                ->where(function($q) use ($teacherId) {
                    // Chercher par teacher_id direct ou via les cours liés
                    $q->where('teacher_id', $teacherId)
                      ->orWhereHas('lessons', function($query) use ($teacherId) {
                          $query->where('teacher_id', $teacherId);
                      });
                })
                ->whereBetween('date_paiement', [$startDate, $endDate])
                ->whereNotNull('montant')
                ->where('montant', '>', 0)
                ->whereIn('status', ['active', 'completed'])
                ->with(['subscription', 'teacher.user'])
                ->orderBy('date_paiement')
                ->get()
                ->map(function($instance) {
                    // Utiliser le service pour calculer la commission (cohérent avec generatePayrollReport)
                    // Note: Le service gère correctement est_legacy === false (DCL) vs true/null (NDCL)
                    $commission = $this->commissionCalculationService->calculateCommission($instance);
                    
                    return [
                        'id' => $instance->id,
                        'type' => 'subscription',
                        'date_paiement' => $instance->date_paiement ? $instance->date_paiement->format('Y-m-d') : null,
                        'subscription_name' => $instance->subscription?->name ?? 'Abonnement',
                        'montant' => (float) $instance->montant,
                        'montant_initial' => (float) $instance->montant,
                        'commission' => $commission,
                        'est_legacy' => $instance->est_legacy ?? null, // Garder null pour l'affichage (null = NDCL)
                        'status' => $instance->status,
                        'is_manual_override' => false, // Les abonnements n'ont pas de montant initial différent
                    ];
                });

            // Calculer les totaux
            $totalLessons = $lessons->sum('commission');
            $totalSubscriptions = $subscriptions->sum('commission');
            $totalDcl = $lessons->where('est_legacy', false)->sum('commission') + 
                       $subscriptions->where('est_legacy', false)->sum('commission');
            $totalNdcl = $lessons->where('est_legacy', true)->sum('commission') + 
                        $subscriptions->where('est_legacy', true)->sum('commission');

            return response()->json([
                'success' => true,
                'data' => [
                    'teacher_id' => $teacherId,
                    'period' => [
                        'year' => $year,
                        'month' => $month,
                        'month_name' => Carbon::create($year, $month, 1)->locale('fr')->monthName,
                    ],
                    'lessons' => $lessons->values(),
                    'subscriptions' => $subscriptions->values(),
                    'totals' => [
                        'total_lessons' => round($totalLessons, 2),
                        'total_subscriptions' => round($totalSubscriptions, 2),
                        'total_commissions_dcl' => round($totalDcl, 2),
                        'total_commissions_ndcl' => round($totalNdcl, 2),
                        'total_a_payer' => round($totalLessons + $totalSubscriptions, 2),
                    ],
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error("Erreur lors de la récupération des détails des paiements", [
                'error' => $e->getMessage(),
                'year' => $year,
                'month' => $month,
                'teacher_id' => $teacherId
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des détails',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mettre à jour les paiements (valider, modifier, reporter)
     * 
     * @param Request $request
     * @param int $year
     * @param int $month
     * @param int $teacherId
     * @return JsonResponse
     */
    public function updatePayments(Request $request, int $year, int $month, int $teacherId): JsonResponse
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
                'updates' => 'required|array',
                'updates.*.id' => 'required|integer',
                'updates.*.type' => 'required|in:lesson,subscription',
                'updates.*.action' => 'required|in:validate,modify,defer',
                'updates.*.montant' => 'nullable|numeric|min:0',
                'updates.*.date_paiement' => 'nullable|date',
            ]);

            $updates = $request->input('updates', []);
            $updated = [];
            $deferred = [];

            foreach ($updates as $update) {
                if ($update['type'] === 'lesson') {
                    $lesson = \App\Models\Lesson::where('id', $update['id'])
                        ->where('teacher_id', $teacherId)
                        ->where('club_id', $clubId)
                        ->first();

                    if (!$lesson) {
                        continue;
                    }

                    if ($update['action'] === 'validate') {
                        // Valider le paiement : utiliser le montant actuel (price ou montant existant)
                        $lesson->date_paiement = Carbon::create($year, $month, 1)->endOfMonth();
                        if ($lesson->montant === null) {
                            $lesson->montant = $lesson->price;
                        }
                        $lesson->save();
                        $updated[] = ['id' => $lesson->id, 'type' => 'lesson', 'action' => 'validated'];
                    } elseif ($update['action'] === 'modify') {
                        // Modifier le montant et/ou la date
                        if (isset($update['montant'])) {
                            $lesson->montant = $update['montant'];
                        }
                        if (isset($update['date_paiement'])) {
                            $lesson->date_paiement = Carbon::parse($update['date_paiement']);
                        }
                        $lesson->save();
                        $updated[] = ['id' => $lesson->id, 'type' => 'lesson', 'action' => 'modified'];
                    } elseif ($update['action'] === 'defer') {
                        // Reporter au mois suivant
                        $nextMonth = Carbon::create($year, $month, 1)->addMonth();
                        $lesson->date_paiement = $nextMonth->endOfMonth();
                        $lesson->save();
                        $deferred[] = [
                            'id' => $lesson->id,
                            'type' => 'lesson',
                            'new_period' => [
                                'year' => $nextMonth->year,
                                'month' => $nextMonth->month,
                            ]
                        ];
                    }
                } elseif ($update['type'] === 'subscription') {
                    $subscription = SubscriptionInstance::where('id', $update['id'])
                        ->whereHas('subscription', function($q) use ($clubId) {
                            $q->where('club_id', $clubId);
                        })
                        ->where(function($q) use ($teacherId) {
                            $q->where('teacher_id', $teacherId)
                              ->orWhereHas('lessons', function($query) use ($teacherId) {
                                  $query->where('teacher_id', $teacherId);
                              });
                        })
                        ->first();

                    if (!$subscription) {
                        continue;
                    }

                    if ($update['action'] === 'validate') {
                        // Valider le paiement
                        $subscription->date_paiement = Carbon::create($year, $month, 1)->endOfMonth();
                        $subscription->save();
                        $updated[] = ['id' => $subscription->id, 'type' => 'subscription', 'action' => 'validated'];
                    } elseif ($update['action'] === 'modify') {
                        // Modifier le montant et/ou la date
                        if (isset($update['montant'])) {
                            $subscription->montant = $update['montant'];
                        }
                        if (isset($update['date_paiement'])) {
                            $subscription->date_paiement = Carbon::parse($update['date_paiement']);
                        }
                        $subscription->save();
                        $updated[] = ['id' => $subscription->id, 'type' => 'subscription', 'action' => 'modified'];
                    } elseif ($update['action'] === 'defer') {
                        // Reporter au mois suivant
                        $nextMonth = Carbon::create($year, $month, 1)->addMonth();
                        $subscription->date_paiement = $nextMonth->endOfMonth();
                        $subscription->save();
                        $deferred[] = [
                            'id' => $subscription->id,
                            'type' => 'subscription',
                            'new_period' => [
                                'year' => $nextMonth->year,
                                'month' => $nextMonth->month,
                            ]
                        ];
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Paiements mis à jour avec succès',
                'data' => [
                    'updated' => $updated,
                    'deferred' => $deferred,
                ]
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error("Erreur lors de la mise à jour des paiements", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour des paiements',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Recharger un rapport avec option de réinitialiser ou garder les modifications
     * 
     * @param Request $request
     * @param int $year
     * @param int $month
     * @return JsonResponse
     */
    public function reloadReport(Request $request, int $year, int $month): JsonResponse
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
                'reset_manual_changes' => 'boolean',
            ]);

            $resetManualChanges = $request->input('reset_manual_changes', false);

            // Si on réinitialise, remettre les montants manuels à null pour les cours
            if ($resetManualChanges) {
                $startDate = Carbon::create($year, $month, 1)->startOfMonth();
                $endDate = Carbon::create($year, $month, 1)->endOfMonth();

                \App\Models\Lesson::where('club_id', $clubId)
                    ->whereBetween('date_paiement', [$startDate, $endDate])
                    ->whereNotNull('montant')
                    ->whereRaw('montant != price')
                    ->update(['montant' => null]);
            }

            // Régénérer le rapport
            $report = $this->commissionCalculationService->generatePayrollReport($year, $month, $clubId);
            $stats = $this->calculateStats($report, $year, $month, $clubId);

            return response()->json([
                'success' => true,
                'message' => 'Rapport rechargé avec succès',
                'data' => [
                    'report' => $report,
                    'statistics' => $stats,
                    'period' => [
                        'year' => $year,
                        'month' => $month,
                        'month_name' => Carbon::create($year, $month, 1)->locale('fr')->monthName,
                    ],
                    'reloaded_at' => now()->toDateTimeString(),
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error("Erreur lors du rechargement du rapport", [
                'error' => $e->getMessage(),
                'year' => $year,
                'month' => $month
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du rechargement du rapport',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculer les statistiques globales d'un rapport
     * 
     * @param array $report
     * @param int $year
     * @param int $month
     * @param int|null $clubId
     * @return array
     */
    private function calculateStats(array $report, int $year, int $month, ?int $clubId = null): array
    {
        $totalDcl = 0;
        $totalNdcl = 0;
        $totalAPayer = 0;

        foreach ($report as $data) {
            $totalDcl += $data['total_commissions_dcl'] ?? 0;
            $totalNdcl += $data['total_commissions_ndcl'] ?? 0;
            $totalAPayer += $data['total_a_payer'];
        }

        // Calculer le report du mois suivant (négatif)
        // Ce sont les paiements qui ont été reportés du mois actuel vers le mois suivant
        // Ces montants doivent être soustraits du total car ils ne seront pas payés ce mois-ci
        $currentMonthStart = Carbon::create($year, $month, 1)->startOfMonth();
        $currentMonthEnd = Carbon::create($year, $month, 1)->endOfMonth();
        $nextMonthStart = Carbon::create($year, $month, 1)->addMonth()->startOfMonth();
        $nextMonthEnd = Carbon::create($year, $month, 1)->addMonth()->endOfMonth();
        
        $reportMoisSuivant = 0;
        
        // Chercher les cours qui ont été reportés vers le mois suivant
        // Ce sont des cours créés avant ou pendant le mois actuel mais avec date_paiement dans le mois suivant
        $deferredLessonsQuery = \App\Models\Lesson::whereBetween('date_paiement', [$nextMonthStart, $nextMonthEnd])
            ->whereNotNull('date_paiement')
            ->whereIn('status', ['confirmed', 'completed'])
            ->whereDoesntHave('subscriptionInstances')
            ->where(function($q) use ($currentMonthEnd) {
                // Le cours doit avoir été créé avant ou pendant le mois actuel
                $q->where('created_at', '<=', $currentMonthEnd);
            });
            
        if ($clubId !== null) {
            $deferredLessonsQuery->where('club_id', $clubId);
        }
        
        $deferredLessons = $deferredLessonsQuery->get();
        
        foreach ($deferredLessons as $lesson) {
            $amount = $lesson->montant ?? $lesson->price ?? 0;
            if ($amount > 0) {
                // Calculer la commission selon le type
                if ($lesson->est_legacy === false) {
                    $commission = $amount * 1.00; // DCL
                } else {
                    $commission = $amount * 1.00; // NDCL
                }
                $reportMoisSuivant += $commission;
            }
        }
        
        // Chercher les abonnements reportés vers le mois suivant
        $deferredSubscriptionsQuery = SubscriptionInstance::whereBetween('date_paiement', [$nextMonthStart, $nextMonthEnd])
            ->whereNotNull('date_paiement')
            ->whereNotNull('montant')
            ->where('montant', '>', 0)
            ->whereIn('status', ['active', 'completed'])
            ->where(function($q) use ($currentMonthEnd) {
                // L'abonnement doit avoir été créé avant ou pendant le mois actuel
                $q->where('created_at', '<=', $currentMonthEnd);
            });
            
        if ($clubId !== null) {
            $deferredSubscriptionsQuery->whereHas('subscription', function($q) use ($clubId) {
                $q->where('club_id', $clubId);
            });
        }
        
        $deferredSubscriptions = $deferredSubscriptionsQuery->get();
        
        foreach ($deferredSubscriptions as $subscription) {
            $commission = $this->commissionCalculationService->calculateCommission($subscription);
            $reportMoisSuivant += $commission;
        }
        
        // Le report est négatif car ce sont des montants soustraits du total du mois actuel
        $reportMoisSuivant = -abs($reportMoisSuivant);
        
        // Total payé = total à payer + report mois suivant (qui est négatif)
        $totalPaye = $totalAPayer + $reportMoisSuivant;

        return [
            'nombre_enseignants' => count($report),
            'total_commissions_dcl' => round($totalDcl, 2),
            'total_commissions_ndcl' => round($totalNdcl, 2),
            'total_a_payer' => round($totalAPayer, 2),
            'report_mois_suivant' => round($reportMoisSuivant, 2), // Négatif
            'total_paye' => round($totalPaye, 2),
        ];
    }
}

