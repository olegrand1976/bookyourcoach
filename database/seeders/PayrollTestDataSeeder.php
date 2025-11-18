<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Teacher;
use App\Models\Subscription;
use App\Models\SubscriptionInstance;
use App\Models\Club;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

/**
 * Seeder pour créer les données de test du système de paie
 * 
 * Scénario de test : Novembre 2025
 * - prof_alpha : 3 abonnements (2 Type 1, 1 Type 2)
 * - prof_beta : 1 abonnement Type 1
 * - 2 abonnements hors période (Octobre) pour vérifier le filtre
 */
class PayrollTestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌱 Création des données de test pour le système de paie...');

        // Créer ou récupérer un club
        $club = Club::firstOrCreate(
            ['email' => 'test-payroll@club.com'],
            [
                'name' => 'Club Test Paie',
                'phone' => '+33123456789',
                'address' => '123 Rue Test',
                'city' => 'Paris',
                'postal_code' => '75001',
                'country' => 'France',
            ]
        );

        // Créer prof_alpha
        $userAlpha = User::firstOrCreate(
            ['email' => 'prof_alpha@test.com'],
            [
                'name' => 'Prof Alpha',
                'first_name' => 'Alpha',
                'last_name' => 'Teacher',
                'password' => Hash::make('password'),
                'role' => 'teacher',
            ]
        );

        $teacherAlpha = Teacher::firstOrCreate(
            ['user_id' => $userAlpha->id],
            [
                'hourly_rate' => 50.00,
                'is_available' => true,
            ]
        );

        // Créer prof_beta
        $userBeta = User::firstOrCreate(
            ['email' => 'prof_beta@test.com'],
            [
                'name' => 'Prof Beta',
                'first_name' => 'Beta',
                'last_name' => 'Teacher',
                'password' => Hash::make('password'),
                'role' => 'teacher',
            ]
        );

        $teacherBeta = Teacher::firstOrCreate(
            ['user_id' => $userBeta->id],
            [
                'hourly_rate' => 50.00,
                'is_available' => true,
            ]
        );

        // Créer un abonnement modèle (pour les SubscriptionInstance)
        // Utiliser le premier abonnement existant ou créer un nouveau
        $subscription = Subscription::where('club_id', $club->id)->first();
        
        if (!$subscription) {
            // Créer un abonnement minimal si aucun n'existe
            $subscription = Subscription::create([
                'club_id' => $club->id,
                'subscription_number' => 'TEST-001',
            ]);
        }

        // Données de test selon le scénario
        $testData = [
            // Novembre 2025 - prof_alpha
            [
                'id' => 1,
                'teacher_id' => $teacherAlpha->id,
                'montant' => 100.00,
                'date_paiement' => '2025-11-05',
                'est_legacy' => false, // Type 1
                'status' => 'active',
            ],
            [
                'id' => 2,
                'teacher_id' => $teacherAlpha->id,
                'montant' => 50.00,
                'date_paiement' => '2025-11-10',
                'est_legacy' => false, // Type 1
                'status' => 'active',
            ],
            [
                'id' => 3,
                'teacher_id' => $teacherAlpha->id,
                'montant' => 80.00,
                'date_paiement' => '2025-11-15',
                'est_legacy' => true, // Type 2
                'status' => 'active',
            ],
            // Novembre 2025 - prof_beta
            [
                'id' => 4,
                'teacher_id' => $teacherBeta->id,
                'montant' => 100.00,
                'date_paiement' => '2025-11-20',
                'est_legacy' => false, // Type 1
                'status' => 'active',
            ],
            // Octobre 2025 - Hors période (doivent être ignorés)
            [
                'id' => 5,
                'teacher_id' => $teacherAlpha->id,
                'montant' => 1000.00,
                'date_paiement' => '2025-10-30',
                'est_legacy' => false, // Type 1
                'status' => 'active',
            ],
            [
                'id' => 6,
                'teacher_id' => $teacherBeta->id,
                'montant' => 200.00,
                'date_paiement' => '2025-10-28',
                'est_legacy' => true, // Type 2
                'status' => 'active',
            ],
        ];

        foreach ($testData as $data) {
            SubscriptionInstance::updateOrCreate(
                ['id' => $data['id']],
                array_merge($data, [
                    'subscription_id' => $subscription->id,
                    'started_at' => $data['date_paiement'],
                    'expires_at' => Carbon::parse($data['date_paiement'])->addMonths(1),
                    'lessons_used' => 0,
                ])
            );
        }

        $this->command->info('✅ Données de test créées avec succès !');
        $this->command->info("   - prof_alpha (ID: {$teacherAlpha->id}) : 3 abonnements Novembre + 1 Octobre");
        $this->command->info("   - prof_beta (ID: {$teacherBeta->id}) : 1 abonnement Novembre + 1 Octobre");
    }
}
