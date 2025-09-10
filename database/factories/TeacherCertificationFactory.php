<?php

namespace Database\Factories;

use App\Models\TeacherCertification;
use App\Models\Teacher;
use App\Models\Certification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TeacherCertification>
 */
class TeacherCertificationFactory extends Factory
{
    protected $model = TeacherCertification::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $obtainedDate = $this->faker->dateTimeBetween('-5 years', 'now');
        $validityYears = $this->faker->randomElement([1, 2, 3, 5, null]);
        $expiryDate = $validityYears ? 
            (clone $obtainedDate)->modify("+{$validityYears} years") : 
            null;

        return [
            'teacher_id' => Teacher::factory(),
            'certification_id' => Certification::factory(),
            'obtained_date' => $obtainedDate,
            'expiry_date' => $expiryDate,
            'certificate_number' => 'CERT-' . $this->faker->unique()->numerify('########'),
            'issuing_authority' => $this->faker->randomElement([
                'FFE', 'Fédération Française d\'Équitation', 'Ministère des Sports', 'Organisme Privé'
            ]),
            'certificate_file' => $this->faker->optional(0.7)->filePath(),
            'notes' => $this->faker->optional(0.3)->paragraph(),
            'is_valid' => $this->faker->boolean(85), // 85% valid
            'renewal_required' => $validityYears ? $this->faker->boolean(70) : false,
            'renewal_reminder_date' => $expiryDate ? 
                (clone $expiryDate)->modify('-30 days') : 
                null,
            'is_verified' => $this->faker->boolean(80), // 80% verified
            'verified_by' => $this->faker->optional(0.8)->randomElement(User::where('role', 'admin')->pluck('id')->toArray()),
            'verified_at' => $this->faker->optional(0.8)->dateTimeBetween($obtainedDate, 'now'),
        ];
    }

    /**
     * Indicate that the certification is valid.
     */
    public function valid(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_valid' => true,
        ]);
    }

    /**
     * Indicate that the certification is invalid.
     */
    public function invalid(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_valid' => false,
        ]);
    }

    /**
     * Indicate that the certification is verified.
     */
    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_verified' => true,
            'verified_by' => User::factory()->create(['role' => 'admin'])->id,
            'verified_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ]);
    }

    /**
     * Indicate that the certification is not verified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_verified' => false,
            'verified_by' => null,
            'verified_at' => null,
        ]);
    }

    /**
     * Create an expired certification.
     */
    public function expired(): static
    {
        $obtainedDate = $this->faker->dateTimeBetween('-5 years', '-2 years');
        
        return $this->state(fn (array $attributes) => [
            'obtained_date' => $obtainedDate,
            'expiry_date' => (clone $obtainedDate)->modify('+1 year'),
            'is_valid' => false,
        ]);
    }

    /**
     * Create an expiring soon certification.
     */
    public function expiringSoon(): static
    {
        $expiryDate = $this->faker->dateTimeBetween('now', '+30 days');
        $obtainedDate = (clone $expiryDate)->modify('-3 years');
        
        return $this->state(fn (array $attributes) => [
            'obtained_date' => $obtainedDate,
            'expiry_date' => $expiryDate,
            'renewal_reminder_date' => (clone $expiryDate)->modify('-30 days'),
            'is_valid' => true,
        ]);
    }

    /**
     * Create a permanent certification.
     */
    public function permanent(): static
    {
        return $this->state(fn (array $attributes) => [
            'validity_years' => null,
            'expiry_date' => null,
            'renewal_required' => false,
            'renewal_reminder_date' => null,
        ]);
    }

    /**
     * Create a safety certification.
     */
    public function safety(): static
    {
        return $this->state(fn (array $attributes) => [
            'certification_id' => Certification::factory()->safety()->create()->id,
            'certificate_number' => 'SAFETY-' . $this->faker->unique()->numerify('########'),
            'issuing_authority' => 'FFE',
            'validity_years' => 3,
        ]);
    }

    /**
     * Create a teaching certification.
     */
    public function teaching(): static
    {
        return $this->state(fn (array $attributes) => [
            'certification_id' => Certification::factory()->teaching()->create()->id,
            'certificate_number' => 'TEACH-' . $this->faker->unique()->numerify('########'),
            'issuing_authority' => 'Fédération Française d\'Équitation',
            'validity_years' => 5,
        ]);
    }
}
