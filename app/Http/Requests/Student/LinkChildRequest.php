<?php

namespace App\Http\Requests\Student;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class LinkChildRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        return $user !== null && $user->role === User::ROLE_STUDENT;
    }

    public function rules(): array
    {
        return [
            'invite_code' => ['required', 'string', 'min:6', 'max:24'],
        ];
    }

    public function messages(): array
    {
        return [
            'invite_code.required' => 'Le code d\'invitation est obligatoire.',
            'invite_code.min' => 'Le code d\'invitation est trop court.',
            'invite_code.max' => 'Le code d\'invitation est trop long.',
        ];
    }
}
