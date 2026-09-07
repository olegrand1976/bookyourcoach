<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LessonMovementHistory extends Model
{
    public $timestamps = false;

    public const EVENT_CREATED = 'created';

    public const EVENT_UPDATED = 'updated';

    public const EVENT_MOVED = 'moved';

    public const EVENT_TEACHER_REASSIGNED = 'teacher_reassigned';

    public const EVENT_CANCELLED = 'cancelled';

    public const EVENT_DELETED = 'deleted';

    public const EVENT_REACTIVATED = 'reactivated';

    public const EVENT_SUBSCRIPTION_LINKED = 'subscription_linked';

    public const EVENT_TEACHER_EXCEPTION_SKIPPED = 'teacher_exception_skipped';

    protected $fillable = [
        'club_id',
        'lesson_id',
        'event',
        'update_scope',
        'cascade_from_lesson_id',
        'old_teacher_id',
        'new_teacher_id',
        'old_start_time',
        'new_start_time',
        'old_end_time',
        'new_end_time',
        'old_status',
        'new_status',
        'student_id',
        'performed_by_user_id',
        'performed_by_role',
        'meta',
        'created_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'club_id' => 'integer',
        'lesson_id' => 'integer',
        'cascade_from_lesson_id' => 'integer',
        'old_teacher_id' => 'integer',
        'new_teacher_id' => 'integer',
        'student_id' => 'integer',
        'performed_by_user_id' => 'integer',
        'old_start_time' => 'datetime',
        'new_start_time' => 'datetime',
        'old_end_time' => 'datetime',
        'new_end_time' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function cascadeFromLesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class, 'cascade_from_lesson_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function oldTeacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'old_teacher_id');
    }

    public function newTeacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'new_teacher_id');
    }

    public function performedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by_user_id');
    }
}
