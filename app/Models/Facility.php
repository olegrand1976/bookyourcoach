<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Facility extends Model
{
    use HasFactory;

    protected $fillable = [
        'activity_type_id',
        'name',
        'type',
        'capacity',
        'dimensions',
        'equipment',
        'description',
        'is_active'
    ];

    protected $casts = [
        'dimensions' => 'array',
        'equipment' => 'array',
        'is_active' => 'boolean',
        'capacity' => 'integer'
    ];

    // Relations
    public function activityType(): BelongsTo
    {
        return $this->belongsTo(ActivityType::class);
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class);
    }

    public function availabilities(): HasMany
    {
        return $this->hasMany(Availability::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByActivityType($query, $activityTypeId)
    {
        return $query->where('activity_type_id', $activityTypeId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    // Méthodes utilitaires
    public function getCapacityAttribute($value)
    {
        return $value ?: 1;
    }

    public function getDimensionsAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    public function getEquipmentAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }
}