<?php

namespace Database\Seeders;

use App\Models\Discipline;
use App\Models\CourseType;
use Illuminate\Database\Seeder;

class DisciplineSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Créer les disciplines
        $equitation = Discipline::create([
            'name' => 'Équitation',
            'description' => 'Discipline équestre comprenant dressage, saut d\'obstacles, cross-country et autres spécialités.',
            'is_active' => true,
        ]);

        $natation = Discipline::create([
            'name' => 'Natation',
            'description' => 'Discipline aquatique comprenant cours particuliers et aquagym.',
            'is_active' => true,
        ]);

        // Créer les types de cours pour l'équitation
        CourseType::create([
            'discipline_id' => $equitation->id,
            'name' => 'Dressage particulier',
            'description' => 'Cours de dressage individuel adapté au niveau de l\'élève.',
            'duration_minutes' => null, // Variable selon l'enseignant
            'is_individual' => true,
            'max_participants' => 1,
            'is_active' => true,
        ]);

        CourseType::create([
            'discipline_id' => $equitation->id,
            'name' => 'Dressage collectif',
            'description' => 'Cours de dressage en groupe pour partager l\'apprentissage.',
            'duration_minutes' => null, // Variable selon l'enseignant
            'is_individual' => false,
            'max_participants' => 6,
            'is_active' => true,
        ]);

        CourseType::create([
            'discipline_id' => $equitation->id,
            'name' => 'Obstacles particulier',
            'description' => 'Cours de saut d\'obstacles individuel pour progresser rapidement.',
            'duration_minutes' => null, // Variable selon l'enseignant
            'is_individual' => true,
            'max_participants' => 1,
            'is_active' => true,
        ]);

        CourseType::create([
            'discipline_id' => $equitation->id,
            'name' => 'Obstacles collectif',
            'description' => 'Cours de saut d\'obstacles en groupe pour l\'émulation.',
            'duration_minutes' => null, // Variable selon l'enseignant
            'is_individual' => false,
            'max_participants' => 4,
            'is_active' => true,
        ]);

        // Créer les types de cours pour la natation
        CourseType::create([
            'discipline_id' => $natation->id,
            'name' => 'Cours particulier',
            'description' => 'Cours de natation individuel de 20 minutes.',
            'duration_minutes' => 20,
            'is_individual' => true,
            'max_participants' => 1,
            'is_active' => true,
        ]);

        CourseType::create([
            'discipline_id' => $natation->id,
            'name' => 'Aquagym',
            'description' => 'Cours d\'aquagym collectif d\'une heure.',
            'duration_minutes' => 60,
            'is_individual' => false,
            'max_participants' => 12,
            'is_active' => true,
        ]);
    }
}