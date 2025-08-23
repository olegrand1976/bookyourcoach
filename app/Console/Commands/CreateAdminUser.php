<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Profile;
use Illuminate\Support\Facades\Hash;

class CreateAdminUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:create 
                            {--name= : Le nom complet de l\'administrateur}
                            {--email= : L\'adresse email de l\'administrateur}
                            {--password= : Le mot de passe (optionnel, sera généré automatiquement)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Créer un nouveau compte administrateur';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔧 Création d\'un nouveau compte administrateur...');

        // Récupérer les paramètres ou demander interactivement
        $name = $this->option('name') ?: $this->ask('Nom complet de l\'administrateur', 'Administrateur Supplémentaire');
        $email = $this->option('email') ?: $this->ask('Adresse email', 'admin2@bookyourcoach.com');
        $password = $this->option('password') ?: $this->generateSecurePassword();

        // Vérifier si l'utilisateur existe déjà
        if (User::where('email', $email)->exists()) {
            $this->error("❌ Un utilisateur avec l'email {$email} existe déjà !");
            return 1;
        }

        try {
            // Créer l'utilisateur admin
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'role' => 'admin',
                'is_active' => true,
                'email_verified_at' => now(),
            ]);

            // Créer le profil associé
            Profile::create([
                'user_id' => $user->id,
                'first_name' => explode(' ', $name)[0] ?? $name,
                'last_name' => explode(' ', $name, 2)[1] ?? '',
                'phone' => '+32 2 123 45 67',
                'bio' => 'Administrateur de la plateforme BookYourCoach',
                'preferences' => [
                    'notifications' => true,
                    'email_updates' => true,
                    'admin_dashboard' => true
                ]
            ]);

            // Afficher les informations de connexion
            $this->newLine();
            $this->info('✅ Compte administrateur créé avec succès !');
            $this->newLine();

            $this->line('📋 <fg=cyan>Informations de connexion :</fg=cyan>');
            $this->line('┌─────────────────────────────────────────┐');
            $this->line('│ <fg=yellow>Email:</fg=yellow>    ' . str_pad($email, 30) . ' │');
            $this->line('│ <fg=yellow>Mot de passe:</fg=yellow> ' . str_pad($password, 22) . ' │');
            $this->line('│ <fg=yellow>Rôle:</fg=yellow>     ' . str_pad('admin', 30) . ' │');
            $this->line('└─────────────────────────────────────────┘');
            $this->newLine();

            $this->line('🌐 <fg=green>URLs d\'accès :</fg=green>');
            $this->line('• Frontend: http://localhost:3000/login');
            $this->line('• API Backend: http://localhost:8081');
            $this->line('• Admin Dashboard: http://localhost:3000/admin');
            $this->newLine();

            $this->warn('⚠️  Veuillez noter ces informations en lieu sûr !');

            return 0;
        } catch (\Exception $e) {
            $this->error("❌ Erreur lors de la création du compte : " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Générer un mot de passe sécurisé
     */
    private function generateSecurePassword(): string
    {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        $password = '';

        for ($i = 0; $i < 12; $i++) {
            $password .= $characters[random_int(0, strlen($characters) - 1)];
        }

        return $password;
    }
}
