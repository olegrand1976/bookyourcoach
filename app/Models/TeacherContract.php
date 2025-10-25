<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @OA\Schema(
 *     schema="TeacherContract",
 *     type="object",
 *     title="Teacher Contract",
 *     description="Contrat détaillé d'un enseignant avec un club",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="teacher_id", type="integer", example=1),
 *     @OA\Property(property="club_id", type="integer", example=1),
 *     @OA\Property(property="contract_type", type="string", enum={"permanent", "temporary", "freelance", "seasonal"}),
 *     @OA\Property(property="start_date", type="string", format="date", example="2025-01-15"),
 *     @OA\Property(property="end_date", type="string", format="date", nullable=true),
 *     @OA\Property(property="max_hours_per_week", type="integer", nullable=true, example=40),
 *     @OA\Property(property="min_hours_per_week", type="integer", nullable=true, example=20),
 *     @OA\Property(property="hourly_rate", type="number", format="float", example=35.00),
 *     @OA\Property(property="allowed_disciplines", type="array", @OA\Items(type="string")),
 *     @OA\Property(property="restricted_disciplines", type="array", @OA\Items(type="string")),
 *     @OA\Property(property="preferred_facilities", type="array", @OA\Items(type="string")),
 *     @OA\Property(property="unavailable_days", type="array", @OA\Items(type="string")),
 *     @OA\Property(property="earliest_start_time", type="string", format="time", nullable=true),
 *     @OA\Property(property="latest_end_time", type="string", format="time", nullable=true),
 *     @OA\Property(property="can_teach_weekends", type="boolean", example=false),
 *     @OA\Property(property="can_teach_holidays", type="boolean", example=false),
 *     @OA\Property(property="is_active", type="boolean", example=true),
 *     @OA\Property(property="notes", type="string", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class TeacherContract extends Model
{
    use HasFactory;

    protected $fillable = [
        'teacher_id',
        'club_id',
        'contract_type',
        'start_date',
        'end_date',
        'max_hours_per_week',
        'min_hours_per_week',
        'hourly_rate',
        'allowed_disciplines',
        'restricted_disciplines',
        'preferred_facilities',
        'unavailable_days',
        'earliest_start_time',
        'latest_end_time',
        'can_teach_weekends',
        'can_teach_holidays',
        'is_active',
        'notes'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'hourly_rate' => 'decimal:2',
        'allowed_disciplines' => 'array',
        'restricted_disciplines' => 'array',
        'preferred_facilities' => 'array',
        'unavailable_days' => 'array',
        'earliest_start_time' => 'datetime:H:i',
        'latest_end_time' => 'datetime:H:i',
        'can_teach_weekends' => 'boolean',
        'can_teach_holidays' => 'boolean',
        'is_active' => 'boolean',
        'max_hours_per_week' => 'integer',
        'min_hours_per_week' => 'integer'
    ];

    /**
     * Get the teacher for this contract
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    /**
     * Get the club for this contract
     */
    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    /**
     * Get the assignments for this contract
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(CourseAssignment::class, 'contract_id');
    }

    /**
     * Get contract type options
     */
    public static function getContractTypeOptions(): array
    {
        return [
            'permanent' => 'CDI',
            'temporary' => 'CDD',
            'freelance' => 'Freelance',
            'seasonal' => 'Saisonnier'
        ];
    }

    /**
     * Check if contract is active for a specific date
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
     * Check if teacher can teach on a specific day
     */
    public function canTeachOnDay($dayOfWeek): bool
    {
        if (!$this->can_teach_weekends && in_array($dayOfWeek, ['saturday', 'sunday'])) {
            return false;
        }

        return !in_array($dayOfWeek, $this->unavailable_days ?? []);
    }

    /**
     * Check if teacher can teach at a specific time
     */
    public function canTeachAtTime($startTime, $endTime): bool
    {
        $start = \Carbon\Carbon::parse($startTime);
        $end = \Carbon\Carbon::parse($endTime);

        if ($this->earliest_start_time && $start->lt($this->earliest_start_time)) {
            return false;
        }

        if ($this->latest_end_time && $end->gt($this->latest_end_time)) {
            return false;
        }

        return true;
    }

    /**
     * Check if teacher can teach a specific discipline
     */
    public function canTeachDiscipline($disciplineId): bool
    {
        // Si des disciplines sont autorisées, vérifier qu'elle est dans la liste
        if (!empty($this->allowed_disciplines)) {
            return in_array($disciplineId, $this->allowed_disciplines);
        }

        // Si des disciplines sont restreintes, vérifier qu'elle n'est pas dans la liste
        if (!empty($this->restricted_disciplines)) {
            return !in_array($disciplineId, $this->restricted_disciplines);
        }

        return true;
    }

    /**
     * Get hours worked in a specific week
     */
    public function getHoursWorkedInWeek($weekStart): int
    {
        $weekEnd = \Carbon\Carbon::parse($weekStart)->addWeek();
        
        return $this->assignments()
            ->whereBetween('assignment_date', [$weekStart, $weekEnd])
            ->whereIn('status', ['confirmed', 'completed'])
            ->sum('actual_duration') / 60; // Convert minutes to hours
    }

    /**
     * Check if teacher has reached maximum hours for the week
     */
    public function hasReachedMaxHoursForWeek($weekStart): bool
    {
        if (!$this->max_hours_per_week) {
            return false;
        }

        $hoursWorked = $this->getHoursWorkedInWeek($weekStart);
        return $hoursWorked >= $this->max_hours_per_week;
    }
}
