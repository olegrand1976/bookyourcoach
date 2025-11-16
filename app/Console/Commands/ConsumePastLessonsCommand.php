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

        // Récupérer tous les abonnements actifs
        $subscriptionInstances = SubscriptionInstance::where('status', 'active')
            ->with(['lessons' => function ($query) use ($now) {
                // Charger seulement les cours futurs qui sont maintenant passés
                $query->where('start_time', '<=', $now)
                      ->where('start_time', '>', $now->copy()->subHours(24)) // Seulement les cours des dernières 24h
                      ->whereIn('status', ['pending', 'confirmed'])
                      ->where('status', '!=', 'cancelled');
            }])
            ->get();

        foreach ($subscriptionInstances as $instance) {
            foreach ($instance->lessons as $lesson) {
                $stats['processed']++;
                
                try {
                    // Vérifier si le cours est déjà consommé (en vérifiant lessons_used)
                    // On recalcule pour voir si le cours est déjà compté
                    $oldLessonsUsed = $instance->lessons_used;
                    $instance->recalculateLessonsUsed();
                    $newLessonsUsed = $instance->lessons_used;
                    
                    // Si lessons_used n'a pas changé, c'est que le cours n'était pas encore consommé
                    if ($oldLessonsUsed === $newLessonsUsed) {
                        // Le cours n'est pas encore consommé, le consommer maintenant
                        $instance->lessons_used = $instance->lessons_used + 1;
                        $instance->saveQuietly();
                        
                        $stats['consumed']++;
                        
                        Log::info("✅ Cours passé consommé automatiquement", [
                            'lesson_id' => $lesson->id,
                            'lesson_start_time' => $lesson->start_time,
                            'subscription_instance_id' => $instance->id,
                            'old_lessons_used' => $oldLessonsUsed,
                            'new_lessons_used' => $instance->lessons_used,
                        ]);
                    } else {
                        $stats['skipped']++;
                    }
                } catch (\Exception $e) {
                    $stats['errors']++;
                    Log::error("Erreur lors de la consommation du cours passé", [
                        'lesson_id' => $lesson->id,
                        'subscription_instance_id' => $instance->id,
                        'error' => $e->getMessage(),
                    ]);
                }
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

