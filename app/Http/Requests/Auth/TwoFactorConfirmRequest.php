<?php

namespace App\Http\Requests\Auth;

class TwoFactorConfirmRequest extends TwoFactorSetupRequest
{
    public function rules(): array
    {
        return parent::rules() + [
            'code' => ['required', 'string', 'max:10'],
        ];
    }

    public function messages(): array
    {
        return parent::messages() + [
            'code.required' => 'Saisissez le code à 6 chiffres affiché par votre application.',
        ];
    }
}
