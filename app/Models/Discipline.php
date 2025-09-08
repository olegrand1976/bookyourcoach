<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Discipline extends Model
{
    use HasFactory;

    protected $fillable = [
        'activity_type_id',
        'name',
        'slug',
        'description',
        'min_participants',
        'max_participants',
        'duration_minutes',
        'equipment_required',
        'skill_levels',
        'base_price',
        'is_active'
    ];

    protected $casts = [
        'equipment_required' => 'array',
        'skill_levels' => 'array',
        'base_price' => 'decimal:2',
        'is_active' => 'boolean',
        'min_participants' => 'integer',
        'max_participants' => 'integer',
        'duration_minutes' => 'integer'
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

    public function courseTypes(): HasMany
    {
        return $this->hasMany(CourseType::class);
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

    public function scopeBySlug($query, $slug)
    {
        return $query->where('slug', $slug);
    }

    // Méthodes utilitaires
    public function getEquipmentRequiredAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    public function getSkillLevelsAttribute($value)
    {
        return $value ? json_decode($value, true) : ['débutant', 'intermédiaire', 'expert'];
    }

    public function getBasePriceAttribute($value)
    {
        return $value ?: 0.00;
    }
}