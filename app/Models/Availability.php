<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Availability extends Model
{
    protected $fillable = [
        'teacher_id',
        'location_id',
        'start_time',
        'end_time',
        'is_available',
        'notes',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'is_available' => 'boolean',
    ];

    /**
     * Relation avec l'enseignant.
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    /**
     * Relation avec le lieu.
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }
}
