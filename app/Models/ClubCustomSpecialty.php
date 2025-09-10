<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ClubCustomSpecialty extends Model
{
    use HasFactory;

    protected $fillable = [
        'club_id',
        'activity_type_id',
        'name',
        'description',
        'duration_minutes',
        'base_price',
        'skill_levels',
        'min_participants',
        'max_participants',
        'equipment_required',
        'is_active'
    ];

    protected $casts = [
        'skill_levels' => 'array',
        'equipment_required' => 'array',
        'base_price' => 'float',
        'duration_minutes' => 'integer',
        'min_participants' => 'integer',
        'max_participants' => 'integer',
        'is_active' => 'boolean'
    ];

    // Relations
    public function club()
    {
        return $this->belongsTo(Club::class);
    }

    public function activityType()
    {
        return $this->belongsTo(ActivityType::class);
    }
}
