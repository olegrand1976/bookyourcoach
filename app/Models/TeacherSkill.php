<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherSkill extends Model
{
    use HasFactory;

    protected $fillable = [
        'teacher_id',
        'skill_id',
        'level',
        'experience_years',
        'acquired_date',
        'last_practiced',
        'notes',
        'evidence',
        'is_verified',
        'verified_by',
        'verified_at',
        'is_active'
    ];

    protected $casts = [
        'evidence' => 'array',
        'is_verified' => 'boolean',
        'is_active' => 'boolean',
        'acquired_date' => 'date',
        'last_practiced' => 'date',
        'verified_at' => 'date',
        'experience_years' => 'integer'
    ];

    // Relations
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function skill(): BelongsTo
    {
        return $this->belongsTo(Skill::class);
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeByLevel($query, $level)
    {
        return $query->where('level', $level);
    }

    // Méthodes utilitaires
    public function getEvidenceAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    public function isRecentlyPracticed(): bool
    {
        return $this->last_practiced && $this->last_practiced->isAfter(now()->subDays(30));
    }

    public function getExperienceLevel(): string
    {
        if ($this->experience_years >= 10) return 'expert';
        if ($this->experience_years >= 5) return 'advanced';
        if ($this->experience_years >= 2) return 'intermediate';
        return 'beginner';
    }
}