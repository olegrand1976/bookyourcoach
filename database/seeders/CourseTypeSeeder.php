<?php

namespace Database\Seeders;

use App\Models\CourseType;
use Illuminate\Database\Seeder;

class CourseTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $courseTypes = [
            [
                'name' => 'Dressage',
                'description' => 'Cours de dressage classique pour améliorer l\'harmonie entre le cavalier et sa monture',
                'duration' => 60,
                'price' => 45.00,
            ],
            [
                'name' => 'Saut d\'obstacles',
                'description' => 'Apprentissage et perfectionnement du saut d\'obstacles',
                'duration' => 60,
                'price' => 50.00,
            ],
            [
                'name' => 'Cross-country',
                'description' => 'Cours de cross-country en extérieur avec obstacles naturels',
                'duration' => 90,
                'price' => 65.00,
            ],
            [
                'name' => 'Équitation Western',
                'description' => 'Initiation et perfectionnement à l\'équitation western',
                'duration' => 60,
                'price' => 55.00,
            ],
            [
                'name' => 'Travail à pied',
                'description' => 'Éducation et travail du cheval à pied, éthologie',
                'duration' => 45,
                'price' => 35.00,
            ],
            [
                'name' => 'Longe et liberté',
                'description' => 'Travail en longe et en liberté pour développer la relation avec le cheval',
                'duration' => 45,
                'price' => 40.00,
            ],
            [
                'name' => 'Cours d\'initiation',
                'description' => 'Premier contact avec l\'équitation, cours pour débutants',
                'duration' => 45,
                'price' => 30.00,
            ],
            [
                'name' => 'Perfectionnement',
                'description' => 'Cours de perfectionnement pour cavaliers confirmés',
                'duration' => 60,
                'price' => 60.00,
            ],
            [
                'name' => 'Préparation concours',
                'description' => 'Préparation spécifique pour les compétitions équestres',
                'duration' => 90,
                'price' => 80.00,
            ],
            [
                'name' => 'Cours collectif',
                'description' => 'Cours en groupe (maximum 6 cavaliers)',
                'duration' => 60,
                'price' => 25.00,
            ]
        ];

        foreach ($courseTypes as $courseType) {
            CourseType::create($courseType);
        }
    }
}
