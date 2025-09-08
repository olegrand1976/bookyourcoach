<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ActivityType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'color',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    // Relations
    public function facilities(): HasMany
    {
        return $this->hasMany(Facility::class);
    }

    public function disciplines(): HasMany
    {
        return $this->hasMany(Discipline::class);
    }

    public function clubs(): HasMany
    {
        return $this->hasMany(Club::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeBySlug($query, $slug)
    {
        return $query->where('slug', $slug);
    }

    // Méthodes utilitaires
    public function getIconAttribute($value)
    {
        return $value ?: '🏃‍♂️'; // Icône par défaut
    }

    public function getColorAttribute($value)
    {
        return $value ?: '#6B7280'; // Couleur par défaut
    }
}