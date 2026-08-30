<?php

namespace App\Http\Requests;

use App\Services\SubscriptionRecurringSlotDiagnosticsService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecurringSlotDiagnosticsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->role === 'club' && $user->getFirstClub() !== null;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $clubId = $this->user()?->getFirstClub()?->id;

        return [
            'status' => ['nullable', 'string', 'in:active,cancelled,expired,paused'],
            'teacher_id' => [
                'nullable',
                'integer',
                Rule::exists('teachers', 'id')->when(
                    $clubId !== null,
                    fn ($rule) => $rule->where('club_id', $clubId)
                ),
            ],
            'student_id' => [
                'nullable',
                'integer',
                Rule::exists('students', 'id')->when(
                    $clubId !== null,
                    fn ($rule) => $rule->where('club_id', $clubId)
                ),
            ],
            'issue' => [
                'nullable',
                'string',
                Rule::in([
                    SubscriptionRecurringSlotDiagnosticsService::ISSUE_NO_FUTURE_LESSONS,
                    SubscriptionRecurringSlotDiagnosticsService::ISSUE_TEACHER_MISMATCH,
                    SubscriptionRecurringSlotDiagnosticsService::ISSUE_ORPHAN_LESSONS,
                    SubscriptionRecurringSlotDiagnosticsService::ISSUE_CANCELLED_SRS_WITH_FUTURES,
                    SubscriptionRecurringSlotDiagnosticsService::ISSUE_SCHEDULE_DRIFT,
                    SubscriptionRecurringSlotDiagnosticsService::ISSUE_GAP_IN_SERIES,
                ]),
            ],
        ];
    }
}
