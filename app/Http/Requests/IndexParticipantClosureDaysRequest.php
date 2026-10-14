<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IndexParticipantClosureDaysRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'date_from' => ['required', 'date_format:Y-m-d'],
            'date_to' => ['required', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'club_id' => ['sometimes', 'integer', 'exists:clubs,id'],
        ];
    }
}
