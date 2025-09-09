<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\AppSetting;

class ContractSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $volunteerSettings = [
            'volunteer' => [
                'name' => 'Bénévole',
                'annual_ceiling' => 3900,
                'daily_ceiling' => 42.31,
                'mileage_allowance' => 0.4,
                'max_annual_mileage' => 2000
            ]
        ];

        AppSetting::updateOrCreate(
            ['key' => 'contract_parameters'],
            [
                'value' => json_encode($volunteerSettings),
                'type' => 'json'
            ]
        );
    }
}
