<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @OA\Schema(
 *     schema="Lesson",
 *     type="object",
 *     title="Lesson",
 *     description="Cours/leçon réservé sur la plateforme",
 *     @OA\Property(property="id", type="integer", format="int64", description="Identifiant unique du cours", example=1),
 *     @OA\Property(property="teacher_id", type="integer", description="ID de l'enseignant", example=1),
 *     @OA\Property(property="student_id", type="integer", description="ID de l'élève principal (pour compatibilité)", example=1),
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
        'club_id',
        'teacher_id',
        'student_id',
        'course_type_id',
        'location_id',
        'start_time',
        'end_time',
        'status',
        'payment_status',
        'price',
        'notes',
        'teacher_feedback',
        'rating',
        'review'
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'price' => 'decimal:2',
        'rating' => 'integer'
    ];

    protected $appends = ['teacher_name', 'student_name', 'duration', 'title'];

    /**
     * Get the club for this lesson.
     */
    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    /**
     * Get the teacher for this lesson.
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    /**
     * Get the primary student for this lesson (for backward compatibility).
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get all students for this lesson (many-to-many relationship).
     */
    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'lesson_student')
                    ->withPivot(['status', 'price', 'notes'])
                    ->withTimestamps();
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
        return $query->whereBetween('start_time', [$from, $to]);
    }

    /**
     * Get formatted duration.
     */
    public function getFormattedDurationAttribute(): string
    {
        if (!$this->start_time || !$this->end_time) {
            return '0min';
        }
        
        $duration = $this->start_time->diffInMinutes($this->end_time);
        $hours = floor($duration / 60);
        $minutes = $duration % 60;

        if ($hours > 0 && $minutes > 0) {
            return "{$hours}h {$minutes}min";
        } elseif ($hours > 0) {
            return "{$hours}h";
        } else {
            return "{$minutes}min";
        }
    }

    /**
     * Get the total number of students enrolled in this lesson.
     */
    public function getStudentCountAttribute(): int
    {
        return $this->students()->count();
    }

    /**
     * Check if the lesson is a group lesson (multiple students).
     */
    public function getIsGroupLessonAttribute(): bool
    {
        return $this->students()->count() > 1;
    }

    /**
     * Get teacher name for API response.
     */
    public function getTeacherNameAttribute(): ?string
    {
        return $this->teacher?->user?->name;
    }

    /**
     * Get student name for API response.
     */
    public function getStudentNameAttribute(): ?string
    {
        return $this->student?->user?->name;
    }

    /**
     * Get duration in minutes for API response.
     */
    public function getDurationAttribute(): int
    {
        if (!$this->start_time || !$this->end_time) {
            return 0;
        }
        return $this->start_time->diffInMinutes($this->end_time);
    }

    /**
     * Get title for API response.
     */
    public function getTitleAttribute(): string
    {
        $parts = [];
        
        // Ajouter le type de cours si disponible
        if ($this->courseType) {
            $parts[] = $this->courseType->name;
        }
        
        // Ajouter le nom de l'élève si disponible
        if ($this->student_name) {
            $parts[] = $this->student_name;
        }
        
        // Si aucune partie, retourner "Cours"
        return !empty($parts) ? implode(' - ', $parts) : 'Cours';
    }

    /**
     * Get the total price for all students in this lesson.
     */
    public function getTotalPriceAttribute(): float
    {
        $total = 0;
        foreach ($this->students as $student) {
            $total += $student->pivot->price ?? $this->price ?? 0;
        }
        return $total;
    }
}
