<?php

namespace Database\Factories;

use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

class LocationFactory extends Factory
{
    protected $model = Location::class;

    public function definition()
    {
        $belgianCities = [
            ['name' => 'Bruxelles', 'postal_code' => '1000', 'lat' => 50.8503, 'lng' => 4.3517],
            ['name' => 'Anvers', 'postal_code' => '2000', 'lat' => 51.2194, 'lng' => 4.4025],
            ['name' => 'Gand', 'postal_code' => '9000', 'lat' => 51.0543, 'lng' => 3.7174],
            ['name' => 'Charleroi', 'postal_code' => '6000', 'lat' => 50.4108, 'lng' => 4.4446],
            ['name' => 'Liège', 'postal_code' => '4000', 'lat' => 50.6326, 'lng' => 5.5797],
            ['name' => 'Bruges', 'postal_code' => '8000', 'lat' => 51.2093, 'lng' => 3.2247],
            ['name' => 'Namur', 'postal_code' => '5000', 'lat' => 50.4674, 'lng' => 4.8720],
            ['name' => 'Louvain', 'postal_code' => '3000', 'lat' => 50.8798, 'lng' => 4.7005],
        ];

        $city = $this->faker->randomElement($belgianCities);

        $facilities = [
            'carrière extérieure',
            'manège couvert',
            'rond de longe',
            'paddock',
            'parking',
            'vestiaires',
            'club house',
            'écuries',
            'sellerie',
            'douches chevaux',
            'aire de pansage',
            'stockage foin',
            'terrain de cross',
            'carrière de dressage',
            'carrière d\'obstacle'
        ];

        return [
            'name' => 'Centre Équestre ' . $this->faker->lastName,
            'address' => $this->faker->streetAddress,
            'city' => $city['name'],
            'postal_code' => $city['postal_code'],
            'country' => 'Belgique',
            'latitude' => $city['lat'] + $this->faker->randomFloat(4, -0.1, 0.1),
            'longitude' => $city['lng'] + $this->faker->randomFloat(4, -0.1, 0.1),
            'facilities' => $this->faker->randomElements($facilities, $this->faker->numberBetween(3, 8)),
        ];
    }

    /**
     * Location in Brussels
     */
    public function inBrussels()
    {
        return $this->state(function (array $attributes) {
            return [
                'city' => 'Bruxelles',
                'postal_code' => '1000',
                'latitude' => 50.8503 + $this->faker->randomFloat(4, -0.05, 0.05),
                'longitude' => 4.3517 + $this->faker->randomFloat(4, -0.05, 0.05),
            ];
        });
    }

    /**
     * Location with specific facilities
     */
    public function withFacilities(array $facilities)
    {
        return $this->state(function (array $attributes) use ($facilities) {
            return [
                'facilities' => $facilities,
            ];
        });
    }

    /**
     * Premium location with all facilities
     */
    public function premium()
    {
        return $this->state(function (array $attributes) {
            return [
                'name' => 'Centre Équestre Premium',
                'facilities' => [
                    'carrière extérieure',
                    'manège couvert',
                    'rond de longe',
                    'paddock',
                    'parking',
                    'vestiaires',
                    'club house',
                    'écuries',
                    'sellerie',
                    'douches chevaux',
                    'aire de pansage',
                    'stockage foin',
                    'terrain de cross',
                    'carrière de dressage',
                    'carrière d\'obstacle',
                    'restaurant',
                    'boutique',
                    'manège olympique'
                ],
            ];
        });
    }

    /**
     * Basic location with minimal facilities
     */
    public function basic()
    {
        return $this->state(function (array $attributes) {
            return [
                'name' => 'Centre Équestre Basic',
                'facilities' => [
                    'carrière extérieure',
                    'écuries',
                    'parking'
                ],
            ];
        });
    }

    /**
     * Location without GPS coordinates
     */
    public function withoutCoordinates()
    {
        return $this->state(function (array $attributes) {
            return [
                'latitude' => null,
                'longitude' => null,
            ];
        });
    }
}
