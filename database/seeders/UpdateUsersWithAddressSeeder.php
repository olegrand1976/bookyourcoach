<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;

class UpdateUsersWithAddressSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Mettre à jour tous les utilisateurs qui n'ont pas de code postal
        $usersWithoutPostalCode = User::whereNull('postal_code')
            ->orWhere('postal_code', '')
            ->get();

        foreach ($usersWithoutPostalCode as $user) {
            $user->update([
                'first_name' => $user->first_name ?: fake()->firstName(),
                'last_name' => $user->last_name ?: fake()->lastName(),
                'phone' => $user->phone ?: fake()->phoneNumber(),
                'street' => $user->street ?: fake()->streetName(),
                'street_number' => $user->street_number ?: fake()->buildingNumber(),
                'postal_code' => fake()->postcode(),
                'city' => $user->city ?: fake()->city(),
                'country' => $user->country ?: fake()->randomElement(['Belgium', 'France', 'Netherlands', 'Germany', 'Luxembourg']),
                'birth_date' => $user->birth_date ?: fake()->dateTimeBetween('-60 years', '-18 years')->format('Y-m-d'),
            ]);
        }

        $this->command->info('Updated ' . $usersWithoutPostalCode->count() . ' users with address information.');
    }
}