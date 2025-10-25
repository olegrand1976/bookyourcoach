<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'club_id',
        'category_id',
        'name',
        'description',
        'price',
        'cost_price',
        'stock_quantity',
        'min_stock',
        'sku',
        'barcode',
        'images',
        'is_active'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'stock_quantity' => 'integer',
        'min_stock' => 'integer',
        'images' => 'array',
        'is_active' => 'boolean'
    ];

    // Relations
    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function transactionItems(): HasMany
    {
        return $this->hasMany(TransactionItem::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeLowStock($query)
    {
        return $query->whereRaw('stock_quantity <= min_stock');
    }

    public function scopeByClub($query, $clubId)
    {
        return $query->where('club_id', $clubId);
    }

    // Méthodes utilitaires
    public function updateStock($quantity)
    {
        $this->update(['stock_quantity' => $this->stock_quantity + $quantity]);
    }

    public function getProfitMarginAttribute()
    {
        if (!$this->cost_price) {
            return 0;
        }
        
        return round((($this->price - $this->cost_price) / $this->price) * 100, 2);
    }

    public function isLowStock()
    {
        return $this->stock_quantity <= $this->min_stock;
    }

    public function getImagesAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }
}