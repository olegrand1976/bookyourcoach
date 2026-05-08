<?php

namespace App\Console\Commands;

use App\Models\Lesson;
use App\Models\SubscriptionInstance;
use App\Services\CommissionCalculationService;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Synthèse base de données + résultat CommissionCalculationService pour un mois (debug / validation).
 *
 * php artisan payroll:diagnose 2026 4
 */
class PayrollDiagnosticCommand extends Command
{
    protected $signature = 'payroll:diagnose
                            {year? : Année (ex : 2026)}
                            {month? : Mois 1–12}';

    protected $description = 'Affiche des compteurs SQL et le rapport de paie calculé pour une période';

    public function handle(CommissionCalculationService $commissionService): int
    {
        $year = (int) ($this->argument('year') ?: now()->year);
        $month = (int) ($this->argument('month') ?: now()->month);

        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = Carbon::create($year, $month, 1)->endOfMonth();

        $periodClause = static function (\Illuminate\Contracts\Database\Query\Builder $q) use ($start, $end) {
            $q->where(function ($q2) use ($start, $end) {
                $q2->whereNotNull('date_paiement')
                    ->whereBetween('date_paiement', [$start, $end]);
            })->orWhere(function ($q2) use ($start, $end) {
                $q2->whereNull('date_paiement')
                    ->whereBetween('start_time', [$start->copy()->startOfDay(), $end->copy()->endOfDay()]);
            });
        };

        $this->info("Période : {$year}-".str_pad((string) $month, 2, '0', STR_PAD_LEFT).' · timezone '.config('app.timezone'));

        $subPaid = SubscriptionInstance::query()
            ->whereBetween('date_paiement', [$start, $end])
            ->whereNotNull('montant')
            ->where('montant', '>', 0)
            ->whereIn('status', ['active', 'completed'])
            ->count();

        $subWithPivot = SubscriptionInstance::query()
            ->whereBetween('date_paiement', [$start, $end])
            ->whereHas('lessons')
            ->count();

        $this->table(
            ['Métrique', 'Valeur'],
            [
                ['Instances abonnement (paiement dans le mois)', (string) $subPaid],
                ['… dont avec ≥1 cours (pivot)', (string) $subWithPivot],
            ]
        );

        $base = Lesson::query()
            ->whereNotNull('teacher_id')
            ->whereIn('status', ['confirmed', 'completed'])
            ->where(fn ($q) => $periodClause($q));

        $standaloneTeachers = (clone $base)->whereDoesntHave('subscriptionInstances')->distinct()->count('teacher_id');
        $linkedTeachers = (clone $base)->whereHas('subscriptionInstances')->distinct()->count('teacher_id');

        $this->table(
            ['Cours dans le mois', 'Valeur'],
            [
                ['Enseignants distincts (hors carnet pivot)', (string) $standaloneTeachers],
                ['Enseignants distincts (cours sur carnet)', (string) $linkedTeachers],
                ['Lignes cours hors carnet', (string) (clone $base)->whereDoesntHave('subscriptionInstances')->count()],
                ['Lignes cours sur carnet', (string) (clone $base)->whereHas('subscriptionInstances')->count()],
            ]
        );

        $report = $commissionService->generatePayrollReport($year, $month);

        $this->info('CommissionCalculationService : '.count($report).' ligne(s) enseignant');

        foreach ($report as $id => $row) {
            $this->line(sprintf(
                '#%d %s — DCL %.2f NDCL %.2f total %.2f',
                $id,
                $row['nom_enseignant'],
                $row['total_commissions_dcl'],
                $row['total_commissions_ndcl'],
                $row['total_a_payer']
            ));
        }

        return self::SUCCESS;
    }
}
