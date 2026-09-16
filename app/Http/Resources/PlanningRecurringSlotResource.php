<?php

namespace App\Http\Resources;

use App\Models\SubscriptionRecurringSlot;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Payload léger pour la grille planning (placeholders récurrence).
 */
class PlanningRecurringSlotResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var SubscriptionRecurringSlot $slot */
        $slot = $this->resource;

        $mapUserName = static function ($model): ?array {
            if (! $model) {
                return null;
            }

            $user = ($model->relationLoaded('user') && $model->user)
                ? [
                    'id' => $model->user->id,
                    'name' => $model->user->name,
                ]
                : null;

            return [
                'id' => $model->id,
                'user_id' => $model->user_id ?? null,
                'first_name' => $model->first_name ?? null,
                'last_name' => $model->last_name ?? null,
                'user' => $user,
            ];
        };

        $subscriptionInstance = null;
        if ($slot->relationLoaded('subscriptionInstance') && $slot->subscriptionInstance) {
            $si = $slot->subscriptionInstance;
            $template = null;
            if ($si->relationLoaded('subscription') && $si->subscription
                && $si->subscription->relationLoaded('template') && $si->subscription->template) {
                $tmpl = $si->subscription->template;
                // Pas de colonne name sur subscription_templates : model_number pour le libellé UI
                $template = [
                    'id' => $tmpl->id,
                    'name' => $tmpl->model_number,
                    'model_number' => $tmpl->model_number,
                    'price' => $tmpl->price !== null ? (float) $tmpl->price : null,
                ];
            }
            $subscriptionInstance = [
                'id' => $si->id,
                'subscription' => $template !== null
                    ? ['template' => $template]
                    : null,
            ];
        }

        return [
            'id' => $slot->id,
            'teacher_id' => $slot->teacher_id,
            'student_id' => $slot->student_id,
            'day_of_week' => $slot->day_of_week,
            'start_time' => $slot->start_time,
            'end_time' => $slot->end_time,
            'recurring_interval' => $slot->recurring_interval,
            'start_date' => $slot->start_date?->format('Y-m-d') ?? $slot->start_date,
            'end_date' => $slot->end_date?->format('Y-m-d') ?? $slot->end_date,
            'status' => $slot->status,
            'skipped_dates' => array_values(array_map(
                static fn ($d) => substr((string) $d, 0, 10),
                $slot->skipped_dates ?? []
            )),
            'subscription_instance_id' => $slot->subscription_instance_id,
            'open_slot_id' => $slot->open_slot_id,
            'teacher' => ($slot->relationLoaded('teacher') && $slot->teacher)
                ? $mapUserName($slot->teacher)
                : null,
            'student' => ($slot->relationLoaded('student') && $slot->student)
                ? $mapUserName($slot->student)
                : null,
            'subscription_instance' => $subscriptionInstance,
        ];
    }
}
