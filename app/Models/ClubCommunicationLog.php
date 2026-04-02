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
        'selection_mode',
        'selected_teacher_ids',
        'selected_student_ids',
        'subject',
        'body',
        'recipient_count',
        'sent_count',
        'failed_count',
        'teacher_recipient_count',
        'student_recipient_count',
    ];

    protected function casts(): array
    {
        return [
            'selected_teacher_ids' => 'array',
            'selected_student_ids' => 'array',
        ];
    }

    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    public function sentBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by_user_id');
    }
}
