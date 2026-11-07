<?php

namespace App\Console\Commands;

use App\Mail\ClubSubscriptionExpiryWatchMail;
use App\Models\Club;
use App\Services\SubscriptionExpiryWatchService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Worker de veille : N jours avant chaque jour de cours (config lead_days, défaut 2), signale
 * aux responsables du club les élèves qui ont cours ce jour-là avec un abonnement en fin de vie
 * (dès 3 séances restantes). Rien n'est envoyé si le club n'a pas cours (ou est en congé) ce
 * jour-là, ni si aucun abonnement n'est concerné.
 */
class WatchExpiringSubscriptionsCommand extends Command
{
    protected $signature = 'subscriptions:watch-expiring
                            {--club= : Limiter à un club_id}
                            {--date= : Jour de cours ciblé (Y-m-d), défaut = aujourd’hui + lead_days (2)}
                            {--dry-run : Afficher le rapport sans envoyer d’e-mail}
                            {--force : Ignorer le cache d’idempotence}';

    protected $description = 'Veille : abonnements en fin de vie des élèves ayant cours dans N jours (défaut 2, e-mail par urgence).';

    public function __construct(private readonly SubscriptionExpiryWatchService $watchService)
    {
        parent::__construct();
    }

    /** Nombre de jours entre l'envoi de l'e-mail et le jour de cours signalé. */
    public static function leadDays(): int
    {
        return max(1, (int) config('bookyourcoach.subscription_expiry_watch.lead_days', 2));
    }

    public function handle(): int
    {
        $tz = (string) config('bookyourcoach.subscription_expiry_watch.timezone', 'Europe/Brussels');
        $target = $this->option('date')
            ? Carbon::parse((string) $this->option('date'), $tz)->startOfDay()
            : now($tz)->addDays(self::leadDays())->startOfDay();

        $clubs = Club::query()->where('is_active', true)
            ->when($this->option('club'), fn ($q) => $q->where('id', (int) $this->option('club')))
            ->orderBy('id')->get();

        $sent = 0;
        $skipped = 0;

        foreach ($clubs as $club) {
            $report = $this->watchService->buildReport($club, $target, $target, $tz);
            $flagged = array_sum($report['counts']);

            if ($report['students_with_lessons'] === 0 || $flagged === 0) {
                $skipped++;

                continue;
            }

            if ($this->option('dry-run')) {
                $c = $report['counts'];
                $this->info("DRY-RUN club #{$club->id} {$club->name} — {$target->toDateString()} — "
                    ."critique {$c['critical']}, urgent {$c['urgent']}, à surveiller {$c['watch']}");
                foreach ($this->watchService->flatten($report) as $e) {
                    $this->line("  [{$e['level']}] {$e['student_name']} — ".implode(' · ', $e['reasons']));
                }
                $sent++;

                continue;
            }

            $recipients = $club->stakeholderUsersNotifiableByMail();
            if ($recipients->isEmpty()) {
                $this->line("Club #{$club->id} : aucun e-mail responsable — skip.");
                $skipped++;

                continue;
            }

            $cacheKey = 'subscription_expiry_watch_sent:'.$club->id.':'.$target->toDateString();
            if (! $this->option('force') && Cache::has($cacheKey)) {
                $skipped++;

                continue;
            }

            $frontend = rtrim((string) config('app.frontend_url', ''), '/');
            $mail = new ClubSubscriptionExpiryWatchMail(
                $club->name,
                $target->locale('fr')->isoFormat('dddd D MMMM YYYY'),
                $report,
                $frontend !== '' ? $frontend.'/club/subscriptions' : null,
            );

            $to = $recipients->first();
            $cc = $recipients->slice(1)->pluck('email')->filter()->values()->all();
            try {
                Mail::to($to->email)->cc($cc)->send($mail);
            } catch (\Throwable $e) {
                // Un club en échec ne doit pas priver les suivants de leur veille.
                Log::error('Veille abonnements : envoi impossible', ['club_id' => $club->id, 'error' => $e->getMessage()]);
                $skipped++;

                continue;
            }

            Cache::put($cacheKey, 1, now()->addHours(48));
            $sent++;

            Log::info('Veille abonnements envoyée', [
                'club_id' => $club->id,
                'target' => $target->toDateString(),
                'counts' => $report['counts'],
            ]);
        }

        $this->info("Terminé : envoyés {$sent}, ignorés {$skipped}.");

        return self::SUCCESS;
    }
}
