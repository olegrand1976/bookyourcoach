<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ActivityTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $activityTypes = [
            ['id' => 1, 'name' => 'Équitation', 'slug' => 'equitation', 'icon' => '🐴', 'description' => 'Sports équestres et équitation'],
            ['id' => 2, 'name' => 'Natation', 'slug' => 'natation', 'icon' => '🏊', 'description' => 'Natation et sports aquatiques'],
            ['id' => 3, 'name' => 'Fitness', 'slug' => 'fitness', 'icon' => '💪', 'description' => 'Fitness, musculation et remise en forme'],
            ['id' => 4, 'name' => 'Sports collectifs', 'slug' => 'sports-collectifs', 'icon' => '⚽', 'description' => 'Sports d\'équipe'],
            ['id' => 5, 'name' => 'Arts martiaux', 'slug' => 'arts-martiaux', 'icon' => '🥋', 'description' => 'Arts martiaux et sports de combat'],
            ['id' => 6, 'name' => 'Danse', 'slug' => 'danse', 'icon' => '💃', 'description' => 'Danse et chorégraphie'],
            ['id' => 7, 'name' => 'Tennis', 'slug' => 'tennis', 'icon' => '🎾', 'description' => 'Tennis et sports de raquette'],
            ['id' => 8, 'name' => 'Gymnastique', 'slug' => 'gymnastique', 'icon' => '🤸', 'description' => 'Gymnastique artistique et rythmique'],
        ];

        foreach ($activityTypes as $activityType) {
            DB::table('activity_types')->updateOrInsert(
                ['id' => $activityType['id']],
                [
                    'name' => $activityType['name'],
                    'slug' => $activityType['slug'],
                    'icon' => $activityType['icon'] ?? null,
                    'description' => $activityType['description'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        $this->command->info('✅ ' . count($activityTypes) . ' types d\'activités insérés/mis à jour avec succès !');
    }
}
