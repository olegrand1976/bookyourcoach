<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DisciplineSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $disciplines = [
            // Équitation (activity_type_id: 1)
            ['id' => 1, 'name' => 'Dressage', 'activity_type_id' => 1, 'description' => 'Dressage équestre'],
            ['id' => 2, 'name' => 'Saut d\'obstacles', 'activity_type_id' => 1, 'description' => 'Saut d\'obstacles équestre'],
            ['id' => 3, 'name' => 'Concours complet', 'activity_type_id' => 1, 'description' => 'Concours complet d\'équitation'],
            ['id' => 4, 'name' => 'Équitation western', 'activity_type_id' => 1, 'description' => 'Équitation de style western'],
            ['id' => 5, 'name' => 'Endurance', 'activity_type_id' => 1, 'description' => 'Endurance équestre'],
            ['id' => 6, 'name' => 'Voltige', 'activity_type_id' => 1, 'description' => 'Voltige équestre'],
            ['id' => 7, 'name' => 'Équitation de loisir', 'activity_type_id' => 1, 'description' => 'Équitation de loisir et promenade'],
            
            // Natation (activity_type_id: 2)
            ['id' => 11, 'name' => 'Cours individuel enfant', 'activity_type_id' => 2, 'description' => 'Cours de natation individuel pour enfants (6-12 ans)'],
            ['id' => 12, 'name' => 'Cours individuel adulte', 'activity_type_id' => 2, 'description' => 'Cours de natation individuel pour adultes'],
            ['id' => 13, 'name' => 'Cours aquagym', 'activity_type_id' => 2, 'description' => 'Cours de gymnastique aquatique'],
            ['id' => 14, 'name' => 'Cours collectif enfant', 'activity_type_id' => 2, 'description' => 'Cours de natation en groupe pour enfants'],
            ['id' => 15, 'name' => 'Cours collectif adulte', 'activity_type_id' => 2, 'description' => 'Cours de natation en groupe pour adultes'],
            
            // Fitness (activity_type_id: 3)
            ['id' => 21, 'name' => 'Musculation', 'activity_type_id' => 3, 'description' => 'Entraînement de musculation'],
            ['id' => 22, 'name' => 'CrossFit', 'activity_type_id' => 3, 'description' => 'Entraînement CrossFit'],
            ['id' => 23, 'name' => 'Cardio-training', 'activity_type_id' => 3, 'description' => 'Entraînement cardio-vasculaire'],
            ['id' => 24, 'name' => 'Yoga', 'activity_type_id' => 3, 'description' => 'Cours de yoga'],
            ['id' => 25, 'name' => 'Pilates', 'activity_type_id' => 3, 'description' => 'Cours de Pilates'],
            ['id' => 26, 'name' => 'Zumba', 'activity_type_id' => 3, 'description' => 'Cours de Zumba'],
            
            // Sports collectifs (activity_type_id: 4)
            ['id' => 31, 'name' => 'Football', 'activity_type_id' => 4, 'description' => 'Football'],
            ['id' => 32, 'name' => 'Basketball', 'activity_type_id' => 4, 'description' => 'Basketball'],
            ['id' => 33, 'name' => 'Volleyball', 'activity_type_id' => 4, 'description' => 'Volleyball'],
            ['id' => 34, 'name' => 'Handball', 'activity_type_id' => 4, 'description' => 'Handball'],
            ['id' => 35, 'name' => 'Rugby', 'activity_type_id' => 4, 'description' => 'Rugby'],
            
            // Arts martiaux (activity_type_id: 5)
            ['id' => 41, 'name' => 'Karaté', 'activity_type_id' => 5, 'description' => 'Karaté'],
            ['id' => 42, 'name' => 'Judo', 'activity_type_id' => 5, 'description' => 'Judo'],
            ['id' => 43, 'name' => 'Taekwondo', 'activity_type_id' => 5, 'description' => 'Taekwondo'],
            ['id' => 44, 'name' => 'Boxe', 'activity_type_id' => 5, 'description' => 'Boxe'],
            ['id' => 45, 'name' => 'Aïkido', 'activity_type_id' => 5, 'description' => 'Aïkido'],
            
            // Danse (activity_type_id: 6)
            ['id' => 51, 'name' => 'Danse classique', 'activity_type_id' => 6, 'description' => 'Danse classique'],
            ['id' => 52, 'name' => 'Danse moderne', 'activity_type_id' => 6, 'description' => 'Danse moderne'],
            ['id' => 53, 'name' => 'Hip-hop', 'activity_type_id' => 6, 'description' => 'Danse hip-hop'],
            ['id' => 54, 'name' => 'Salsa', 'activity_type_id' => 6, 'description' => 'Danse salsa'],
            ['id' => 55, 'name' => 'Tango', 'activity_type_id' => 6, 'description' => 'Danse tango'],
            
            // Tennis (activity_type_id: 7)
            ['id' => 61, 'name' => 'Tennis de table', 'activity_type_id' => 7, 'description' => 'Tennis de table (ping-pong)'],
            ['id' => 62, 'name' => 'Tennis sur court', 'activity_type_id' => 7, 'description' => 'Tennis sur court'],
            ['id' => 63, 'name' => 'Badminton', 'activity_type_id' => 7, 'description' => 'Badminton'],
            
            // Gymnastique (activity_type_id: 8)
            ['id' => 71, 'name' => 'Gymnastique artistique', 'activity_type_id' => 8, 'description' => 'Gymnastique artistique'],
            ['id' => 72, 'name' => 'Gymnastique rythmique', 'activity_type_id' => 8, 'description' => 'Gymnastique rythmique'],
            ['id' => 73, 'name' => 'Trampoline', 'activity_type_id' => 8, 'description' => 'Trampoline'],
        ];

        foreach ($disciplines as $discipline) {
            DB::table('disciplines')->updateOrInsert(
                ['id' => $discipline['id']],
                [
                    'name' => $discipline['name'],
                    'slug' => \Illuminate\Support\Str::slug($discipline['name']),
                    'activity_type_id' => $discipline['activity_type_id'],
                    'description' => $discipline['description'] ?? null,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        $this->command->info('✅ ' . count($disciplines) . ' disciplines insérées/mises à jour avec succès !');
    }
}