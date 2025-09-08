<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherCertification extends Model
{
    use HasFactory;

    protected $fillable = [
        'teacher_id',
        'certification_id',
        'obtained_date',
        'expiry_date',
        'certificate_number',
        'issuing_authority',
        'certificate_file',
        'notes',
        'is_valid',
        'renewal_required',
        'renewal_reminder_date',
        'is_verified',
        'verified_by',
        'verified_at'
    ];

    protected $casts = [
        'is_valid' => 'boolean',
        'renewal_required' => 'boolean',
        'is_verified' => 'boolean',
        'obtained_date' => 'date',
        'expiry_date' => 'date',
        'renewal_reminder_date' => 'date',
        'verified_at' => 'date'
    ];

    // Relations
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function certification(): BelongsTo
    {
        return $this->belongsTo(Certification::class);
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    // Scopes
    public function scopeValid($query)
    {
        return $query->where('is_valid', true);
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeExpiringSoon($query, $days = 30)
    {
        return $query->where('expiry_date', '<=', now()->addDays($days))
                    ->where('expiry_date', '>', now());
    }

    // Méthodes utilitaires
    public function isExpired(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    public function isExpiringSoon($days = 30): bool
    {
        return $this->expiry_date && 
               $this->expiry_date->isAfter(now()) && 
               $this->expiry_date->isBefore(now()->addDays($days));
    }

    public function getDaysUntilExpiry(): ?int
    {
        if (!$this->expiry_date) return null;
        
        return now()->diffInDays($this->expiry_date, false);
    }
}