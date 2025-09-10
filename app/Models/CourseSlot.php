<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @OA\Schema(
 *     schema="CourseSlot",
 *     type="object",
 *     title="Course Slot",
 *     description="Plage horaire d'ouverture pour un cours",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="club_id", type="integer", example=1),
 *     @OA\Property(property="facility_id", type="integer", example=1),
 *     @OA\Property(property="course_type_id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Cours dressage matin"),
 *     @OA\Property(property="start_time", type="string", format="time", example="09:00:00"),
 *     @OA\Property(property="end_time", type="string", format="time", example="10:00:00"),
 *     @OA\Property(property="day_of_week", type="string", enum={"monday", "tuesday", "wednesday", "thursday", "friday", "saturday", "sunday"}),
 *     @OA\Property(property="start_date", type="string", format="date", example="2025-01-15"),
 *     @OA\Property(property="end_date", type="string", format="date", nullable=true),
 *     @OA\Property(property="max_students", type="integer", example=8),
 *     @OA\Property(property="price", type="number", format="float", example=45.00),
 *     @OA\Property(property="is_recurring", type="boolean", example=true),
 *     @OA\Property(property="is_active", type="boolean", example=true),
 *     @OA\Property(property="notes", type="string", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class CourseSlot extends Model
{
    use HasFactory;

    protected $fillable = [
        'club_id',
        'facility_id',
        'course_type_id',
        'name',
        'start_time',
        'end_time',
        'day_of_week',
        'start_date',
        'end_date',
        'max_students',
        'price',
        'is_recurring',
        'is_active',
        'notes'
    ];

    protected $casts = [
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'start_date' => 'date',
        'end_date' => 'date',
        'price' => 'decimal:2',
        'is_recurring' => 'boolean',
        'is_active' => 'boolean',
        'max_students' => 'integer'
    ];

    /**
     * Get the club that owns this course slot
     */
    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    /**
     * Get the facility for this course slot
     */
    public function facility(): BelongsTo
    {
        return $this->belongsTo(ClubFacility::class, 'facility_id');
    }

    /**
     * Get the course type for this slot
     */
    public function courseType(): BelongsTo
    {
        return $this->belongsTo(CourseType::class);
    }

    /**
     * Get the assignments for this course slot
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(CourseAssignment::class);
    }

    /**
     * Get the day of week options
     */
    public static function getDayOfWeekOptions(): array
    {
        return [
            'monday' => 'Lundi',
            'tuesday' => 'Mardi',
            'wednesday' => 'Mercredi',
            'thursday' => 'Jeudi',
            'friday' => 'Vendredi',
            'saturday' => 'Samedi',
            'sunday' => 'Dimanche'
        ];
    }

    /**
     * Get formatted time range
     */
    public function getTimeRangeAttribute(): string
    {
        return $this->start_time->format('H:i') . ' - ' . $this->end_time->format('H:i');
    }

    /**
     * Get formatted day name
     */
    public function getDayNameAttribute(): string
    {
        return self::getDayOfWeekOptions()[$this->day_of_week] ?? $this->day_of_week;
    }

    /**
     * Get duration in minutes
     */
    public function getDurationAttribute(): int
    {
        return $this->start_time->diffInMinutes($this->end_time);
    }

    /**
     * Check if slot is active for a specific date
     */
    public function isActiveForDate($date): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $dateObj = is_string($date) ? \Carbon\Carbon::parse($date) : $date;
        
        if ($dateObj->lt($this->start_date)) {
            return false;
        }

        if ($this->end_date && $dateObj->gt($this->end_date)) {
            return false;
        }

        return true;
    }

    /**
     * Generate dates for this recurring slot within a date range
     */
    public function generateDatesForRange($startDate, $endDate): array
    {
        if (!$this->is_recurring) {
            return [];
        }

        $dates = [];
        $start = \Carbon\Carbon::parse($startDate);
        $end = \Carbon\Carbon::parse($endDate);
        
        $dayMap = [
            'monday' => 1,
            'tuesday' => 2,
            'wednesday' => 3,
            'thursday' => 4,
            'friday' => 5,
            'saturday' => 6,
            'sunday' => 0
        ];

        $targetDay = $dayMap[$this->day_of_week];
        
        $current = $start->copy()->next($targetDay);
        
        while ($current->lte($end)) {
            if ($this->isActiveForDate($current)) {
                $dates[] = $current->format('Y-m-d');
            }
            $current->addWeek();
        }

        return $dates;
    }
}
