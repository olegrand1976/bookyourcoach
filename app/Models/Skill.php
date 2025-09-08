<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Skill extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'category',
        'activity_type_id',
        'description',
        'icon',
        'levels',
        'requirements',
        'is_active',
        'sort_order'
    ];

    protected $casts = [
        'levels' => 'array',
        'requirements' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer'
    ];

    // Relations
    public function activityType(): BelongsTo
    {
        return $this->belongsTo(ActivityType::class);
    }

    public function teacherSkills(): HasMany
    {
        return $this->hasMany(TeacherSkill::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByActivityType($query, $activityTypeId)
    {
        return $query->where('activity_type_id', $activityTypeId);
    }

    // Méthodes utilitaires
    public function getLevelsAttribute($value)
    {
        return $value ? json_decode($value, true) : ['beginner', 'intermediate', 'advanced', 'expert', 'master'];
    }

    public function getRequirementsAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }
}