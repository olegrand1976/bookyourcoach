<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClubSettings extends Model
{
    use HasFactory;

    protected $fillable = [
        'club_id',
        'feature_key',
        'feature_name',
        'feature_category',
        'is_enabled',
        'configuration',
        'description',
        'icon',
        'sort_order'
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'configuration' => 'array',
        'sort_order' => 'integer'
    ];

    // Relations
    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    // Scopes
    public function scopeByCategory($query, $category)
    {
        return $query->where('feature_category', $category);
    }

    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    public function scopeDisabled($query)
    {
        return $query->where('is_enabled', false);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('feature_name');
    }

    // Méthodes utilitaires
    public function enable()
    {
        $this->update(['is_enabled' => true]);
    }

    public function disable()
    {
        $this->update(['is_enabled' => false]);
    }

    public function toggle()
    {
        $this->update(['is_enabled' => !$this->is_enabled]);
    }

    public function updateConfiguration($config)
    {
        $this->update(['configuration' => array_merge($this->configuration ?? [], $config)]);
    }

    public function getConfigurationValue($key, $default = null)
    {
        return data_get($this->configuration, $key, $default);
    }

    public function setConfigurationValue($key, $value)
    {
        $config = $this->configuration ?? [];
        data_set($config, $key, $value);
        $this->update(['configuration' => $config]);
    }

    // Méthodes statiques pour la gestion des fonctionnalités
    public static function isFeatureEnabled($clubId, $featureKey)
    {
        return self::where('club_id', $clubId)
            ->where('feature_key', $featureKey)
            ->where('is_enabled', true)
            ->exists();
    }

    public static function getEnabledFeatures($clubId)
    {
        return self::where('club_id', $clubId)
            ->where('is_enabled', true)
            ->pluck('feature_key')
            ->toArray();
    }

    public static function getFeaturesByCategory($clubId, $category)
    {
        return self::where('club_id', $clubId)
            ->where('feature_category', $category)
            ->ordered()
            ->get();
    }

    public static function getAllFeatures($clubId)
    {
        return self::where('club_id', $clubId)
            ->ordered()
            ->get()
            ->groupBy('feature_category');
    }
}