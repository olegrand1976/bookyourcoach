<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Mention « dernier abonnement » : la récurrence de l'élève s'arrête à la fin de cet abonnement.
 */
class UpdateLastSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'club';
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'is_last_subscription' => 'required|boolean',
        ];
    }
}
