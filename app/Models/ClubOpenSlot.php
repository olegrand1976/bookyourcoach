<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClubOpenSlot extends Model
{
    use HasFactory;

    protected $fillable = [
        'club_id',
        'day_of_week',
        'start_time',
        'end_time',
        'discipline_id',
        'max_capacity',
        'max_slots',
        'duration',
        'price',
        'is_active',
    ];

    /**
     * Get the club that owns this slot
     */
    public function club()
    {
        return $this->belongsTo(Club::class);
    }

    /**
     * Get the discipline for this slot
     */
    public function discipline()
    {
        return $this->belongsTo(Discipline::class);
    }

    /**
     * Get the course types associated with this slot
     */
    public function courseTypes()
    {
        return $this->belongsToMany(CourseType::class, 'club_open_slot_course_types');
    }
}
