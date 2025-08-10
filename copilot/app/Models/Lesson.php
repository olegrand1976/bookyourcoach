<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @OA\Schema(
 *     schema="Lesson",
 *     type="object",
 *     title="Lesson",
 *     description="Cours/leçon réservé sur la plateforme",
 *     @OA\Property(property="id", type="integer", format="int64", description="Identifiant unique du cours", example=1),
 *     @OA\Property(property="teacher_id", type="integer", description="ID de l'enseignant", example=1),
 *     @OA\Property(property="student_id", type="integer", description="ID de l'élève", example=1),
 *     @OA\Property(property="course_type_id", type="integer", description="ID du type de cours", example=1),
 *     @OA\Property(property="location_id", type="integer", description="ID du lieu", example=1),
 *     @OA\Property(property="scheduled_at", type="string", format="datetime", description="Date et heure prévues", example="2025-08-15 14:00:00"),
 *     @OA\Property(property="duration", type="integer", description="Durée en minutes", example=60),
 *     @OA\Property(property="price", type="number", format="float", description="Prix du cours", example=50.00),
 *     @OA\Property(property="status", type="string", enum={"pending", "confirmed", "completed", "cancelled"}, example="pending"),
 *     @OA\Property(property="notes", type="string", description="Notes sur le cours", example="Premier cours de dressage"),
 *     @OA\Property(property="created_at", type="string", format="datetime", description="Date de création"),
 *     @OA\Property(property="updated_at", type="string", format="datetime", description="Date de dernière mise à jour")
 * )
 */
class Lesson extends Model
{
    use HasFactory;

    protected $fillable = [
        'teacher_id',
        'student_id',
        'course_type_id',
        'location_id',
        'scheduled_at',
        'duration',
        'price',
        'status',
        'notes'
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'price' => 'decimal:2',
        'duration' => 'integer'
    ];

    /**
     * Get the teacher for this lesson.
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    /**
     * Get the student for this lesson.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the course type for this lesson.
     */
    public function courseType(): BelongsTo
    {
        return $this->belongsTo(CourseType::class);
    }

    /**
     * Get the location for this lesson.
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Scope to filter lessons by status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter lessons by date range.
     */
    public function scopeInDateRange($query, $from, $to)
    {
        return $query->whereBetween('scheduled_at', [$from, $to]);
    }

    /**
     * Get formatted duration.
     */
    public function getFormattedDurationAttribute(): string
    {
        $hours = floor($this->duration / 60);
        $minutes = $this->duration % 60;

        if ($hours > 0 && $minutes > 0) {
            return "{$hours}h {$minutes}min";
        } elseif ($hours > 0) {
            return "{$hours}h";
        } else {
            return "{$minutes}min";
        }
    }
}
