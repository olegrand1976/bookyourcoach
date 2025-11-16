<?php

namespace App\Jobs;

use App\Services\RecurringSlotService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job pour générer automatiquement les lessons à partir des créneaux récurrents
 * 
 * Ce job peut être exécuté :
 * - Via une commande planifiée (cron)
 * - Manuellement via artisan
 * - Après la création d'un nouveau créneau récurrent
 */
class GenerateRecurringLessonsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Date de début pour la génération (optionnel)
     */
    public ?Carbon $startDate;

    /**
     * Date de fin pour la génération (optionnel)
     */
    public ?Carbon $endDate;

    /**
     * ID du créneau récurrent spécifique (optionnel, null = tous les créneaux actifs)
     */
    public ?int $recurringSlotId;

    /**
     * Create a new job instance.
     */
    public function __construct(
        ?Carbon $startDate = null,
        ?Carbon $endDate = null,
        ?int $recurringSlotId = null
    ) {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->recurringSlotId = $recurringSlotId;
    }

    /**
     * Execute the job.
     */
    public function handle(RecurringSlotService $service): void
    {
        try {
            Log::info('🚀 [GenerateRecurringLessonsJob] Début de la génération', [
                'start_date' => $this->startDate?->format('Y-m-d'),
                'end_date' => $this->endDate?->format('Y-m-d'),
                'recurring_slot_id' => $this->recurringSlotId,
            ]);

            if ($this->recurringSlotId) {
                // Générer pour un créneau spécifique
                $recurringSlot = \App\Models\RecurringSlot::find($this->recurringSlotId);
                
                if (!$recurringSlot) {
                    Log::warning("Créneau récurrent #{$this->recurringSlotId} introuvable");
                    return;
                }

                $stats = $service->generateLessonsForSlot(
                    $recurringSlot,
                    $this->startDate,
                    $this->endDate
                );

                Log::info("✅ [GenerateRecurringLessonsJob] Génération terminée pour créneau #{$this->recurringSlotId}", $stats);
            } else {
                // Générer pour tous les créneaux actifs
                $stats = $service->generateLessonsForAllActiveSlots(
                    $this->startDate,
                    $this->endDate
                );

                Log::info('✅ [GenerateRecurringLessonsJob] Génération terminée pour tous les créneaux', $stats);
            }
        } catch (\Exception $e) {
            Log::error('❌ [GenerateRecurringLessonsJob] Erreur lors de la génération', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            throw $e; // Re-lancer pour que le job soit marqué comme échoué
        }
    }
}
