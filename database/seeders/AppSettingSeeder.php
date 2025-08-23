<?php

namespace Database\Seeders;

use App\Models\AppSetting;
use Illuminate\Database\Seeder;

class AppSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Supprimer les anciens paramètres
        AppSetting::query()->delete();

        // Créer les paramètres par défaut de BookYourCoach
        AppSetting::create([
            'app_name' => 'BookYourCoach',
            'primary_color' => '#2563eb',   // Bleu principal
            'secondary_color' => '#1e40af', // Bleu foncé
            'accent_color' => '#3b82f6',    // Bleu accent
            'logo_url' => null,
            'logo_path' => null,
            'app_description' => 'Plateforme de réservation de cours avec des coaches professionnels',
            'contact_email' => 'contact@bookyourcoach.com',
            'contact_phone' => '+32 2 123 45 67',
            'social_links' => [
                'facebook' => 'https://facebook.com/bookyourcoach',
                'instagram' => 'https://instagram.com/bookyourcoach',
                'linkedin' => 'https://linkedin.com/company/bookyourcoach'
            ],
            'is_active' => true
        ]);
    }
}
