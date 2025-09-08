<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'discipline_id',
        'course_type_id',
        'is_preferred',
        'priority_level',
    ];

    protected $casts = [
        'is_preferred' => 'boolean',
        'priority_level' => 'integer',
    ];

    /**
     * Get the student that owns this preference
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the discipline for this preference
     */
    public function discipline(): BelongsTo
    {
        return $this->belongsTo(Discipline::class);
    }

    /**
     * Get the course type for this preference
     */
    public function courseType(): BelongsTo
    {
        return $this->belongsTo(CourseType::class);
    }
}
