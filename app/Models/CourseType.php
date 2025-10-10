<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourseType extends Model
{
    use HasFactory;

    protected $fillable = [
        'discipline_id',
        'name',
        'description',
        'duration_minutes',
        'is_individual',
        'max_participants',
        'is_active',
    ];

    protected $casts = [
        'is_individual' => 'boolean',
        'is_active' => 'boolean',
        'duration_minutes' => 'integer',
        'max_participants' => 'integer',
    ];

    /**
     * Get the discipline that owns this course type
     */
    public function discipline(): BelongsTo
    {
        return $this->belongsTo(Discipline::class);
    }

    /**
     * Get the student preferences for this course type
     */
    public function studentPreferences(): HasMany
    {
        return $this->hasMany(StudentPreference::class);
    }

    /**
     * Get the lessons for this course type
     */
    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class);
    }

    /**
     * Get the club open slots that can use this course type
     */
    public function clubOpenSlots()
    {
        return $this->belongsToMany(ClubOpenSlot::class, 'club_open_slot_course_types');
    }
}