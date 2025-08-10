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
        $experienceLevels = ['débutant', 'intermédiaire', 'confirmé', 'expert'];
        $disciplines = ['dressage', 'obstacle', 'cross', 'complet', 'western', 'endurance'];

        return [
            'user_id' => User::factory()->state(['role' => 'student']),
            'experience_level' => $this->faker->randomElement($experienceLevels),
            'preferred_disciplines' => $this->faker->randomElements($disciplines, $this->faker->numberBetween(1, 3)),
            'emergency_contact_name' => $this->faker->name(),
            'emergency_contact_phone' => $this->faker->phoneNumber(),
            'medical_notes' => $this->faker->optional(0.3)->sentence(),
            'active' => $this->faker->boolean(90), // 90% chance d'être actif
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
                'experience_level' => 'débutant',
                'preferred_disciplines' => ['dressage'],
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
                'experience_level' => 'expert',
                'preferred_disciplines' => ['dressage', 'obstacle', 'cross'],
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
                'medical_notes' => $notes ?? 'Allergie aux poils de chat. Porter un casque obligatoire.',
            ];
        });
    }

    /**
     * Inactive student
     */
    public function inactive()
    {
        return $this->state(function (array $attributes) {
            return [
                'active' => false,
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
                'experience_level' => 'confirmé',
                'preferred_disciplines' => ['dressage'],
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
                'experience_level' => 'confirmé',
                'preferred_disciplines' => ['obstacle'],
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
                'experience_level' => 'expert',
                'preferred_disciplines' => ['dressage', 'obstacle', 'cross', 'complet'],
            ];
        });
    }
}
