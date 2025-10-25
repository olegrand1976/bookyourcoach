<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $locations = [
            [
                'name' => 'Centre Équestre de Bruxelles',
                'address' => 'Avenue des Sports 123',
                'city' => 'Uccle',
                'postal_code' => '1180',
                'country' => 'Belgique',
                'latitude' => 50.8203,
                'longitude' => 4.3517,
                'facilities' => ['manège couvert', 'carrière extérieure', 'paddocks', 'écuries', 'parking'],
            ],
            [
                'name' => 'Haras de la Forêt',
                'address' => 'Chemin de la Forêt 45',
                'city' => 'Waterloo',
                'postal_code' => '1410',
                'country' => 'Belgique',
                'latitude' => 50.7158,
                'longitude' => 4.3996,
                'facilities' => ['manège couvert', '2 carrières extérieures', 'cross', 'écuries', 'club house', 'parking'],
            ],
            [
                'name' => 'Écuries du Château',
                'address' => 'Rue du Château 78',
                'city' => 'Wavre',
                'postal_code' => '1300',
                'country' => 'Belgique',
                'latitude' => 50.7167,
                'longitude' => 4.6181,
                'facilities' => ['manège couvert', 'carrière extérieure', 'paddocks', 'écuries', 'sellerie'],
            ],
            [
                'name' => 'Centre Équestre des Ardennes',
                'address' => 'Route de la Nature 12',
                'city' => 'Marche-en-Famenne',
                'postal_code' => '6900',
                'country' => 'Belgique',
                'latitude' => 50.2275,
                'longitude' => 5.3444,
                'facilities' => ['manège couvert', 'carrière extérieure', 'parcours cross', 'écuries', 'pension', 'restaurant'],
            ],
            [
                'name' => 'Club Hippique de Liège',
                'address' => 'Boulevard des Chevaux 89',
                'city' => 'Liège',
                'postal_code' => '4000',
                'country' => 'Belgique',
                'latitude' => 50.6292,
                'longitude' => 5.5797,
                'facilities' => ['2 manèges couverts', 'carrière extérieure', 'écuries', 'parking', 'vestiaires'],
            ],
            [
                'name' => 'Domaine Équestre de Gand',
                'address' => 'Landweg 156',
                'city' => 'Gent',
                'postal_code' => '9000',
                'country' => 'Belgique',
                'latitude' => 51.0543,
                'longitude' => 3.7174,
                'facilities' => ['manège couvert', 'carrière extérieure', 'paddocks', 'écuries', 'café'],
            ],
            [
                'name' => 'Ranch Western Brabant',
                'address' => 'Westernlaan 23',
                'city' => 'Leuven',
                'postal_code' => '3000',
                'country' => 'Belgique',
                'latitude' => 50.8798,
                'longitude' => 4.7005,
                'facilities' => ['manège western', 'carrière extérieure', 'ranch', 'écuries', 'saloon', 'parking'],
            ],
            [
                'name' => 'Centre d\'Équitation Naturelle',
                'address' => 'Sentier Vert 67',
                'city' => 'Namur',
                'postal_code' => '5000',
                'country' => 'Belgique',
                'latitude' => 50.4669,
                'longitude' => 4.8667,
                'facilities' => ['rond de longe', 'carrières', 'paddocks paradise', 'écuries actives', 'parking'],
            ],
            [
                'name' => 'Poney Club des Enfants',
                'address' => 'Rue des Poneys 34',
                'city' => 'Woluwe-Saint-Lambert',
                'postal_code' => '1200',
                'country' => 'Belgique',
                'latitude' => 50.8436,
                'longitude' => 4.4198,
                'facilities' => ['petit manège', 'carrière poneys', 'paddocks', 'écuries poneys', 'aire de jeux', 'parking'],
            ],
            [
                'name' => 'Académie Équestre Elite',
                'address' => 'Avenue de l\'Excellence 101',
                'city' => 'Ixelles',
                'postal_code' => '1050',
                'country' => 'Belgique',
                'latitude' => 50.8333,
                'longitude' => 4.3667,
                'facilities' => ['manège olympique', 'carrière dressage', 'carrière obstacles', 'écuries VIP', 'spa chevaux', 'restaurant', 'parking'],
            ]
        ];

        foreach ($locations as $location) {
            Location::create($location);
        }
    }
}
