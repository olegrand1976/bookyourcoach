<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClubClosureDay extends Model
{
    protected $fillable = [
        'club_id',
        'closed_on',
        'closed_by_user_id',
        'detached_links',
    ];

    protected $casts = [
        'closed_on' => 'date',
        'detached_links' => 'array',
    ];

    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    public function closedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by_user_id');
    }

    public static function clubIsClosedOn(?int $clubId, string $dateYmd): bool
    {
        if ($clubId === null || $clubId <= 0) {
            return false;
        }

        return self::query()
            ->where('club_id', $clubId)
            ->whereDate('closed_on', $dateYmd)
            ->exists();
    }
}
