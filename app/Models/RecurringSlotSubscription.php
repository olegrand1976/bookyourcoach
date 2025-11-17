<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class RecurringSlotSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'recurring_slot_id',
        'subscription_instance_id',
        'start_date',
        'end_date',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    /**
     * Le créneau récurrent associé
     */
    public function recurringSlot(): BelongsTo
    {
        return $this->belongsTo(RecurringSlot::class);
    }

    /**
     * L'instance d'abonnement associée
     */
    public function subscriptionInstance(): BelongsTo
    {
        return $this->belongsTo(SubscriptionInstance::class);
    }

    /**
     * Vérifie si la liaison est active
     */
    public function isActive(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        $now = Carbon::now();
        return $now->isBetween($this->start_date, $this->end_date, true);
    }

    /**
     * Marque la liaison comme expirée
     */
    public function expire(): void
    {
        if ($this->status === 'active') {
            $this->status = 'expired';
            $this->save();
        }
    }

    /**
     * Annule la liaison
     */
    public function cancel(string $reason = null): void
    {
        $this->status = 'cancelled';
        $this->save();
    }

    /**
     * Scope pour les liaisons actives
     */
    public function scopeActive($query)
    {
        $now = Carbon::now();
        return $query->where('status', 'active')
            ->where('start_date', '<=', $now)
            ->where('end_date', '>=', $now);
    }

    /**
     * Scope pour les liaisons expirées
     */
    public function scopeExpired($query)
    {
        return $query->where(function ($q) {
            $q->where('status', 'expired')
                ->orWhere(function ($q2) {
                    $q2->where('status', 'active')
                        ->where('end_date', '<', Carbon::now());
                });
        });
    }
}
