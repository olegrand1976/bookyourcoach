<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClubCommunicationLog extends Model
{
    protected $fillable = [
        'club_id',
        'sent_by_user_id',
        'audience',
        'subject',
        'body',
        'recipient_count',
        'sent_count',
        'failed_count',
    ];

    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    public function sentBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by_user_id');
    }
}
