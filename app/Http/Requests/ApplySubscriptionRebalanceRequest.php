<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Remise en cohérence d'un élève : proposition telle quelle (corps vide) ou ajustée depuis
 * la fiche de cohérence (carnet choisi par cours, compteurs manuels).
 */
class ApplySubscriptionRebalanceRequest extends FormRequest
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
            'assignments' => 'sometimes|array|max:2000',
            'assignments.*.lesson_id' => 'required|integer|distinct',
            'assignments.*.instance_id' => 'present|nullable|integer',
            'manual' => 'sometimes|array|max:200',
            'manual.*.instance_id' => 'required|integer|distinct',
            'manual.*.value' => 'required|integer|min:0|max:1000',
        ];
    }
}
