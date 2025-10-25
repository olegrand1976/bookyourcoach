<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AppSetting>
 */
class AppSettingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $brandNames = [
            'activibe',
            'EquestrianPro',
            'CoachConnect',
            'LessonMaster',
            'RideBooking',
            'HorseAcademy'
        ];

        $colorSchemes = [
            ['#2563eb', '#1e40af', '#3b82f6'], // Bleu
            ['#dc2626', '#b91c1c', '#ef4444'], // Rouge
            ['#059669', '#047857', '#10b981'], // Vert
            ['#7c3aed', '#6d28d9', '#8b5cf6'], // Violet
            ['#ea580c', '#c2410c', '#f97316'], // Orange
            ['#0891b2', '#0e7490', '#06b6d4'], // Cyan
        ];

        $scheme = $this->faker->randomElement($colorSchemes);

        return [
            'app_name' => $this->faker->randomElement($brandNames),
            'primary_color' => $scheme[0],
            'secondary_color' => $scheme[1],
            'accent_color' => $scheme[2],
            'logo_url' => $this->faker->optional(0.7)->imageUrl(200, 80, 'business'),
            'logo_path' => $this->faker->optional(0.3)->filePath(),
            'app_description' => $this->faker->optional(0.8)->catchPhrase(),
            'contact_email' => $this->faker->optional(0.9)->safeEmail(),
            'contact_phone' => $this->faker->optional(0.7)->phoneNumber(),
            'social_links' => $this->faker->optional(0.6)->randomElements([
                'facebook' => 'https://facebook.com/' . $this->faker->userName(),
                'instagram' => 'https://instagram.com/' . $this->faker->userName(),
                'twitter' => 'https://twitter.com/' . $this->faker->userName(),
                'linkedin' => 'https://linkedin.com/company/' . $this->faker->slug(2),
            ], $this->faker->numberBetween(1, 4)),
            'is_active' => true,
        ];
    }
}
