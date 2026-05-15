<?php

namespace App\Services;

use App\Models\Lesson;
use App\Models\User;

class LessonCancellationAudit
{
    /**
     * @return array<string, mixed>
     */
    public static function auditAttributes(Lesson $lesson, ?User $user, string $role): array
    {
        $lesson->loadMissing('subscriptionInstances');

        $instanceIds = $lesson->subscriptionInstances
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        return [
            'cancelled_at' => now(),
            'cancelled_by_user_id' => $user?->id,
            'cancelled_by_role' => $role,
            'cancelled_subscription_instance_ids' => $instanceIds !== [] ? $instanceIds : null,
        ];
    }

    public static function applyToLesson(Lesson $lesson, ?User $user, string $role, bool $isNewCancellation = true): void
    {
        if (! $isNewCancellation && $lesson->cancelled_at !== null) {
            return;
        }

        $attrs = self::auditAttributes($lesson, $user, $role);

        if (! $isNewCancellation && ! empty($lesson->cancelled_subscription_instance_ids)) {
            unset($attrs['cancelled_subscription_instance_ids']);
        }

        foreach ($attrs as $key => $value) {
            $lesson->{$key} = $value;
        }
    }

    public static function inferRoleFromNotes(?string $notes): string
    {
        $notes = $notes ?? '';

        if (str_contains($notes, "[Annulé par l'élève]")) {
            return 'student';
        }

        if (str_contains($notes, '[Annulé')) {
            return 'club';
        }

        return 'unknown';
    }
}
