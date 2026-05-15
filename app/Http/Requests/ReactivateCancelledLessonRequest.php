<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReactivateCancelledLessonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'club';
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'reactivate_scope' => 'sometimes|in:single,all_future',
            'restore_recurring_slot' => 'sometimes|boolean',
            'reattach_subscription' => 'sometimes|boolean',
            'reason' => 'nullable|string|max:500',
        ];
    }
}
