<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBulkLessonReplacementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->teacher !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'lesson_ids' => ['required', 'array', 'min:1'],
            'lesson_ids.*' => ['required', 'integer', 'exists:lessons,id'],
            'replacement_teacher_id' => ['required', 'integer', 'exists:teachers,id'],
            'reason' => ['required', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
