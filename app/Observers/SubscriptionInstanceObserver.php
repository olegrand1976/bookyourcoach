<?php

namespace App\Observers;

use App\Models\SubscriptionInstance;
use App\Models\SubscriptionRecurringSlot;
use Illuminate\Support\Facades\Log;

class SubscriptionInstanceObserver
{
    /**
     * Handle the SubscriptionInstance "updated" event.
     * Si le statut change vers completed/cancelled/expired, annuler les récurrences
     */
    public function updated(SubscriptionInstance $subscriptionInstance): void
    {
        // Si le statut a changé vers un état terminal
        if ($subscriptionInstance->isDirty('status')) {
            $newStatus = $subscriptionInstance->status;
            $oldStatus = $subscriptionInstance->getOriginal('status');
            
            // Si on passe de 'active' à un statut terminal
            if ($oldStatus === 'active' && in_array($newStatus, ['completed', 'cancelled', 'expired'])) {
                $this->cancelRecurringSlotsForSubscription($subscriptionInstance, $newStatus);
            }
        }
        
        // Si la date d'expiration est prolongée, mettre à jour les récurrences
        if ($subscriptionInstance->isDirty('expires_at')) {
            $this->updateRecurringSlotsEndDate($subscriptionInstance);
        }
    }

    /**
     * Handle the SubscriptionInstance "deleted" event.
     * Annuler toutes les récurrences associées
     */
    public function deleted(SubscriptionInstance $subscriptionInstance): void
    {
        $this->cancelRecurringSlotsForSubscription($subscriptionInstance, 'deleted');
    }

    /**
     * Annule toutes les récurrences actives pour cet abonnement
     */
    private function cancelRecurringSlotsForSubscription(SubscriptionInstance $subscriptionInstance, string $reason): void
    {
        // Récupérer les récurrences valides (sans utiliser la colonne status qui n'existe peut-être pas)
        $recurringSlots = SubscriptionRecurringSlot::where('subscription_instance_id', $subscriptionInstance->id)
            ->where(function($query) {
                $query->where('end_date', '>=', now())
                      ->orWhereNull('end_date');
            })
            ->get()
            ->filter(function($slot) {
                // Filtrer uniquement les récurrences valides
                return method_exists($slot, 'isValid') ? $slot->isValid() : true;
            });

        $cancelledCount = 0;
        foreach ($recurringSlots as $recurringSlot) {
            $recurringSlot->cancel("Abonnement {$reason} (ID: {$subscriptionInstance->id})");
            $cancelledCount++;
        }

        if ($cancelledCount > 0) {
            Log::info("🔄 Récurrences annulées automatiquement", [
                'subscription_instance_id' => $subscriptionInstance->id,
                'reason' => $reason,
                'cancelled_count' => $cancelledCount
            ]);
        }
    }

    /**
     * Met à jour la end_date des récurrences quand l'abonnement est prolongé
     */
    private function updateRecurringSlotsEndDate(SubscriptionInstance $subscriptionInstance): void
    {
        $newExpiresAt = $subscriptionInstance->expires_at;
        
        if (!$newExpiresAt) {
            return;
        }

        // Récupérer les récurrences valides (sans utiliser la colonne status qui n'existe peut-être pas)
        $recurringSlots = SubscriptionRecurringSlot::where('subscription_instance_id', $subscriptionInstance->id)
            ->where(function($query) {
                $query->where('end_date', '>=', now())
                      ->orWhereNull('end_date');
            })
            ->get()
            ->filter(function($slot) {
                // Filtrer uniquement les récurrences valides
                return method_exists($slot, 'isValid') ? $slot->isValid() : true;
            });

        $updatedCount = 0;
        foreach ($recurringSlots as $recurringSlot) {
            // Ne pas dépasser l'horizon de récurrence depuis start_date
            $maxEndDate = \Carbon\Carbon::parse($recurringSlot->start_date)
                ->addWeeks(SubscriptionRecurringSlot::RECURRENCE_WEEKS);
            $newEndDate = min(
                \Carbon\Carbon::parse($newExpiresAt),
                $maxEndDate
            );

            // Mettre à jour uniquement si la nouvelle date est après l'ancienne
            if ($newEndDate->greaterThan($recurringSlot->end_date)) {
                $recurringSlot->end_date = $newEndDate;
                $recurringSlot->save();
                $updatedCount++;
            }
        }

        if ($updatedCount > 0) {
            Log::info("🔄 Récurrences prolongées automatiquement", [
                'subscription_instance_id' => $subscriptionInstance->id,
                'new_expires_at' => $newExpiresAt,
                'updated_count' => $updatedCount
            ]);
        }
    }
}

