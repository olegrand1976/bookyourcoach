<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Remise en cohérence des abonnements d'un élève / d'une famille, annulable.
 *
 * before / after : ['links' => [lesson_id => instance_id|null], 'instances' => [id => champs compteur]]
 */
class SubscriptionRebalance extends Model
{
    protected $fillable = [
        'club_id',
        'student_ids',
        'instance_ids',
        'before',
        'after',
        'summary',
        'applied_by',
        'reverted_at',
        'reverted_by',
    ];

    protected $casts = [
        'student_ids' => 'array',
        'instance_ids' => 'array',
        'before' => 'array',
        'after' => 'array',
        'summary' => 'array',
        'reverted_at' => 'datetime',
    ];

    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    public function appliedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applied_by');
    }

    public function revertedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reverted_by');
    }

    /** @param  list<int>  $studentIds */
    public function concernsAnyStudent(array $studentIds): bool
    {
        return array_intersect(array_map('intval', $this->student_ids ?? []), array_map('intval', $studentIds)) !== [];
    }
}
