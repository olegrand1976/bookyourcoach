<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpsertClubClosureDayRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->getFirstClub() !== null;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'date' => ['required', 'date_format:Y-m-d'],
            'closed' => ['required', 'boolean'],
            // Intention explicite : le client annonce ce qu'il croit impacter.
            // Le contrôleur refuse la fermeture si ce chiffre ne correspond pas
            // à la réalité — un client mal informé ne doit pas pouvoir agir.
            'expected_impacted_lessons' => ['sometimes', 'integer', 'min:0'],
            'acknowledge_impact' => ['sometimes', 'boolean'],
            // Fermer ou rouvrir une journée prévient tout le monde et touche aux
            // carnets : une session laissée ouverte ne doit pas suffire. Le secret
            // est vérifié par le contrôleur, pour que les refus soient tracés.
            'confirmation_method' => ['required', 'string', 'in:password,totp'],
            'password' => ['required_if:confirmation_method,password', 'nullable', 'string', 'max:255'],
            'code' => ['required_if:confirmation_method,totp', 'nullable', 'string', 'max:10'],
        ];
    }

    public function messages(): array
    {
        return [
            'confirmation_method.required' => 'Confirmez la demande par votre mot de passe ou votre code 2FA.',
            'confirmation_method.in' => 'Méthode de confirmation inconnue.',
            'password.required_if' => 'Saisissez votre mot de passe.',
            'code.required_if' => 'Saisissez le code à 6 chiffres de votre application.',
        ];
    }
}
