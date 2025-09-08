<?php

namespace Database\Factories;

use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class StudentFactory extends Factory
{
    protected $model = Student::class;

    public function definition()
    {
        $levels = ['debutant', 'intermediaire', 'avance', 'expert'];
        $disciplines = ['dressage', 'obstacle', 'cross', 'complet', 'western', 'endurance'];

        return [
            'user_id' => User::factory()->state(['role' => 'student']),
            'level' => $this->faker->randomElement($levels),
            'goals' => $this->faker->sentence(),
            'medical_info' => $this->faker->optional(0.3)->sentence(),
            'emergency_contacts' => [
                'name' => $this->faker->name(),
                'phone' => $this->faker->phoneNumber(),
                'relationship' => $this->faker->randomElement(['parent', 'spouse', 'friend', 'relative'])
            ],
            'preferred_disciplines' => $this->faker->randomElements($disciplines, $this->faker->numberBetween(1, 3)),
            'preferred_levels' => $this->faker->randomElements($levels, $this->faker->numberBetween(1, 2)),
            'preferred_formats' => $this->faker->randomElements(['individual', 'group'], 1),
            'location' => $this->faker->city(),
            'max_price' => $this->faker->numberBetween(30, 100),
            'max_distance' => $this->faker->numberBetween(5, 50),
            'notifications_enabled' => $this->faker->boolean(80)
        ];
    }

    /**
     * Student with existing user
     */
    public function forUser($user)
    {
        return $this->state(function (array $attributes) use ($user) {
            return [
                'user_id' => $user->id ?? $user,
            ];
        });
    }

    /**
     * Beginner student
     */
    public function beginner()
    {
        return $this->state(function (array $attributes) {
            return [
                'level' => 'debutant',
                'preferred_disciplines' => ['dressage'],
                'preferred_levels' => ['debutant'],
                'preferred_formats' => ['individual'],
                'location' => 'Paris',
                'max_price' => 50,
                'max_distance' => 10,
                'notifications_enabled' => true
            ];
        });
    }

    /**
     * Advanced student
     */
    public function advanced()
    {
        return $this->state(function (array $attributes) {
            return [
                'level' => 'expert',
                'preferred_disciplines' => ['dressage', 'obstacle', 'cross'],
                'preferred_levels' => ['avance', 'expert'],
                'preferred_formats' => ['individual', 'group'],
                'location' => 'Lyon',
                'max_price' => 100,
                'max_distance' => 30,
                'notifications_enabled' => true
            ];
        });
    }

    /**
     * Student with medical notes
     */
    public function withMedicalNotes($notes = null)
    {
        return $this->state(function (array $attributes) use ($notes) {
            return [
                'medical_info' => $notes ?? 'Allergie aux poils de chat. Porter un casque obligatoire.',
            ];
        });
    }

    /**
     * Student focused on dressage
     */
    public function dressageSpecialist()
    {
        return $this->state(function (array $attributes) {
            return [
                'level' => 'avance',
                'preferred_disciplines' => ['dressage'],
                'preferred_levels' => ['avance'],
                'preferred_formats' => ['individual'],
                'location' => 'Versailles',
                'max_price' => 80,
                'max_distance' => 20,
                'notifications_enabled' => true
            ];
        });
    }

    /**
     * Student focused on jumping
     */
    public function jumpingSpecialist()
    {
        return $this->state(function (array $attributes) {
            return [
                'level' => 'avance',
                'preferred_disciplines' => ['obstacle'],
                'preferred_levels' => ['avance'],
                'preferred_formats' => ['individual'],
                'location' => 'Chantilly',
                'max_price' => 90,
                'max_distance' => 25,
                'notifications_enabled' => true
            ];
        });
    }

    /**
     * Complete equestrian student
     */
    public function completeRider()
    {
        return $this->state(function (array $attributes) {
            return [
                'level' => 'expert',
                'preferred_disciplines' => ['dressage', 'obstacle', 'cross', 'complet'],
                'preferred_levels' => ['avance', 'expert'],
                'preferred_formats' => ['individual', 'group'],
                'location' => 'Fontainebleau',
                'max_price' => 120,
                'max_distance' => 50,
                'notifications_enabled' => true
            ];
        });
    }
}
