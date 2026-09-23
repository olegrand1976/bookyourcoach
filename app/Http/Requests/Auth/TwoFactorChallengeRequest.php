<?php

namespace App\Http\Requests\Auth;

class TwoFactorChallengeRequest extends TwoFactorSetupRequest
{
    public function rules(): array
    {
        return parent::rules() + [
            'code' => ['nullable', 'required_without:recovery_code', 'string', 'max:10'],
            'recovery_code' => ['nullable', 'required_without:code', 'string', 'max:32'],
            'remember_device' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return parent::messages() + [
            'code.required_without' => 'Saisissez le code à 6 chiffres ou un code de récupération.',
            'recovery_code.required_without' => 'Saisissez le code à 6 chiffres ou un code de récupération.',
        ];
    }
}
