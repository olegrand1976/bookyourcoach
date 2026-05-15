<?php

namespace App\Http\Resources;

use App\Models\LessonActionLog;
use App\Services\LessonActionLogService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin LessonActionLog */
class LessonActionLogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $logService = app(LessonActionLogService::class);

        $studentName = $logService->studentDisplayName($this->student);
        if ($studentName === null && is_array($this->meta['student_names'] ?? null)) {
            $studentName = implode(', ', $this->meta['student_names']);
        }

        $subscriptionLabel = $logService->subscriptionDisplayLabel($this->subscriptionInstance);
        if ($subscriptionLabel === null && is_array($this->meta['subscription_labels'] ?? null)) {
            $subscriptionLabel = implode(', ', $this->meta['subscription_labels']);
        }

        $performedByName = $this->performedByUser?->name;
        if ($performedByName === null) {
            $performedByName = match ($this->performed_by_role) {
                'student' => 'Élève',
                'club' => 'Club',
                'teacher' => 'Enseignant',
                'system' => 'Système',
                default => '—',
            };
        }

        return [
            'id' => $this->id,
            'action' => $this->action,
            'action_label' => $this->actionLabel(),
            'created_at' => $this->created_at?->toIso8601String(),
            'lesson_id' => $this->lesson_id,
            'lesson_start_time' => $this->meta['lesson_start_time'] ?? $this->lesson?->start_time?->toIso8601String(),
            'student_id' => $this->student_id,
            'student_name' => $studentName ?: '—',
            'subscription_instance_id' => $this->subscription_instance_id,
            'subscription_label' => $subscriptionLabel,
            'performed_by' => [
                'user_id' => $this->performed_by_user_id,
                'name' => $performedByName,
                'role' => $this->performed_by_role,
            ],
            'meta' => $this->meta,
        ];
    }
}
