<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VolunteerExpenseLimit extends Model
{
    protected $fillable = [
        'year',
        'daily_amount',
        'yearly_amount',
        'yearly_special_categories',
        'yearly_health_sector',
        'source_url',
        'fetched_at',
    ];

    protected $casts = [
        'year' => 'integer',
        'daily_amount' => 'decimal:2',
        'yearly_amount' => 'decimal:2',
        'yearly_special_categories' => 'decimal:2',
        'yearly_health_sector' => 'decimal:2',
        'fetched_at' => 'datetime',
    ];

    /**
     * Récupérer les plafonds pour l'année en cours
     */
    public static function getCurrentLimits()
    {
        return self::where('year', now()->year)->first();
    }

    /**
     * Récupérer les plafonds pour une année spécifique
     */
    public static function forYear(int $year)
    {
        return self::where('year', $year)->first();
    }
}
