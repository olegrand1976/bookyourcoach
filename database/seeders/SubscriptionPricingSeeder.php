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
            $standard = SubscriptionTemplate::updateOrCreate(
                [
                    'club_id' => $club->id,
                    'price' => 180.00
                ],
                [
                    'model_number' => 'Abonnement PRO',
                    'total_lessons' => 10,
                    'validity_months' => 24,
                    'is_active' => true,
                    'is_recurring' => false,
                ]
            );

            $courseTypes = CourseType::all()->pluck('id');
            $standard->courseTypes()->sync($courseTypes);

            SubscriptionTemplate::updateOrCreate(
                [
                    'club_id' => $club->id,
                    'price' => 18.00
                ],
                [
                    'model_number' => 'Individuel',
                    'total_lessons' => 1,
                    'validity_months' => 1,
                    'is_active' => true,
                    'is_recurring' => false,
                ]
            )->courseTypes()->sync($courseTypes);
        }
    }
}
