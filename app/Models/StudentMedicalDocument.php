<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentMedicalDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'document_type',
        'file_path',
        'file_name',
        'expiry_date',
        'renewal_frequency',
        'notes',
        'is_active'
    ];

    protected $casts = [
        'expiry_date' => 'date',
        'is_active' => 'boolean'
    ];

    /**
     * Get the student that owns the document.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Check if the document is expired.
     */
    public function isExpired(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    /**
     * Check if the document expires soon (within 30 days).
     */
    public function expiresSoon(): bool
    {
        return $this->expiry_date && $this->expiry_date->isFuture() && $this->expiry_date->diffInDays(now()) <= 30;
    }
}
