<?php

namespace App\Console\Commands;

use App\Models\SubscriptionInstance;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Commande pour consommer automatiquement les cours dont la date/heure est passée
 * 
 * Cette commande doit être exécutée régulièrement (toutes les heures ou toutes les 30 minutes)
 * pour consommer les cours futurs qui sont maintenant passés
 */
class ConsumePastLessonsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:consume-past-lessons';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Consomme automatiquement les cours dont la date/heure est passée';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔄 Consommation des cours passés...');
        $this->newLine();

        $now = Carbon::now();
        $stats = [
            'processed' => 0,
            'consumed' => 0,
            'skipped' => 0,
            'errors' => 0,
        ];

        // Récupérer tous les abonnements actifs qui ont des cours attachés
        $subscriptionInstances = SubscriptionInstance::where('status', 'active')
            ->whereHas('lessons', function ($query) use ($now) {
                // Chercher les abonnements qui ont des cours passés
                $query->where('start_time', '<=', $now)
                      ->whereIn('status', ['pending', 'confirmed', 'completed'])
                      ->where('status', '!=', 'cancelled');
            })
            ->get();

        foreach ($subscriptionInstances as $instance) {
            try {
                $oldLessonsUsed = $instance->lessons_used;
                
                // Recalculer lessons_used (ne compte que les cours passés)
                $instance->recalculateLessonsUsed();
                
                // Si lessons_used a changé, c'est qu'il y avait des cours passés non consommés
                if ($instance->lessons_used != $oldLessonsUsed) {
                    $consumed = $instance->lessons_used - $oldLessonsUsed;
                    $stats['consumed'] += $consumed;
                    $stats['processed']++;
                    
                    Log::info("✅ Cours passés consommés automatiquement", [
                        'subscription_instance_id' => $instance->id,
                        'old_lessons_used' => $oldLessonsUsed,
                        'new_lessons_used' => $instance->lessons_used,
                        'courses_consumed' => $consumed,
                    ]);
                } else {
                    $stats['skipped']++;
                }
            } catch (\Exception $e) {
                $stats['errors']++;
                Log::error("Erreur lors de la consommation des cours passés", [
                    'subscription_instance_id' => $instance->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->table(
            ['Métrique', 'Valeur'],
            [
                ['Abonnements traités', $subscriptionInstances->count()],
                ['Cours traités', $stats['processed']],
                ['Cours consommés', $stats['consumed']],
                ['Cours déjà consommés', $stats['skipped']],
                ['Erreurs', $stats['errors']],
            ]
        );

        if ($stats['consumed'] > 0) {
            $this->info("✅ {$stats['consumed']} cours(s) consommé(s) automatiquement");
        } else {
            $this->info("ℹ️  Aucun nouveau cours à consommer");
        }

        return Command::SUCCESS;
    }
}

