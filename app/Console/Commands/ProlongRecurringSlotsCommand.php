<?php

namespace App\Console\Commands;

use App\Services\ProlongRecurringSlotsService;
use Illuminate\Console\Command;

/**
 * Prolonge d'une semaine les récurrences des abonnements actifs pour maintenir
 * un horizon de 26 semaines. À exécuter chaque dimanche.
 */
class ProlongRecurringSlotsCommand extends Command
{
    protected $signature = 'recurring-slots:prolong
                            {--dry-run : Afficher ce qui serait prolongé sans le faire}';

    protected $description = 'Prolonge les récurrences des abonnements actifs d\'une semaine (horizon 26 semaines)';

    public function handle(ProlongRecurringSlotsService $service): int
    {
        $dryRun = $this->option('dry-run');

        $this->info('🔄 Prolongation des récurrences (abonnements actifs, horizon 26 semaines)');
        $this->newLine();

        if ($dryRun) {
            $this->warn('⚠️  Mode DRY-RUN activé - Aucune modification ne sera effectuée');
            $this->newLine();
            $slots = \App\Models\SubscriptionRecurringSlot::query()
                ->where('status', 'active')
                ->whereHas('subscriptionInstance', function ($q) {
                    $q->where('status', 'active');
                    $q->where(function ($q2) {
                        $q2->whereNull('expires_at')
                            ->orWhere('expires_at', '>=', now()->toDateString());
                    });
                })
                ->with('subscriptionInstance')
                ->get();
            $today = now()->startOfDay();
            $horizonEnd = $today->copy()->addWeeks(ProlongRecurringSlotsService::HORIZON_WEEKS);
            $wouldProlong = 0;
            foreach ($slots as $slot) {
                $instance = $slot->subscriptionInstance;
                $targetEnd = $horizonEnd->copy();
                if ($instance && $instance->expires_at) {
                    $expires = \Carbon\Carbon::parse($instance->expires_at)->endOfDay();
                    if ($expires->isBefore($targetEnd)) {
                        $targetEnd = $expires;
                    }
                }
                $currentEnd = \Carbon\Carbon::parse($slot->end_date)->startOfDay();
                if ($currentEnd->lt($targetEnd)) {
                    $wouldProlong++;
                }
            }
            $this->info("📊 Récurrences qui seraient prolongées : {$wouldProlong}");
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
            $this->error('❌ Erreur : ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
