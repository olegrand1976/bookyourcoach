<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\SubscriptionInstance;
use Illuminate\Console\Command;

/**
 * Remet en « actif » les abonnements restés « expirés » alors que leur date de fin est à venir
 * (dates repoussées avant le correctif de SubscriptionController::updateInstance).
 */
class ReopenExpiredSubscriptionsCommand extends Command
{
    protected $signature = 'subscriptions:reopen-expired
                            {--ids= : IDs d\'instances séparés par des virgules (sinon toutes)}
                            {--apply : Écrire (simulation par défaut)}';

    protected $description = 'Réactive les abonnements « expirés » dont la date de fin est à venir';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $ids = array_filter(array_map('intval', explode(',', (string) $this->option('ids'))));

        $instances = SubscriptionInstance::query()
            ->where('status', 'expired')
            ->where('expires_at', '>=', now()->startOfDay())
            ->when($ids !== [], fn ($q) => $q->whereIn('id', $ids))
            ->with('subscription.template')
            ->orderBy('id')
            ->get();

        $rows = [];
        foreach ($instances as $instance) {
            if (! $instance->reopenIfNotYetExpired()) {
                continue;
            }

            $rows[] = [
                $instance->id,
                $instance->subscription?->subscription_number,
                $instance->started_at?->format('Y-m-d'),
                $instance->expires_at?->format('Y-m-d'),
                $instance->lessons_used,
            ];

            if (! $apply) {
                continue;
            }

            $instance->saveQuietly();

            AuditLog::create([
                'user_id' => null,
                'action' => 'subscription_instance_updated',
                'model_type' => SubscriptionInstance::class,
                'model_id' => $instance->id,
                'data' => [
                    'changes' => ['status' => ['old' => 'expired', 'new' => 'active']],
                    'instance_id' => $instance->id,
                    'subscription_id' => $instance->subscription_id,
                    'source' => 'subscriptions:reopen-expired',
                ],
            ]);
        }

        $this->table(['Instance', 'N°', 'Début', 'Fin', 'Cours utilisés'], $rows);
        $this->info(($apply ? 'Réactivés : ' : 'À réactiver (simulation) : ').count($rows));

        return self::SUCCESS;
    }
}
