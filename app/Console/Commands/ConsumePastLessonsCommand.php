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
            // Récupérer tous les cours attachés qui sont passés mais pas encore consommés
            $pastLessons = $instance->lessons()
                ->where('start_time', '<=', $now)
                ->whereIn('status', ['pending', 'confirmed', 'completed'])
                ->where('status', '!=', 'cancelled')
                ->get();
            
            foreach ($pastLessons as $lesson) {
                $stats['processed']++;
                
                try {
                    // Vérifier si le cours est déjà compté dans lessons_used
                    // On compte combien de cours passés sont actuellement dans lessons_used
                    $currentConsumedCount = \Illuminate\Support\Facades\DB::table('subscription_lessons')
                        ->join('lessons', 'subscription_lessons.lesson_id', '=', 'lessons.id')
                        ->where('subscription_lessons.subscription_instance_id', $instance->id)
                        ->whereIn('lessons.status', ['pending', 'confirmed', 'completed'])
                        ->where('lessons.status', '!=', 'cancelled')
                        ->where('lessons.start_time', '<=', $now)
                        ->count();
                    
                    // Si lessons_used est inférieur au nombre de cours passés, il y a des cours non consommés
                    if ($instance->lessons_used < $currentConsumedCount) {
                        // Il y a des cours passés non encore consommés
                        // Consommer la différence
                        $toConsume = $currentConsumedCount - $instance->lessons_used;
                        $oldLessonsUsed = $instance->lessons_used;
                        $instance->lessons_used = $instance->lessons_used + $toConsume;
                        $instance->saveQuietly();
                        
                        $stats['consumed'] += $toConsume;
                        
                        Log::info("✅ Cours passés consommés automatiquement", [
                            'subscription_instance_id' => $instance->id,
                            'old_lessons_used' => $oldLessonsUsed,
                            'new_lessons_used' => $instance->lessons_used,
                            'courses_consumed' => $toConsume,
                            'total_past_lessons' => $currentConsumedCount,
                        ]);
                    } else {
                        $stats['skipped']++;
                    }
                } catch (\Exception $e) {
                    $stats['errors']++;
                    Log::error("Erreur lors de la consommation des cours passés", [
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

