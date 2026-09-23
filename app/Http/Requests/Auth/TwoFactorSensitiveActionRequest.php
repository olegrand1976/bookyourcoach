<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Action sensible sur la 2FA de son propre compte (nouveaux codes de récupération,
 * changement de téléphone) : mot de passe ET code courant exigés, pour qu'une
 * session laissée ouverte ne suffise pas à détourner le second facteur.
 */
class TwoFactorSensitiveActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'password' => ['required', 'string', 'current_password:sanctum'],
            'code' => ['required', 'string', 'max:10'],
        ];
    }

    public function messages(): array
    {
        return [
            'password.required' => 'Saisissez votre mot de passe.',
            'password.current_password' => 'Mot de passe incorrect.',
            'code.required' => 'Saisissez le code à 6 chiffres de votre application.',
        ];
    }
}
