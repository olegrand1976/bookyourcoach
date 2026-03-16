<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialPost extends Model
{
    protected $fillable = [
        'club_id',
        'scheduled_at',
        'type',
        'text',
        'image_prompt',
        'image_path',
        'status',
    ];

    protected $casts = [
        'scheduled_at' => 'date',
    ];

    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    public function scopeForClub($query, ?int $clubId)
    {
        if ($clubId === null) {
            return $query->whereNull('club_id');
        }
        return $query->where('club_id', $clubId);
    }

    public function scopeForMonth($query, int $year, int $month)
    {
        return $query->whereYear('scheduled_at', $year)->whereMonth('scheduled_at', $month);
    }
}
