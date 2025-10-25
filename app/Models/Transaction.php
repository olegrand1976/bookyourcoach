<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'club_id',
        'cash_register_id',
        'user_id',
        'type',
        'amount',
        'payment_method',
        'description',
        'reference',
        'metadata',
        'processed_at'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'metadata' => 'array',
        'processed_at' => 'datetime'
    ];

    // Relations
    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    public function cashRegister(): BelongsTo
    {
        return $this->belongsTo(CashRegister::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(TransactionItem::class);
    }

    // Scopes
    public function scopeByDate($query, $date)
    {
        return $query->whereDate('processed_at', $date);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByPaymentMethod($query, $method)
    {
        return $query->where('payment_method', $method);
    }

    public function scopeByClub($query, $clubId)
    {
        return $query->where('club_id', $clubId);
    }

    public function scopeSales($query)
    {
        return $query->where('type', 'sale');
    }

    public function scopeRefunds($query)
    {
        return $query->where('type', 'refund');
    }

    public function scopeExpenses($query)
    {
        return $query->where('type', 'expense');
    }

    // Méthodes utilitaires
    public function calculateTotal()
    {
        return $this->items()->sum('total_price');
    }

    public function generateReceipt()
    {
        // Logique pour générer un reçu
        return [
            'transaction_id' => $this->id,
            'date' => $this->processed_at,
            'items' => $this->items,
            'total' => $this->amount,
            'payment_method' => $this->payment_method
        ];
    }

    public function refund()
    {
        // Créer une transaction de remboursement
        return self::create([
            'club_id' => $this->club_id,
            'cash_register_id' => $this->cash_register_id,
            'user_id' => auth()->id(),
            'type' => 'refund',
            'amount' => -$this->amount,
            'payment_method' => $this->payment_method,
            'description' => "Remboursement de la transaction #{$this->id}",
            'reference' => "REF-{$this->id}",
            'processed_at' => now()
        ]);
    }

    public function getMetadataAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }
}