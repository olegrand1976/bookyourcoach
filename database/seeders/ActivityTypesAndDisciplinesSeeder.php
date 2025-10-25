<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ActivityTypesAndDisciplinesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Vider les tables d'abord
        DB::table('disciplines')->truncate();
        DB::table('activity_types')->truncate();

        // Insérer les types d'activités
        $activityTypes = [
            [
                'id' => 1,
                'name' => 'Équitation',
                'slug' => 'equitation',
                'description' => 'Sports équestres et monte à cheval',
                'icon' => '🐎',
                'color' => '#8B4513',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'name' => 'Natation',
                'slug' => 'natation',
                'description' => 'Sports aquatiques et natation',
                'icon' => '🏊‍♀️',
                'color' => '#0EA5E9',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'name' => 'Fitness',
                'slug' => 'fitness',
                'description' => 'Musculation et remise en forme',
                'icon' => '💪',
                'color' => '#DC2626',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 4,
                'name' => 'Sports collectifs',
                'slug' => 'sports-collectifs',
                'description' => 'Football, basketball, volleyball',
                'icon' => '⚽',
                'color' => '#16A34A',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 5,
                'name' => 'Arts martiaux',
                'slug' => 'arts-martiaux',
                'description' => 'Karaté, judo, taekwondo',
                'icon' => '🥋',
                'color' => '#7C3AED',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 6,
                'name' => 'Danse',
                'slug' => 'danse',
                'description' => 'Danse classique, moderne, hip-hop',
                'icon' => '💃',
                'color' => '#EC4899',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 7,
                'name' => 'Tennis',
                'slug' => 'tennis',
                'description' => 'Tennis de table et tennis',
                'icon' => '🎾',
                'color' => '#FACC15',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 8,
                'name' => 'Gymnastique',
                'slug' => 'gymnastique',
                'description' => 'Gymnastique artistique et rythmique',
                'icon' => '🤸‍♀️',
                'color' => '#F59E0B',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('activity_types')->insert($activityTypes);

        // Insérer les disciplines - Natation avec les nouvelles spécialités
        $disciplines = [
            // Équitation (activity_type_id: 1)
            [
                'id' => 1,
                'name' => 'Dressage',
                'slug' => 'dressage',
                'description' => 'Discipline olympique axée sur l\'harmonie entre cavalier et cheval',
                'activity_type_id' => 1,
                'min_participants' => 1,
                'max_participants' => 1,
                'duration_minutes' => 60,
                'skill_levels' => json_encode(['débutant', 'intermédiaire', 'avancé', 'expert']),
                'base_price' => 50.00,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'name' => 'Saut d\'obstacles',
                'slug' => 'saut-obstacles',
                'description' => 'Parcours avec obstacles de différentes hauteurs',
                'activity_type_id' => 1,
                'min_participants' => 1,
                'max_participants' => 1,
                'duration_minutes' => 60,
                'skill_levels' => json_encode(['intermédiaire', 'avancé', 'expert']),
                'base_price' => 55.00,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 7,
                'name' => 'Équitation de loisir',
                'slug' => 'equitation-loisir',
                'description' => 'Promenades et balades à cheval pour tous niveaux',
                'activity_type_id' => 1,
                'min_participants' => 1,
                'max_participants' => 6,
                'duration_minutes' => 90,
                'skill_levels' => json_encode(['débutant', 'intermédiaire']),
                'base_price' => 35.00,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Natation (activity_type_id: 2) - NOUVELLES SPÉCIALITÉS
            [
                'id' => 11,
                'name' => 'Cours individuel enfant',
                'slug' => 'cours-individuel-enfant',
                'description' => 'Cours de natation individuel pour enfants (6-12 ans)',
                'activity_type_id' => 2,
                'min_participants' => 1,
                'max_participants' => 1,
                'duration_minutes' => 30,
                'skill_levels' => json_encode(['débutant', 'intermédiaire', 'avancé']),
                'base_price' => 35.00,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 12,
                'name' => 'Cours individuel adulte',
                'slug' => 'cours-individuel-adulte',
                'description' => 'Cours de natation individuel pour adultes',
                'activity_type_id' => 2,
                'min_participants' => 1,
                'max_participants' => 1,
                'duration_minutes' => 45,
                'skill_levels' => json_encode(['débutant', 'intermédiaire', 'avancé']),
                'base_price' => 45.00,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 13,
                'name' => 'Cours aquagym',
                'slug' => 'cours-aquagym',
                'description' => 'Cours de gymnastique aquatique',
                'activity_type_id' => 2,
                'min_participants' => 5,
                'max_participants' => 15,
                'duration_minutes' => 45,
                'skill_levels' => json_encode(['débutant', 'intermédiaire']),
                'base_price' => 20.00,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 14,
                'name' => 'Cours collectif enfant',
                'slug' => 'cours-collectif-enfant',
                'description' => 'Cours de natation en groupe pour enfants',
                'activity_type_id' => 2,
                'min_participants' => 3,
                'max_participants' => 8,
                'duration_minutes' => 45,
                'skill_levels' => json_encode(['débutant', 'intermédiaire']),
                'base_price' => 25.00,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 15,
                'name' => 'Cours collectif adulte',
                'slug' => 'cours-collectif-adulte',
                'description' => 'Cours de natation en groupe pour adultes',
                'activity_type_id' => 2,
                'min_participants' => 4,
                'max_participants' => 10,
                'duration_minutes' => 60,
                'skill_levels' => json_encode(['débutant', 'intermédiaire', 'avancé']),
                'base_price' => 30.00,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Fitness (activity_type_id: 3) - quelques exemples
            [
                'id' => 21,
                'name' => 'Musculation',
                'slug' => 'musculation',
                'description' => 'Entraînement avec poids et machines',
                'activity_type_id' => 3,
                'min_participants' => 1,
                'max_participants' => 1,
                'duration_minutes' => 60,
                'skill_levels' => json_encode(['débutant', 'intermédiaire', 'avancé']),
                'base_price' => 25.00,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 24,
                'name' => 'Yoga',
                'slug' => 'yoga',
                'description' => 'Pratique de postures et méditation',
                'activity_type_id' => 3,
                'min_participants' => 1,
                'max_participants' => 12,
                'duration_minutes' => 75,
                'skill_levels' => json_encode(['débutant', 'intermédiaire', 'avancé']),
                'base_price' => 18.00,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('disciplines')->insert($disciplines);

        $this->command->info('✅ Types d\'activités et disciplines insérés avec succès !');
        $this->command->info('📊 ' . count($activityTypes) . ' types d\'activités créés');
        $this->command->info('🎯 ' . count($disciplines) . ' disciplines créées');
        $this->command->info('🏊‍♀️ Spécialités natation mises à jour : Cours individuel enfant/adulte, Aquagym, Cours collectif enfant/adulte');
    }
}