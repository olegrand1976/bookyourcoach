<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @OA\Schema(
 *     schema="CourseAssignment",
 *     type="object",
 *     title="Course Assignment",
 *     description="Affectation d'un enseignant à une plage de cours",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="course_slot_id", type="integer", example=1),
 *     @OA\Property(property="teacher_id", type="integer", example=1),
 *     @OA\Property(property="contract_id", type="integer", example=1),
 *     @OA\Property(property="assignment_date", type="string", format="date", example="2025-01-15"),
 *     @OA\Property(property="status", type="string", enum={"assigned", "confirmed", "completed", "cancelled", "no_show"}),
 *     @OA\Property(property="hourly_rate", type="number", format="float", example=35.00),
 *     @OA\Property(property="actual_duration", type="integer", nullable=true, example=60),
 *     @OA\Property(property="notes", type="string", nullable=true),
 *     @OA\Property(property="confirmed_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="completed_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class CourseAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_slot_id',
        'teacher_id',
        'contract_id',
        'assignment_date',
        'status',
        'hourly_rate',
        'actual_duration',
        'notes',
        'confirmed_at',
        'completed_at'
    ];

    protected $casts = [
        'assignment_date' => 'date',
        'hourly_rate' => 'decimal:2',
        'actual_duration' => 'integer',
        'confirmed_at' => 'datetime',
        'completed_at' => 'datetime'
    ];

    /**
     * Get the course slot for this assignment
     */
    public function courseSlot(): BelongsTo
    {
        return $this->belongsTo(CourseSlot::class);
    }

    /**
     * Get the teacher for this assignment
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    /**
     * Get the contract for this assignment
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(TeacherContract::class, 'contract_id');
    }

    /**
     * Get status options
     */
    public static function getStatusOptions(): array
    {
        return [
            'assigned' => 'Affecté',
            'confirmed' => 'Confirmé',
            'completed' => 'Terminé',
            'cancelled' => 'Annulé',
            'no_show' => 'Absent'
        ];
    }

    /**
     * Get formatted status
     */
    public function getStatusLabelAttribute(): string
    {
        return self::getStatusOptions()[$this->status] ?? $this->status;
    }

    /**
     * Get total cost for this assignment
     */
    public function getTotalCostAttribute(): float
    {
        $duration = $this->actual_duration ?? $this->courseSlot->duration;
        return ($duration / 60) * $this->hourly_rate;
    }

    /**
     * Confirm the assignment
     */
    public function confirm(): bool
    {
        if ($this->status === 'assigned') {
            $this->update([
                'status' => 'confirmed',
                'confirmed_at' => now()
            ]);
            return true;
        }
        return false;
    }

    /**
     * Complete the assignment
     */
    public function complete($actualDuration = null): bool
    {
        if (in_array($this->status, ['assigned', 'confirmed'])) {
            $this->update([
                'status' => 'completed',
                'completed_at' => now(),
                'actual_duration' => $actualDuration ?? $this->courseSlot->duration
            ]);
            return true;
        }
        return false;
    }

    /**
     * Cancel the assignment
     */
    public function cancel(): bool
    {
        if (in_array($this->status, ['assigned', 'confirmed'])) {
            $this->update(['status' => 'cancelled']);
            return true;
        }
        return false;
    }

    /**
     * Mark as no show
     */
    public function markAsNoShow(): bool
    {
        if (in_array($this->status, ['assigned', 'confirmed'])) {
            $this->update(['status' => 'no_show']);
            return true;
        }
        return false;
    }

    /**
     * Check if assignment is active (not cancelled or no show)
     */
    public function isActive(): bool
    {
        return !in_array($this->status, ['cancelled', 'no_show']);
    }

    /**
     * Check if assignment is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Get assignment details for display
     */
    public function getDisplayDetailsAttribute(): array
    {
        return [
            'teacher_name' => $this->teacher->user->name,
            'course_name' => $this->courseSlot->name,
            'facility' => $this->courseSlot->facility->name,
            'time_range' => $this->courseSlot->time_range,
            'date' => $this->assignment_date->format('d/m/Y'),
            'status' => $this->status_label,
            'cost' => $this->total_cost
        ];
    }
}
