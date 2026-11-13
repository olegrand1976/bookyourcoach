<?php

namespace App\Console\Commands;

use App\Mail\ClubPlanningConsistencyMail;
use App\Models\Club;
use App\Services\PlanningConsistencyCheckService;
use App\Services\PlanningIssueReporter;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Veille planning : la veille de chaque jour de cours, contrôle le planning du lendemain et prévient
 * les responsables du club s'il présente des incohérences à corriger. Rien n'est envoyé sinon.
 * Ouvre aussi une issue GitHub (sans donnée personnelle) pour analyse et correction par Claude.
 */
class CheckPlanningConsistencyCommand extends Command
{
    protected $signature = 'planning:check-consistency
                            {--club= : Limiter à un club_id}
                            {--date= : Jour contrôlé (Y-m-d), défaut = demain}
                            {--days=1 : Nombre de jours contrôlés à partir de --date (rapport console uniquement si > 1)}
                            {--dry-run : Afficher le rapport sans envoyer d’e-mail}
                            {--force : Ignorer le cache d’idempotence}';

    protected $description = 'Veille planning : signale aux responsables du club les incohérences du planning du lendemain.';

    public function __construct(
        private readonly PlanningConsistencyCheckService $checker,
        private readonly PlanningIssueReporter $issueReporter,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $tz = (string) config('bookyourcoach.subscription_expiry_watch.timezone', 'Europe/Brussels');
        $first = $this->option('date')
            ? Carbon::parse((string) $this->option('date'), $tz)->startOfDay()
            : now($tz)->addDay()->startOfDay();
        $days = max(1, (int) $this->option('days'));
        $dryRun = (bool) $this->option('dry-run') || $days > 1;

        $clubs = Club::query()->where('is_active', true)
            ->when($this->option('club'), fn ($q) => $q->where('id', (int) $this->option('club')))
            ->orderBy('id')->get();

        $sent = 0;
        $flaggedDays = 0;

        foreach ($clubs as $club) {
            for ($d = 0; $d < $days; $d++) {
                $target = $first->copy()->addDays($d);
                $report = $this->checker->checkDay($club, $target);
                // Seuls les points « à corriger » déclenchent l'alerte ; les « à vérifier » l'accompagnent.
                if ($report['counts']['critical'] === 0) {
                    continue;
                }
                $flaggedDays++;

                if ($dryRun) {
                    $c = $report['counts'];
                    $this->info("club #{$club->id} {$club->name} — {$report['date']} — {$report['lessons_count']} cours — "
                        ."à corriger {$c['critical']}, à vérifier {$c['warning']}");
                    foreach ($report['issues'] as $i) {
                        $this->line("  [{$i['level']}] {$i['time']} {$i['title']} — {$i['detail']}");
                    }

                    continue;
                }

                $recipients = $club->stakeholderUsersNotifiableByMail();
                if ($recipients->isEmpty()) {
                    $this->line("Club #{$club->id} : aucun e-mail responsable — skip.");

                    continue;
                }

                $cacheKey = 'planning_consistency_sent:'.$club->id.':'.$report['date'];
                if (! $this->option('force') && Cache::has($cacheKey)) {
                    continue;
                }

                $frontend = rtrim((string) config('app.frontend_url', ''), '/');
                $mail = new ClubPlanningConsistencyMail(
                    $club->name,
                    $target->locale('fr')->isoFormat('dddd D MMMM YYYY'),
                    $report,
                    $frontend !== '' ? $frontend.'/club/planning' : null,
                );

                $to = $recipients->first();
                $cc = $recipients->slice(1)->pluck('email')->filter()->values()->all();
                try {
                    Mail::to($to->email)->cc($cc)->send($mail);
                } catch (\Throwable $e) {
                    Log::error('Veille planning : envoi impossible', ['club_id' => $club->id, 'error' => $e->getMessage()]);

                    continue;
                }

                Cache::put($cacheKey, 1, now()->addHours(36));
                $sent++;

                try {
                    $issueUrl = $this->issueReporter->report($club, $report);
                    if ($issueUrl) {
                        $this->line("Issue ouverte : {$issueUrl}");
                    }
                } catch (\Throwable $e) {
                    Log::warning('Veille planning : issue GitHub impossible', ['club_id' => $club->id, 'error' => $e->getMessage()]);
                }
                Log::info('Veille planning envoyée', ['club_id' => $club->id, 'date' => $report['date'], 'counts' => $report['counts']]);
            }
        }

        $this->info("Terminé : jours signalés {$flaggedDays}, e-mails envoyés {$sent}.");

        return self::SUCCESS;
    }
}
