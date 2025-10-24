<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LessonReplacement extends Model
{
    use HasFactory;

    protected $fillable = [
        'lesson_id',
        'original_teacher_id',
        'replacement_teacher_id',
        'status',
        'reason',
        'notes',
        'requested_at',
        'responded_at',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'responded_at' => 'datetime',
    ];

    /**
     * Get the lesson that is being replaced.
     */
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    /**
     * Get the original teacher.
     */
    public function originalTeacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'original_teacher_id');
    }

    /**
     * Get the replacement teacher.
     */
    public function replacementTeacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'replacement_teacher_id');
    }
}

