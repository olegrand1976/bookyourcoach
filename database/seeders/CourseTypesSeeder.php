<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\CourseType;
use App\Models\Discipline;

class CourseTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Types de cours génériques (sans discipline spécifique)
        $genericTypes = [
            [
                'name' => 'Cours individuel',
                'description' => 'Cours particulier avec un seul élève',
                'duration_minutes' => 60,
                'is_individual' => true,
                'max_participants' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'Cours collectif (2 élèves)',
                'description' => 'Cours en petit groupe de 2 élèves',
                'duration_minutes' => 60,
                'is_individual' => false,
                'max_participants' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'Cours collectif (3-4 élèves)',
                'description' => 'Cours en petit groupe de 3 à 4 élèves',
                'duration_minutes' => 60,
                'is_individual' => false,
                'max_participants' => 4,
                'is_active' => true,
            ],
            [
                'name' => 'Cours collectif (5-8 élèves)',
                'description' => 'Cours en groupe de 5 à 8 élèves',
                'duration_minutes' => 90,
                'is_individual' => false,
                'max_participants' => 8,
                'is_active' => true,
            ],
            [
                'name' => 'Stage découverte',
                'description' => 'Stage d\'initiation ou découverte',
                'duration_minutes' => 120,
                'is_individual' => false,
                'max_participants' => 10,
                'is_active' => true,
            ],
            [
                'name' => 'Session intensive',
                'description' => 'Session de cours intensif',
                'duration_minutes' => 180,
                'is_individual' => false,
                'max_participants' => 6,
                'is_active' => true,
            ],
        ];

        foreach ($genericTypes as $type) {
            CourseType::firstOrCreate(
                ['name' => $type['name'], 'discipline_id' => null],
                $type
            );
        }

        // Pour chaque discipline existante, créer un type individuel et collectif
        $disciplines = Discipline::all();
        foreach ($disciplines as $discipline) {
            CourseType::firstOrCreate(
                [
                    'name' => 'Cours individuel',
                    'discipline_id' => $discipline->id
                ],
                [
                    'description' => "Cours particulier de {$discipline->name}",
                    'duration_minutes' => 60,
                    'is_individual' => true,
                    'max_participants' => 1,
                    'is_active' => true,
                ]
            );

            CourseType::firstOrCreate(
                [
                    'name' => 'Cours collectif',
                    'discipline_id' => $discipline->id
                ],
                [
                    'description' => "Cours collectif de {$discipline->name}",
                    'duration_minutes' => 60,
                    'is_individual' => false,
                    'max_participants' => 4,
                    'is_active' => true,
                ]
            );
        }

        $this->command->info('✅ Types de cours créés avec succès!');
        $this->command->info('   - ' . count($genericTypes) . ' types génériques');
        $this->command->info('   - ' . ($disciplines->count() * 2) . ' types par discipline');
    }
}
