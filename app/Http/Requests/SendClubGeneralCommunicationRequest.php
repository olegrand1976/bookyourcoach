<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SendClubGeneralCommunicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && $user->role === \App\Models\User::ROLE_CLUB
            && $user->getFirstClub() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'selection_mode' => ['required', 'string', 'in:all,selected'],
            'audience' => ['required_if:selection_mode,all', 'nullable', 'string', 'in:teachers,students,both'],
            'teacher_ids' => ['sometimes', 'array', 'max:500'],
            'teacher_ids.*' => ['integer', 'exists:teachers,id'],
            'student_ids' => ['sometimes', 'array', 'max:500'],
            'student_ids.*' => ['integer', 'exists:students,id'],
            'subject' => ['required', 'string', 'max:200'],
            'body' => ['required', 'string', 'max:20000'],
        ];
    }

    public function messages(): array
    {
        return [
            'audience.required_if' => 'Choisissez un groupe de destinataires.',
            'audience.in' => 'Destinataires invalides.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            if ($this->input('selection_mode') !== 'selected') {
                return;
            }
            $t = $this->input('teacher_ids', []);
            $s = $this->input('student_ids', []);
            if (!is_array($t)) {
                $t = [];
            }
            if (!is_array($s)) {
                $s = [];
            }
            if ($t === [] && $s === []) {
                $v->errors()->add('teacher_ids', 'Sélectionnez au moins un enseignant ou un élève.');
            }
        });
    }
}
