<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Certification extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'issuing_authority',
        'category',
        'activity_type_id',
        'validity_years',
        'requirements',
        'description',
        'icon',
        'is_active',
        'sort_order'
    ];

    protected $casts = [
        'requirements' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'validity_years' => 'integer'
    ];

    // Relations
    public function activityType(): BelongsTo
    {
        return $this->belongsTo(ActivityType::class);
    }

    public function teacherCertifications(): HasMany
    {
        return $this->hasMany(TeacherCertification::class);
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
    public function getRequirementsAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    public function isExpired(): bool
    {
        if (!$this->validity_years) {
            return false; // Certificat permanent
        }
        
        return $this->created_at->addYears($this->validity_years)->isPast();
    }
}