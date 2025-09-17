<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ClubSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $clubs = [
            [
                'name' => 'Club Équestre de Bruxelles',
                'email' => 'contact@club-bruxelles.be',
                'phone' => '+32 2 123 45 67',
                'address' => 'Rue de l\'Équitation 123',
                'city' => 'Bruxelles',
                'postal_code' => '1000',
                'country' => 'Belgium',
                'description' => 'Club équestre moderne avec installations de qualité',
                'website' => 'https://club-bruxelles.be',
                'facilities' => json_encode(['manège couvert', 'carrière extérieure', 'écuries', 'club house']),
                'services' => json_encode(['cours particuliers', 'cours collectifs', 'stages', 'compétitions']),
                'is_active' => true,
            ],
            [
                'name' => 'Centre Équestre d\'Anvers',
                'email' => 'info@centre-anvers.be',
                'phone' => '+32 3 234 56 78',
                'address' => 'Chemin des Chevaux 456',
                'city' => 'Anvers',
                'postal_code' => '2000',
                'country' => 'Belgium',
                'description' => 'Centre équestre familial avec école d\'équitation',
                'website' => 'https://centre-anvers.be',
                'facilities' => json_encode(['manège', 'carrière', 'écuries', 'paddocks']),
                'services' => json_encode(['cours débutants', 'cours avancés', 'balades', 'pension']),
                'is_active' => true,
            ],
            [
                'name' => 'Écuries de Gand',
                'email' => 'admin@ecuries-gand.be',
                'phone' => '+32 9 345 67 89',
                'address' => 'Avenue des Écuries 789',
                'city' => 'Gand',
                'postal_code' => '9000',
                'country' => 'Belgium',
                'description' => 'Écuries spécialisées dans le dressage et le saut d\'obstacles',
                'website' => 'https://ecuries-gand.be',
                'facilities' => json_encode(['manège olympique', 'carrière de dressage', 'parcours d\'obstacles', 'écuries de luxe']),
                'services' => json_encode(['dressage', 'saut d\'obstacles', 'compétitions', 'formation']),
                'is_active' => true,
            ],
        ];

        foreach ($clubs as $club) {
            \App\Models\Club::create($club);
        }
    }
}
