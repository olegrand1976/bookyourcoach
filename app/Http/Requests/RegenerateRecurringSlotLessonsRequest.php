<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegenerateRecurringSlotLessonsRequest extends FormRequest
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
        return [
            'from_date' => ['nullable', 'date_format:Y-m-d'],
        ];
    }
}
