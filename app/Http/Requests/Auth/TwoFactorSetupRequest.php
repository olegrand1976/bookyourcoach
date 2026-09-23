<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Étape 2FA de la connexion : on n'est pas encore authentifié, c'est le
 * challenge_token remis après le mot de passe qui fait foi.
 */
class TwoFactorSetupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'challenge_token' => ['required', 'string', 'size:64'],
        ];
    }

    public function messages(): array
    {
        return [
            'challenge_token.*' => 'Session de vérification invalide : reconnectez-vous.',
        ];
    }
}
