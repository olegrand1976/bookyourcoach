<?php

namespace App\Console\Commands;

use App\Models\Teacher;
use App\Models\Club;
use Illuminate\Console\Command;

class AssignTeacherToClub extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'teacher:assign-to-club 
                            {teacher_id : ID de l\'enseignant}
                            {club_id : ID du club}
                            {--hourly-rate= : Tarif horaire spécifique au club}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assigne un enseignant à un club';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $teacherId = $this->argument('teacher_id');
        $clubId = $this->argument('club_id');
        $hourlyRate = $this->option('hourly-rate');

        // Vérifier que l'enseignant existe
        $teacher = Teacher::with('user')->find($teacherId);
        if (!$teacher) {
            $this->error("Enseignant avec l'ID {$teacherId} non trouvé.");
            return 1;
        }

        // Vérifier que le club existe
        $club = Club::find($clubId);
        if (!$club) {
            $this->error("Club avec l'ID {$clubId} non trouvé.");
            return 1;
        }

        // Vérifier si l'enseignant est déjà assigné au club
        if ($teacher->clubs()->where('clubs.id', $clubId)->exists()) {
            $this->warn("L'enseignant {$teacher->user->name} est déjà assigné au club {$club->name}.");
            
            if ($this->confirm('Voulez-vous mettre à jour l\'assignation ?', false)) {
                $teacher->clubs()->updateExistingPivot($clubId, [
                    'is_active' => true,
                    'hourly_rate' => $hourlyRate ?: $teacher->hourly_rate,
                    'joined_at' => now(),
                ]);
                $this->info("Assignation mise à jour avec succès.");
            }
            return 0;
        }

        // Assigner l'enseignant au club
        $pivotData = [
            'is_active' => true,
            'joined_at' => now(),
        ];

        if ($hourlyRate) {
            $pivotData['hourly_rate'] = $hourlyRate;
        } elseif ($teacher->hourly_rate) {
            $pivotData['hourly_rate'] = $teacher->hourly_rate;
        }

        $teacher->clubs()->attach($clubId, $pivotData);

        $this->info("✅ Enseignant {$teacher->user->name} ({$teacher->user->email}) assigné au club {$club->name} (ID: {$clubId}) avec succès.");
        
        return 0;
    }
}

