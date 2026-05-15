<?php

namespace App\Http\Resources;

use App\Models\Lesson;
use App\Services\LessonReactivationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Lesson */
class CancelledLessonResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $studentName = null;
        if ($this->student) {
            $studentName = $this->student->user?->name
                ?? trim(($this->student->first_name ?? '') . ' ' . ($this->student->last_name ?? ''));
        } elseif ($this->students?->isNotEmpty()) {
            $studentName = $this->students->map(function ($s) {
                return $s->user?->name ?? trim(($s->first_name ?? '') . ' ' . ($s->last_name ?? ''));
            })->filter()->join(', ');
        }

        $reactivationService = app(LessonReactivationService::class);
        $recurringSlot = $reactivationService->findMatchingRecurringSlot($this->resource);

        $isPast = $this->start_time ? Carbon::parse($this->start_time)->isPast() : false;

        return [
            'id' => $this->id,
            'start_time' => $this->start_time?->toIso8601String(),
            'end_time' => $this->end_time?->toIso8601String(),
            'status' => $this->status,
            'student_id' => $this->student_id,
            'student_name' => $studentName ?: 'Élève non défini',
            'teacher_id' => $this->teacher_id,
            'teacher_name' => $this->teacher?->user?->name ?? 'Non assigné',
            'course_type_name' => $this->courseType?->name ?? 'Cours',
            'cancellation_reason' => $this->cancellation_reason,
            'cancellation_certificate_status' => $this->cancellation_certificate_status,
            'cancellation_count_in_subscription' => (bool) $this->cancellation_count_in_subscription,
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'cancelled_by_role' => $this->cancelled_by_role,
            'cancelled_by' => $this->whenLoaded('cancelledByUser', function () {
                return [
                    'id' => $this->cancelledByUser?->id,
                    'name' => $this->cancelledByUser?->name,
                ];
            }),
            'cancelled_by_label' => $this->cancelledByLabel(),
            'notes_excerpt' => $this->cancellationNotesExcerpt(),
            'subscription_instance_ids' => $this->cancelled_subscription_instance_ids ?? [],
            'recurring_slot' => $recurringSlot ? [
                'id' => $recurringSlot->id,
                'status' => $recurringSlot->status,
            ] : null,
            'is_past' => $isPast,
            'can_reactivate' => $this->status === 'cancelled',
            'has_recurring_series' => $this->relationLoaded('subscriptionInstances')
                ? $this->subscriptionInstances->isNotEmpty()
                : null,
        ];
    }

    private function cancelledByLabel(): string
    {
        if ($this->cancelledByUser?->name) {
            return $this->cancelledByUser->name;
        }

        return match ($this->cancelled_by_role) {
            'student' => 'Élève',
            'club' => 'Club',
            'teacher' => 'Enseignant',
            'system' => 'Système',
            default => 'Inconnu',
        };
    }

    private function cancellationNotesExcerpt(): ?string
    {
        $notes = $this->notes ?? '';
        if ($notes === '') {
            return null;
        }

        foreach (preg_split('/\r\n|\r|\n/', $notes) as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            if (str_contains($line, '[Annulé') || str_contains($line, '[Réactivé')) {
                return mb_strlen($line) > 120 ? mb_substr($line, 0, 117) . '...' : $line;
            }
        }

        return mb_strlen($notes) > 120 ? mb_substr($notes, 0, 117) . '...' : $notes;
    }
}
