<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ActivityType;
use App\Models\Facility;
use App\Models\Discipline;
use App\Models\ProductCategory;
use App\Models\CashRegister;
use App\Models\Product;
use App\Models\Club;

class ActivityTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Types d'activités
        $equestrian = ActivityType::create([
            'name' => 'Équitation',
            'slug' => 'equestrian',
            'description' => 'Club d\'équitation avec manèges, carrières et disciplines équestres',
            'icon' => '🐎',
            'color' => '#8B4513',
            'is_active' => true
        ]);

        $swimming = ActivityType::create([
            'name' => 'Natation',
            'slug' => 'swimming',
            'description' => 'Centre de natation avec bassins et activités aquatiques',
            'icon' => '🏊‍♂️',
            'color' => '#0066CC',
            'is_active' => true
        ]);

        // Installations pour l'équitation
        $facilitiesEquestrian = [
            [
                'name' => 'Manège Principal',
                'type' => 'indoor',
                'capacity' => 4,
                'dimensions' => ['length' => 20, 'width' => 40, 'height' => 4],
                'equipment' => ['obstacles', 'miroirs', 'sonorisation'],
                'description' => 'Manège couvert principal pour cours et compétitions'
            ],
            [
                'name' => 'Carrière A',
                'type' => 'outdoor',
                'capacity' => 6,
                'dimensions' => ['length' => 30, 'width' => 60],
                'equipment' => ['obstacles fixes', 'obstacles mobiles', 'arrosage'],
                'description' => 'Carrière extérieure pour saut d\'obstacles'
            ],
            [
                'name' => 'Carrière B',
                'type' => 'outdoor',
                'capacity' => 4,
                'dimensions' => ['length' => 20, 'width' => 40],
                'equipment' => ['lettres de dressage', 'barres de dressage'],
                'description' => 'Carrière extérieure pour dressage'
            ],
            [
                'name' => 'Paddock',
                'type' => 'outdoor',
                'capacity' => 8,
                'dimensions' => ['length' => 15, 'width' => 25],
                'equipment' => ['barrières', 'abreuvoirs'],
                'description' => 'Zone de détente pour les chevaux'
            ]
        ];

        foreach ($facilitiesEquestrian as $facility) {
            Facility::create(array_merge($facility, ['activity_type_id' => $equestrian->id]));
        }

        // Installations pour la natation
        $facilitiesSwimming = [
            [
                'name' => 'Bassin 25m',
                'type' => 'indoor',
                'capacity' => 16,
                'dimensions' => ['length' => 25, 'width' => 12.5, 'depth' => 1.5],
                'equipment' => ['chronomètres', 'starting blocks', 'lignes de nage'],
                'description' => 'Bassin principal de 25 mètres pour natation sportive'
            ],
            [
                'name' => 'Bassin 50m',
                'type' => 'indoor',
                'capacity' => 24,
                'dimensions' => ['length' => 50, 'width' => 25, 'depth' => 2],
                'equipment' => ['chronomètres', 'starting blocks', 'lignes de nage', 'plongeoir'],
                'description' => 'Bassin olympique de 50 mètres'
            ],
            [
                'name' => 'Piscine Enfants',
                'type' => 'indoor',
                'capacity' => 12,
                'dimensions' => ['length' => 10, 'width' => 8, 'depth' => 0.8],
                'equipment' => ['jouets aquatiques', 'toboggan', 'fontaines'],
                'description' => 'Piscine spécialement conçue pour les enfants'
            ],
            [
                'name' => 'Jacuzzi',
                'type' => 'indoor',
                'capacity' => 8,
                'dimensions' => ['length' => 4, 'width' => 3, 'depth' => 1.2],
                'equipment' => ['jets hydromassants', 'éclairage LED', 'sièges'],
                'description' => 'Zone de relaxation avec jacuzzi'
            ]
        ];

        foreach ($facilitiesSwimming as $facility) {
            Facility::create(array_merge($facility, ['activity_type_id' => $swimming->id]));
        }

        // Disciplines équestres
        $disciplinesEquestrian = [
            [
                'name' => 'Dressage',
                'slug' => 'dressage',
                'description' => 'Art de dresser le cheval avec précision et élégance',
                'min_participants' => 1,
                'max_participants' => 2,
                'duration_minutes' => 60,
                'equipment_required' => ['selle de dressage', 'rênes', 'lettres'],
                'skill_levels' => ['débutant', 'intermédiaire', 'avancé', 'expert'],
                'base_price' => 45.00
            ],
            [
                'name' => 'CSO (Saut d\'obstacles)',
                'slug' => 'cso',
                'description' => 'Concours de saut d\'obstacles',
                'min_participants' => 1,
                'max_participants' => 4,
                'duration_minutes' => 60,
                'equipment_required' => ['obstacles', 'selle de saut', 'protège-bras'],
                'skill_levels' => ['débutant', 'intermédiaire', 'avancé'],
                'base_price' => 50.00
            ],
            [
                'name' => 'Balade',
                'slug' => 'balade',
                'description' => 'Promenade à cheval en extérieur',
                'min_participants' => 2,
                'max_participants' => 8,
                'duration_minutes' => 90,
                'equipment_required' => ['selle de randonnée', 'casque', 'bottes'],
                'skill_levels' => ['débutant', 'intermédiaire'],
                'base_price' => 35.00
            ],
            [
                'name' => 'Voltige',
                'slug' => 'voltige',
                'description' => 'Gymnastique à cheval',
                'min_participants' => 1,
                'max_participants' => 3,
                'duration_minutes' => 45,
                'equipment_required' => ['surfaix', 'poignées', 'tapis de protection'],
                'skill_levels' => ['débutant', 'intermédiaire', 'avancé'],
                'base_price' => 40.00
            ]
        ];

        foreach ($disciplinesEquestrian as $discipline) {
            Discipline::create(array_merge($discipline, ['activity_type_id' => $equestrian->id]));
        }

        // Disciplines aquatiques
        $disciplinesSwimming = [
            [
                'name' => 'Natation Sportive',
                'slug' => 'natation-sportive',
                'description' => 'Natation technique et performance',
                'min_participants' => 8,
                'max_participants' => 16,
                'duration_minutes' => 60,
                'equipment_required' => ['lunettes', 'bonnet', 'chronomètre'],
                'skill_levels' => ['débutant', 'intermédiaire', 'avancé', 'expert'],
                'base_price' => 25.00
            ],
            [
                'name' => 'Aquagym',
                'slug' => 'aquagym',
                'description' => 'Gymnastique dans l\'eau',
                'min_participants' => 12,
                'max_participants' => 20,
                'duration_minutes' => 45,
                'equipment_required' => ['frites', 'planches', 'haltères aquatiques'],
                'skill_levels' => ['débutant', 'intermédiaire'],
                'base_price' => 20.00
            ],
            [
                'name' => 'Aquabike',
                'slug' => 'aquabike',
                'description' => 'Cycling dans l\'eau',
                'min_participants' => 8,
                'max_participants' => 12,
                'duration_minutes' => 45,
                'equipment_required' => ['vélos aquatiques', 'musique'],
                'skill_levels' => ['débutant', 'intermédiaire', 'avancé'],
                'base_price' => 30.00
            ],
            [
                'name' => 'Bébés Nageurs',
                'slug' => 'bebes-nageurs',
                'description' => 'Initiation à l\'eau pour les bébés',
                'min_participants' => 6,
                'max_participants' => 8,
                'duration_minutes' => 30,
                'equipment_required' => ['jouets aquatiques', 'matériel de sécurité'],
                'skill_levels' => ['débutant'],
                'base_price' => 35.00
            ]
        ];

        foreach ($disciplinesSwimming as $discipline) {
            Discipline::create(array_merge($discipline, ['activity_type_id' => $swimming->id]));
        }

        // Catégories de produits
        $categories = [
            [
                'name' => 'Snack & Restauration',
                'slug' => 'snack',
                'description' => 'Boissons, snacks et repas',
                'icon' => '🍔',
                'color' => '#FF6B6B'
            ],
            [
                'name' => 'Matériel Équestre',
                'slug' => 'equipment-equestrian',
                'description' => 'Équipements pour l\'équitation',
                'icon' => '🏇',
                'color' => '#8B4513'
            ],
            [
                'name' => 'Matériel Aquatique',
                'slug' => 'equipment-swimming',
                'description' => 'Équipements pour la natation',
                'icon' => '🏊‍♂️',
                'color' => '#0066CC'
            ],
            [
                'name' => 'Vêtements',
                'slug' => 'clothing',
                'description' => 'Vêtements et accessoires',
                'icon' => '👕',
                'color' => '#4ECDC4'
            ],
            [
                'name' => 'Services',
                'slug' => 'services',
                'description' => 'Services supplémentaires',
                'icon' => '⚙️',
                'color' => '#45B7D1'
            ]
        ];

        foreach ($categories as $category) {
            ProductCategory::create($category);
        }

        // Mettre à jour les clubs existants avec les types d'activités
        $clubs = Club::all();
        foreach ($clubs as $index => $club) {
            $activityType = $index % 2 === 0 ? $equestrian : $swimming;
            $club->update([
                'activity_type_id' => $activityType->id,
                'seasonal_variation' => $activityType->id === $equestrian->id ? 30.00 : 5.00,
                'weather_dependency' => $activityType->id === $equestrian->id
            ]);
        }

        $this->command->info('✅ Types d\'activités, installations, disciplines et catégories créés avec succès !');
    }
}