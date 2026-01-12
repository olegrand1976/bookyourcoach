<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SubscriptionTemplate;
use App\Models\Club;
use App\Models\CourseType;

class SubscriptionPricingSeeder extends Seeder
{
    public function run(): void
    {
        $clubs = Club::all();
        
        foreach ($clubs as $club) {
            // 1. Abonnement Standard 180 € - Pack de 10 cours
            $standard = SubscriptionTemplate::updateOrCreate(
                [
                    'club_id' => $club->id,
                    'price' => 180.00
                ],
                [
                    'model_number' => 'PACK-10-180',
                    'total_lessons' => 10,
                    'validity_months' => 24, // 24 mois (2 ans) comme demandé
                    'is_active' => true,
                    'is_recurring' => false,
                ]
            );

            // Associer tous les types de cours du club (ou par défaut)
            $courseTypes = CourseType::all()->pluck('id');
            $standard->courseTypes()->sync($courseTypes);

            // 2. Séance d'essai 18 € (Une fois par utilisateur)
            SubscriptionTemplate::updateOrCreate(
                [
                    'club_id' => $club->id,
                    'price' => 18.00
                ],
                [
                    'model_number' => 'TRIAL-18',
                    'total_lessons' => 1,
                    'validity_months' => 24, // On met aussi 24 mois par précaution, même si c'est une séance unique
                    'is_active' => true,
                    'is_recurring' => false,
                ]
            )->courseTypes()->sync($courseTypes);
        }
    }
}
