<?php

namespace App\Console\Commands;

use App\Mail\ClubDailyPlanningInsightMail;
use App\Models\Club;
use App\Services\AI\GeminiService;
use App\Services\ClubPlanningInsightService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendClubDailyPlanningInsightsCommand extends Command
{
    protected $signature = 'club:send-daily-planning-insights
                            {--club= : Limiter à un club_id}
                            {--date= : Jour cible (Y-m-d), défaut = lendemain (timezone Brussels)}
                            {--dry-run : Ne pas envoyer d’e-mail ni cache}
                            {--force : Ignorer le cache d’idempotence}';

    protected $description = 'Envoie aux responsables club le planning du lendemain avec analyse IA (contraintes fratrie / abo).';

    public function __construct(
        protected ClubPlanningInsightService $planningInsightService,
        protected GeminiService $geminiService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $tz = (string) config('bookyourcoach.club_daily_planning_insight.timezone', 'Europe/Brussels');
        $target = $this->option('date')
            ? Carbon::parse((string) $this->option('date'), $tz)->startOfDay()
            : now($tz)->addDay()->startOfDay();

        $clubQuery = Club::query()->where('is_active', true);
        if ($this->option('club')) {
            $clubQuery->where('id', (int) $this->option('club'));
        }

        $clubs = $clubQuery->orderBy('id')->get();
        $sent = 0;
        $skipped = 0;

        foreach ($clubs as $club) {
            $payload = $this->planningInsightService->buildPayload($club, $target, $tz);

            if (($payload['lessons'] ?? []) === []) {
                $skipped++;
                continue;
            }

            $recipients = $club->stakeholderUsersNotifiableByMail();
            if ($recipients->isEmpty()) {
                $this->line("Club #{$club->id} : aucun e-mail responsable — skip.");
                $skipped++;
                continue;
            }

            $cacheKey = 'club_planning_insight_sent:'.$club->id.':'.$target->toDateString();
            if (! $this->option('force') && ! $this->option('dry-run') && Cache::has($cacheKey)) {
                $this->line("Club #{$club->id} : déjà envoyé pour {$target->toDateString()} — skip (utilisez --force).");
                $skipped++;
                continue;
            }

            $analysis = config('bookyourcoach.club_daily_planning_insight.use_ai', true)
                ? $this->geminiService->analyzeClubDailyPlanning($payload)
                : null;

            if ($analysis === null && $this->geminiService->isAvailable()) {
                Log::warning('Club planning insight: Gemini parse failed or empty', [
                    'club_id' => $club->id,
                    'date' => $target->toDateString(),
                ]);
            }

            if ($this->option('dry-run')) {
                $this->info("DRY-RUN club #{$club->id} {$club->name} — {$target->toDateString()} — "
                    .count($payload['lessons']).' cours, '
                    .count($payload['family_constraint_groups']).' groupes contrainte.');
                $sent++;
                continue;
            }

            $dateLabel = $target->locale('fr')->isoFormat('dddd D MMMM YYYY');
            $mail = new ClubDailyPlanningInsightMail(
                $club->name,
                $dateLabel,
                $analysis,
                $payload
            );

            $to = $recipients->first();
            $cc = $recipients->slice(1)->pluck('email')->filter()->values()->all();

            Mail::to($to->email)->cc($cc)->send($mail);

            Cache::put($cacheKey, 1, now()->addHours(48));
            $sent++;

            Log::info('Club daily planning insight sent', [
                'club_id' => $club->id,
                'target' => $target->toDateString(),
                'recipients' => $recipients->pluck('email')->values()->all(),
                'had_ai' => $analysis !== null,
            ]);
        }

        $this->info("Terminé : envoyés / traités {$sent}, ignorés {$skipped}.");

        return self::SUCCESS;
    }
}
