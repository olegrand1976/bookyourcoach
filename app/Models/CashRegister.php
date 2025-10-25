<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashRegister extends Model
{
    use HasFactory;

    protected $fillable = [
        'club_id',
        'name',
        'location',
        'is_active',
        'current_balance',
        'last_closing_at'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'current_balance' => 'decimal:2',
        'last_closing_at' => 'datetime'
    ];

    // Relations
    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByClub($query, $clubId)
    {
        return $query->where('club_id', $clubId);
    }

    // Méthodes utilitaires
    public function getCurrentBalanceAttribute($value)
    {
        return $value ?: 0.00;
    }

    public function getDailyTotal($date = null)
    {
        $date = $date ?: now()->toDateString();
        
        return $this->transactions()
            ->whereDate('processed_at', $date)
            ->where('type', 'sale')
            ->sum('amount');
    }

    public function getDailyTransactionsCount($date = null)
    {
        $date = $date ?: now()->toDateString();
        
        return $this->transactions()
            ->whereDate('processed_at', $date)
            ->count();
    }

    public function closeRegister()
    {
        $this->update([
            'last_closing_at' => now(),
            'current_balance' => 0.00
        ]);
    }
}