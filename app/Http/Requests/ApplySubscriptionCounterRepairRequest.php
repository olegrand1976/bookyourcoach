<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApplySubscriptionCounterRepairRequest extends FormRequest
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
            'instance_ids' => 'required|array|min:1|max:500',
            'instance_ids.*' => 'integer|distinct',
            'detach_future_excess_for' => 'sometimes|array|max:500',
            'detach_future_excess_for.*' => 'integer|distinct',
        ];
    }
}
