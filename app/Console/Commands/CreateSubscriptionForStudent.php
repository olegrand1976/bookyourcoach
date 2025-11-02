<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\SubscriptionTemplate;
use App\Models\Subscription;
use App\Models\SubscriptionInstance;
use Carbon\Carbon;

class CreateSubscriptionForStudent extends Command
{
    protected $signature = 'student:create-subscription {email}';
    protected $description = 'Crée un abonnement pour un élève s\'il n\'en a pas';

    public function handle()
    {
        $email = $this->argument('email');

        $user = User::where('email', $email)->first();
        
        if (!$user || !$user->student) {
            $this->error("Élève non trouvé : {$email}");
            return 1;
        }

        $student = $user->student;
        $this->info("✅ Élève trouvé : {$user->name} (ID: {$student->id})");

        // Vérifier s'il a déjà un abonnement actif
        $existingSubscription = \App\Models\SubscriptionInstance::whereHas('students', function($q) use ($student) {
            $q->where('students.id', $student->id);
        })->where('status', 'active')->first();

        if ($existingSubscription) {
            $this->info("ℹ️  L'élève a déjà un abonnement actif : {$existingSubscription->subscription->subscription_number}");
            return 0;
        }

        // Récupérer le club de l'élève
        $clubId = $student->club_id;
        if (!$clubId) {
            $this->error("Aucun club associé à cet élève");
            return 1;
        }

        // Récupérer un modèle d'abonnement actif du club
        $template = SubscriptionTemplate::where('club_id', $clubId)
            ->where('is_active', true)
            ->first();

        if (!$template) {
            $this->warn("⚠️  Aucun modèle d'abonnement trouvé pour ce club, création d'un modèle par défaut...");
            
            $template = SubscriptionTemplate::create([
                'club_id' => $clubId,
                'model_number' => 'MOD-DEF-' . rand(1000, 9999),
                'name' => 'Abonnement Standard',
                'description' => 'Abonnement standard créé automatiquement',
                'total_lessons' => 10,
                'free_lessons' => 0,
                'price' => 250.00,
                'validity_months' => 3,
                'validity_value' => 3,
                'validity_unit' => 'months',
                'is_active' => true,
            ]);

            $this->info("✅ Modèle d'abonnement créé : {$template->model_number}");
        }

        // Créer l'abonnement (le numéro sera généré automatiquement par le modèle via boot())
        // Utiliser une transaction pour éviter les conflits de numérotation
        try {
            \DB::beginTransaction();
            
            // Récupérer le dernier numéro pour ce club ce mois-ci
            $now = \Carbon\Carbon::now();
            $yearMonth = $now->format('ym');
            $lastSubscription = \App\Models\Subscription::where('club_id', $clubId)
                ->where('subscription_number', 'like', $yearMonth . '-%')
                ->orderBy('subscription_number', 'desc')
                ->lockForUpdate()
                ->first();
            
            $increment = 1;
            if ($lastSubscription && $lastSubscription->subscription_number) {
                $parts = explode('-', $lastSubscription->subscription_number);
                if (count($parts) === 2 && is_numeric($parts[1])) {
                    $increment = (int) $parts[1] + 1;
                }
            }
            
            $subscriptionNumber = sprintf('%s-%03d', $yearMonth, $increment);
            
            // Vérifier qu'il n'existe pas déjà
            while (\App\Models\Subscription::where('subscription_number', $subscriptionNumber)->exists()) {
                $increment++;
                $subscriptionNumber = sprintf('%s-%03d', $yearMonth, $increment);
            }
            
            $subscription = \App\Models\Subscription::create([
                'club_id' => $clubId,
                'subscription_template_id' => $template->id,
                'subscription_number' => $subscriptionNumber
            ]);
            
            \DB::commit();
        } catch (\Exception $e) {
            \DB::rollBack();
            $this->error("Erreur lors de la création de l'abonnement : " . $e->getMessage());
            return 1;
        }

        // Créer l'instance d'abonnement
        $instance = SubscriptionInstance::create([
            'subscription_id' => $subscription->id,
            'lessons_used' => 0,
            'started_at' => Carbon::now(),
            'expires_at' => null,
            'status' => 'active'
        ]);

        // Calculer la date d'expiration
        $instance->calculateExpiresAt();
        $instance->save();

        // Attacher l'élève
        $instance->students()->attach($student->id);

        $this->info("✅ Abonnement créé avec succès :");
        $this->line("   Numéro : {$subscription->subscription_number}");
        $this->line("   Modèle : {$template->model_number}");
        $this->line("   Cours inclus : {$template->total_lessons}");
        $this->line("   Prix : {$template->price} €");
        $this->line("   Expire le : " . ($instance->expires_at ? $instance->expires_at->format('d/m/Y') : 'N/A'));

        return 0;
    }
}

