<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUsersTestSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Créer quelques utilisateurs de test avec des codes postaux
        $testUsers = [
            [
                'first_name' => 'Alice',
                'last_name' => 'Martin',
                'name' => 'Alice Martin',
                'email' => 'alice.martin@example.com',
                'password' => Hash::make('password'),
                'role' => 'student',
                'phone' => '+32 2 123 45 67',
                'street' => 'Avenue Louise',
                'street_number' => '123',
                'postal_code' => '1000',
                'city' => 'Bruxelles',
                'country' => 'Belgium',
                'birth_date' => '1995-03-15',
                'is_active' => true,
                'status' => 'active',
            ],
            [
                'first_name' => 'Pierre',
                'last_name' => 'Dubois',
                'name' => 'Pierre Dubois',
                'email' => 'pierre.dubois@example.com',
                'password' => Hash::make('password'),
                'role' => 'teacher',
                'phone' => '+32 2 234 56 78',
                'street' => 'Rue de la Loi',
                'street_number' => '45',
                'postal_code' => '1040',
                'city' => 'Etterbeek',
                'country' => 'Belgium',
                'birth_date' => '1985-07-22',
                'is_active' => true,
                'status' => 'active',
            ],
            [
                'first_name' => 'Sophie',
                'last_name' => 'Leroy',
                'name' => 'Sophie Leroy',
                'email' => 'sophie.leroy@example.com',
                'password' => Hash::make('password'),
                'role' => 'student',
                'phone' => '+32 2 345 67 89',
                'street' => 'Boulevard Anspach',
                'street_number' => '78',
                'postal_code' => '1000',
                'city' => 'Bruxelles',
                'country' => 'Belgium',
                'birth_date' => '1992-11-08',
                'is_active' => true,
                'status' => 'active',
            ],
            [
                'first_name' => 'Marc',
                'last_name' => 'Janssens',
                'name' => 'Marc Janssens',
                'email' => 'marc.janssens@example.com',
                'password' => Hash::make('password'),
                'role' => 'teacher',
                'phone' => '+32 2 456 78 90',
                'street' => 'Chaussée de Waterloo',
                'street_number' => '156',
                'postal_code' => '1180',
                'city' => 'Uccle',
                'country' => 'Belgium',
                'birth_date' => '1980-05-12',
                'is_active' => true,
                'status' => 'active',
            ],
            [
                'first_name' => 'Emma',
                'last_name' => 'Van Der Berg',
                'name' => 'Emma Van Der Berg',
                'email' => 'emma.vandenberg@example.com',
                'password' => Hash::make('password'),
                'role' => 'student',
                'phone' => '+32 2 567 89 01',
                'street' => 'Rue Neuve',
                'street_number' => '89',
                'postal_code' => '1000',
                'city' => 'Bruxelles',
                'country' => 'Belgium',
                'birth_date' => '1998-09-25',
                'is_active' => true,
                'status' => 'active',
            ]
        ];

        foreach ($testUsers as $userData) {
            // Vérifier si l'utilisateur existe déjà
            if (!User::where('email', $userData['email'])->exists()) {
                User::create($userData);
                $this->command->info('Utilisateur créé: ' . $userData['name']);
            } else {
                $this->command->info('Utilisateur existe déjà: ' . $userData['name']);
            }
        }

        $this->command->info('Seeder AdminUsersTest terminé avec succès.');
    }
}