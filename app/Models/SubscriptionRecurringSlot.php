<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class SubscriptionRecurringSlot extends Model
{
    use HasFactory;

    protected $fillable = [
        'subscription_instance_id',
        'open_slot_id',
        'teacher_id',
        'student_id',
        'day_of_week',
        'start_time',
        'end_time',
        'start_date',  // Utilise start_date au lieu de started_at
        'end_date',    // Utilise end_date au lieu de expires_at
        'status',
        'notes',
    ];

    protected $casts = [
        'day_of_week' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
    ];
    
    // Alias pour compatibilité avec le code
    public function getStartedAtAttribute()
    {
        return $this->start_date;
    }
    
    public function getExpiresAtAttribute()
    {
        return $this->end_date;
    }
    
    public function setStartedAtAttribute($value)
    {
        $this->attributes['start_date'] = $value;
    }
    
    public function setExpiresAtAttribute($value)
    {
        $this->attributes['end_date'] = $value;
    }

    /**
     * La subscription instance associée
     */
    public function subscriptionInstance()
    {
        return $this->belongsTo(SubscriptionInstance::class);
    }

    /**
     * Le créneau ouvert associé (si applicable)
     */
    public function openSlot()
    {
        return $this->belongsTo(ClubOpenSlot::class, 'open_slot_id');
    }

    /**
     * L'enseignant assigné
     */
    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    /**
     * L'élève concerné
     */
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Scope pour les récurrences actives
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now());
    }

    /**
     * Scope pour filtrer par jour de la semaine
     */
    public function scopeByDayOfWeek($query, int $dayOfWeek)
    {
        return $query->where('day_of_week', $dayOfWeek);
    }

    /**
     * Scope pour filtrer par plage horaire
     */
    public function scopeByTimeRange($query, string $startTime, string $endTime)
    {
        return $query->where(function ($q) use ($startTime, $endTime) {
            // Conflit si les plages horaires se chevauchent
            $q->where(function ($sq) use ($startTime, $endTime) {
                $sq->where('start_time', '<', $endTime)
                   ->where('end_time', '>', $startTime);
            });
        });
    }

    /**
     * Scope pour filtrer par enseignant
     */
    public function scopeByTeacher($query, int $teacherId)
    {
        return $query->where('teacher_id', $teacherId);
    }

    /**
     * Scope pour filtrer par créneau
     */
    public function scopeByOpenSlot($query, int $openSlotId)
    {
        return $query->where('open_slot_id', $openSlotId);
    }

    /**
     * Vérifier si cette récurrence est encore valide
     */
    public function isValid(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        $now = Carbon::now();
        return $now->isBetween($this->start_date, $this->end_date, true);
    }

    /**
     * Annuler cette récurrence
     */
    public function cancel(string $reason = null): void
    {
        $this->status = 'cancelled';
        if ($reason) {
            $this->notes = ($this->notes ? $this->notes . "\n" : '') . "Annulé : " . $reason;
        }
        $this->save();
    }

    /**
     * Marquer comme terminée
     */
    public function complete(): void
    {
        $this->status = 'completed';
        $this->save();
    }

    /**
     * Vérifier et mettre à jour le statut si expiré
     */
    public function checkAndUpdateStatus(): void
    {
        if ($this->status === 'active' && Carbon::now()->isAfter($this->end_date)) {
            $this->status = 'expired';
            $this->save();
        }
    }
}
