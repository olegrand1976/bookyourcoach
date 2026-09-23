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
        ];
    }
}
