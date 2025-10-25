<?php

namespace Database\Factories;

use App\Models\StudentMedicalDocument;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

class StudentMedicalDocumentFactory extends Factory
{
    protected $model = StudentMedicalDocument::class;

    public function definition()
    {
        $documentTypes = ['certificat_medical', 'assurance', 'autorisation_parentale', 'autre'];
        $renewalFrequencies = ['yearly', 'monthly', 'quarterly'];

        return [
            'student_id' => Student::factory(),
            'document_type' => $this->faker->randomElement($documentTypes),
            'file_path' => 'medical_documents/' . $this->faker->uuid() . '.pdf',
            'file_name' => $this->faker->word() . '.pdf',
            'expiry_date' => $this->faker->optional(0.8)->dateTimeBetween('now', '+2 years'),
            'renewal_frequency' => $this->faker->optional(0.7)->randomElement($renewalFrequencies),
            'notes' => $this->faker->optional(0.5)->sentence(),
            'is_active' => $this->faker->boolean(90)
        ];
    }

    /**
     * Certificat médical
     */
    public function medicalCertificate()
    {
        return $this->state(function (array $attributes) {
            return [
                'document_type' => 'certificat_medical',
                'file_name' => 'certificat_medical.pdf',
                'expiry_date' => now()->addYear(),
                'renewal_frequency' => 'yearly',
                'notes' => 'Certificat médical de non contre-indication à la pratique de l\'équitation'
            ];
        });
    }

    /**
     * Assurance
     */
    public function insurance()
    {
        return $this->state(function (array $attributes) {
            return [
                'document_type' => 'assurance',
                'file_name' => 'assurance.pdf',
                'expiry_date' => now()->addYear(),
                'renewal_frequency' => 'yearly',
                'notes' => 'Assurance responsabilité civile et individuelle accident'
            ];
        });
    }

    /**
     * Autorisation parentale
     */
    public function parentalAuthorization()
    {
        return $this->state(function (array $attributes) {
            return [
                'document_type' => 'autorisation_parentale',
                'file_name' => 'autorisation_parentale.pdf',
                'expiry_date' => null,
                'renewal_frequency' => null,
                'notes' => 'Autorisation parentale pour la pratique de l\'équitation'
            ];
        });
    }

    /**
     * Document expiré
     */
    public function expired()
    {
        return $this->state(function (array $attributes) {
            return [
                'expiry_date' => now()->subMonth(),
                'is_active' => false
            ];
        });
    }

    /**
     * Document à renouveler bientôt
     */
    public function expiringSoon()
    {
        return $this->state(function (array $attributes) {
            return [
                'expiry_date' => now()->addMonth(),
                'is_active' => true
            ];
        });
    }
}
