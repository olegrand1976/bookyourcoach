<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SubscriptionRecurringSlot extends Model
{
    use HasFactory;

    /**
     * Durée max (minutes) pour considérer start_time/end_time comme un créneau « cours ».
     * Au-delà (ex. 14h–21h copié depuis ClubOpenSlot), on n'applique pas le chevauchement horaire
     * contre une leçon ponctuelle : sinon tout créneau dans la journée serait en conflit.
     */
    public const MAX_LESSON_LIKE_WINDOW_MINUTES = 300;

    protected $fillable = [
        'subscription_instance_id',
        'open_slot_id',
        'teacher_id',
        'student_id',
        'day_of_week',
        'start_time',
        'end_time',
        'recurring_interval',  // Intervalle de récurrence en semaines
        'start_date',  // Utilise start_date au lieu de started_at
        'end_date',    // Utilise end_date au lieu de expires_at
        'status',
        'notes',
    ];

    protected $casts = [
        'day_of_week' => 'integer',
        'recurring_interval' => 'integer',
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
     * Scope pour les récurrences actives (à la date du jour)
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now());
    }

    /**
     * Scope pour les récurrences actives à une date donnée (validation 26 semaines)
     */
    public function scopeActiveOnDate($query, $date)
    {
        $d = $date instanceof \Carbon\Carbon ? $date->format('Y-m-d') : $date;
        return $query->where('status', 'active')
            ->where('start_date', '<=', $d)
            ->where('end_date', '>=', $d);
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
     * Limite aux récurrences dont la plage horaire ressemble à un cours (pas toute la fenêtre club).
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     */
    public function scopeLessonLikeTimeWindow($query, ?int $maxMinutes = null): \Illuminate\Database\Eloquent\Builder
    {
        $max = $maxMinutes ?? self::MAX_LESSON_LIKE_WINDOW_MINUTES;
        $maxSeconds = $max * 60;
        $driver = DB::connection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            return $query->whereRaw(
                'TIME_TO_SEC(TIMEDIFF(end_time, start_time)) > 0 AND TIME_TO_SEC(TIMEDIFF(end_time, start_time)) <= ?',
                [$maxSeconds]
            );
        }

        if ($driver === 'sqlite') {
            return $query->whereRaw(
                "(julianday('1970-01-01 ' || end_time) - julianday('1970-01-01 ' || start_time)) * 86400 > 0
                 AND (julianday('1970-01-01 ' || end_time) - julianday('1970-01-01 ' || start_time)) * 86400 <= ?",
                [$maxSeconds]
            );
        }

        return $query->whereRaw(
            'TIME_TO_SEC(TIMEDIFF(end_time, start_time)) > 0 AND TIME_TO_SEC(TIMEDIFF(end_time, start_time)) <= ?',
            [$maxSeconds]
        );
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
     * Annuler cette récurrence (libère le créneau)
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
     * Libérer manuellement le créneau (annulation manuelle pour libérer le créneau)
     * Utilisé quand on sait que l'abonnement va se terminer
     */
    public function release(string $reason = null): void
    {
        $this->cancel("Libération manuelle du créneau" . ($reason ? " - " . $reason : ""));
    }

    /**
     * Réactiver une récurrence annulée
     * Utilisé si on veut rétablir la réservation
     */
    public function reactivate(string $reason = null): void
    {
        if ($this->status === 'cancelled') {
            $this->status = 'active';
            if ($reason) {
                $this->notes = ($this->notes ? $this->notes . "\n" : '') . "Réactivé : " . $reason;
            }
            $this->save();
        }
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
