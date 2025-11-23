<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @OA\Schema(
 *     schema="Booking",
 *     type="object",
 *     title="Booking",
 *     description="Réservation d'un cours par un étudiant",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="student_id", type="integer", example=1),
 *     @OA\Property(property="lesson_id", type="integer", example=1),
 *     @OA\Property(property="status", type="string", enum={"pending", "confirmed", "cancelled", "completed"}),
 *     @OA\Property(property="booked_at", type="string", format="date-time"),
 *     @OA\Property(property="confirmed_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="cancelled_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="notes", type="string", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'lesson_id',
        'status',
        'booked_at',
        'confirmed_at',
        'cancelled_at',
        'notes'
    ];

    protected $casts = [
        'booked_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'cancelled_at' => 'datetime'
    ];

    /**
     * Get the student (user) who made this booking
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * Get the lesson for this booking
     */
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    /**
     * Get status options
     */
    public static function getStatusOptions(): array
    {
        return [
            'pending' => 'En attente',
            'confirmed' => 'Confirmée',
            'cancelled' => 'Annulée',
            'completed' => 'Terminée'
        ];
    }

    /**
     * Get formatted status label
     */
    public function getStatusLabelAttribute(): string
    {
        return self::getStatusOptions()[$this->status] ?? $this->status;
    }

    /**
     * Confirm the booking
     */
    public function confirm(): bool
    {
        if ($this->status === 'pending') {
            $this->update([
                'status' => 'confirmed',
                'confirmed_at' => now()
            ]);
            return true;
        }
        return false;
    }

    /**
     * Cancel the booking
     */
    public function cancel(): bool
    {
        if (in_array($this->status, ['pending', 'confirmed'])) {
            $this->update([
                'status' => 'cancelled',
                'cancelled_at' => now()
            ]);
            return true;
        }
        return false;
    }

    /**
     * Mark as completed
     */
    public function complete(): bool
    {
        if ($this->status === 'confirmed') {
            $this->update(['status' => 'completed']);
            return true;
        }
        return false;
    }

    /**
     * Check if booking is active (not cancelled)
     */
    public function isActive(): bool
    {
        return !in_array($this->status, ['cancelled']);
    }

    /**
     * Check if booking is confirmed
     */
    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }

    /**
     * Check if booking is cancelled
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }
}

