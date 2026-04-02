<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            'audience' => ['required', 'string', 'in:teachers,students,both'],
            'subject' => ['required', 'string', 'max:200'],
            'body' => ['required', 'string', 'max:20000'],
        ];
    }

    public function messages(): array
    {
        return [
            'audience.required' => 'Choisissez au moins un groupe de destinataires.',
            'audience.in' => 'Destinataires invalides.',
        ];
    }
}
