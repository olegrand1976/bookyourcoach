<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;
use RRule\RRule;

class RecurringSlot extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'teacher_id',
        'club_id',
        'course_type_id',
        'rrule',
        'reference_start_time',
        'duration_minutes',
        'status',
        'notes',
    ];

    protected $casts = [
        'reference_start_time' => 'datetime',
        'duration_minutes' => 'integer',
    ];

    /**
     * L'élève qui possède ce créneau
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * L'enseignant assigné
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    /**
     * Le club
     */
    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    /**
     * Le type de cours (optionnel)
     */
    public function courseType(): BelongsTo
    {
        return $this->belongsTo(CourseType::class);
    }

    /**
     * Les abonnements qui utilisent ce créneau
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(RecurringSlotSubscription::class);
    }

    /**
     * L'abonnement actif pour ce créneau
     */
    public function activeSubscription()
    {
        return $this->hasOne(RecurringSlotSubscription::class)
            ->where('status', 'active')
            ->where('start_date', '<=', Carbon::now())
            ->where('end_date', '>=', Carbon::now());
    }

    /**
     * Les cours générés à partir de ce créneau
     */
    public function lessons(): HasMany
    {
        return $this->hasMany(LessonRecurringSlot::class);
    }

    /**
     * Génère les dates futures basées sur la RRULE
     * 
     * @param Carbon|null $startDate Date de début (par défaut: maintenant)
     * @param Carbon|null $endDate Date de fin (par défaut: +3 mois)
     * @return array Tableau de dates Carbon
     */
    public function generateDates(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?? Carbon::now();
        $endDate = $endDate ?? Carbon::now()->addMonths(3);

        try {
            $rrule = new RRule($this->rrule, $this->reference_start_time);
            $occurrences = $rrule->getOccurrencesBetween($startDate, $endDate);
            
            return array_map(function ($date) {
                return Carbon::instance($date);
            }, $occurrences);
        } catch (\Exception $e) {
            \Log::error("Erreur lors de la génération des dates pour recurring_slot {$this->id}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Vérifie si le créneau est actif
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Vérifie si le créneau est en pause
     */
    public function isPaused(): bool
    {
        return $this->status === 'paused';
    }

    /**
     * Met en pause le créneau
     */
    public function pause(string $reason = null): void
    {
        $this->status = 'paused';
        if ($reason) {
            $this->notes = ($this->notes ? $this->notes . "\n" : '') . "Mis en pause : " . $reason;
        }
        $this->save();
    }

    /**
     * Reprend le créneau (retour à actif)
     */
    public function resume(string $reason = null): void
    {
        if ($this->status === 'paused') {
            $this->status = 'active';
            if ($reason) {
                $this->notes = ($this->notes ? $this->notes . "\n" : '') . "Repris : " . $reason;
            }
            $this->save();
        }
    }

    /**
     * Annule le créneau
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
     * Scope pour les créneaux actifs
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope pour les créneaux en pause
     */
    public function scopePaused($query)
    {
        return $query->where('status', 'paused');
    }

    /**
     * Scope pour les créneaux non annulés
     */
    public function scopeNotCancelled($query)
    {
        return $query->where('status', '!=', 'cancelled');
    }
}
