<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AppSettingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && $this->user()->role === 'admin';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'app_name' => 'required|string|max:255',
            'primary_color' => 'required|string|regex:/^#[a-fA-F0-9]{6}$/',
            'secondary_color' => 'required|string|regex:/^#[a-fA-F0-9]{6}$/',
            'accent_color' => 'required|string|regex:/^#[a-fA-F0-9]{6}$/',
            'logo_url' => 'nullable|url|max:500',
            'logo_path' => 'nullable|string|max:500',
            'app_description' => 'nullable|string|max:1000',
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:20',
            'social_links' => 'nullable|array',
            'social_links.facebook' => 'nullable|url|max:255',
            'social_links.instagram' => 'nullable|url|max:255',
            'social_links.twitter' => 'nullable|url|max:255',
            'social_links.linkedin' => 'nullable|url|max:255',
            'social_links.youtube' => 'nullable|url|max:255',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'primary_color.regex' => 'La couleur principale doit être au format hexadécimal (#000000)',
            'secondary_color.regex' => 'La couleur secondaire doit être au format hexadécimal (#000000)',
            'accent_color.regex' => 'La couleur d\'accent doit être au format hexadécimal (#000000)',
            'app_name.required' => 'Le nom de l\'application est obligatoire',
            'contact_email.email' => 'L\'email de contact doit être valide',
            'social_links.*.url' => 'Les liens des réseaux sociaux doivent être des URLs valides',
        ];
    }
}
