<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VolunteerLetterSend extends Model
{
    protected $fillable = [
        'club_id',
        'teacher_id',
        'sent_by_user_id',
        'recipient_email',
        'status',
        'error_message',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    /**
     * Relation avec le club
     */
    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    /**
     * Relation avec l'enseignant
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    /**
     * Relation avec l'utilisateur qui a envoyé
     */
    public function sentBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by_user_id');
    }
}
