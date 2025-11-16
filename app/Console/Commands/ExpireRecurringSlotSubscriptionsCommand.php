<?php

namespace App\Console\Commands;

use App\Services\RecurringSlotService;
use Illuminate\Console\Command;

/**
 * Commande pour expirer automatiquement les liaisons abonnement-créneau récurrent
 * 
 * Cette commande doit être exécutée quotidiennement via un cron job
 * pour mettre à jour le statut des liaisons qui ont dépassé leur date de fin.
 */
class ExpireRecurringSlotSubscriptionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'recurring-slots:expire-subscriptions
                            {--dry-run : Afficher ce qui serait expiré sans le faire}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Expire les liaisons abonnement-créneau récurrent qui ont dépassé leur date de fin';

    /**
     * Execute the console command.
     */
    public function handle(RecurringSlotService $service): int
    {
        $dryRun = $this->option('dry-run');

        $this->info('🔄 Expiration des liaisons abonnement-créneau récurrent');
        $this->newLine();

        if ($dryRun) {
            $this->warn('⚠️  Mode DRY-RUN activé - Aucune modification ne sera effectuée');
            $this->newLine();
        }

        try {
            if ($dryRun) {
                // En mode dry-run, on compte seulement
                $expiredLinks = \App\Models\RecurringSlotSubscription::where('status', 'active')
                    ->where('end_date', '<', now())
                    ->get();

                $this->info("📊 Liaisons qui seraient expirées : {$expiredLinks->count()}");

                if ($expiredLinks->count() > 0) {
                    $this->table(
                        ['ID', 'Créneau', 'Abonnement', 'Date de fin', 'Statut'],
                        $expiredLinks->map(function ($link) {
                            return [
                                $link->id,
                                $link->recurring_slot_id,
                                $link->subscription_instance_id,
                                $link->end_date->format('Y-m-d'),
                                $link->status,
                            ];
                        })->toArray()
                    );
                }
            } else {
                // Expiration réelle
                $count = $service->expireSubscriptionLinks();

                if ($count > 0) {
                    $this->info("✅ {$count} liaison(s) expirée(s) avec succès");
                } else {
                    $this->info('ℹ️  Aucune liaison à expirer');
                }
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("❌ Erreur lors de l'expiration : {$e->getMessage()}");
            return Command::FAILURE;
        }
    }
}
