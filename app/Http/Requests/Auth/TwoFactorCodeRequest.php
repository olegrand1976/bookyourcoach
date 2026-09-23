<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Confirmation d'un nouveau téléphone : le premier code généré par le nouveau secret.
 */
class TwoFactorCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:10'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Saisissez le code à 6 chiffres affiché par votre nouvelle application.',
        ];
    }
}
