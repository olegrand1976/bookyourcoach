<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReactivateStudent extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'student:reactivate 
                            {student_id : ID de l\'élève}
                            {club_id : ID du club}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Réactive un élève dans un club (met is_active = true)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $studentId = $this->argument('student_id');
        $clubId = $this->argument('club_id');

        // Vérifier que l'élève existe
        $student = DB::table('students')->where('id', $studentId)->first();
        if (!$student) {
            $this->error("Élève avec l'ID {$studentId} non trouvé.");
            return 1;
        }

        // Vérifier que le club existe
        $club = DB::table('clubs')->where('id', $clubId)->first();
        if (!$club) {
            $this->error("Club avec l'ID {$clubId} non trouvé.");
            return 1;
        }

        // Vérifier que la relation existe
        $clubStudent = DB::table('club_students')
            ->where('club_id', $clubId)
            ->where('student_id', $studentId)
            ->first();

        if (!$clubStudent) {
            $this->error("L'élève {$studentId} n'est pas associé au club {$clubId}.");
            return 1;
        }

        // Réactiver l'élève
        $updated = DB::table('club_students')
            ->where('club_id', $clubId)
            ->where('student_id', $studentId)
            ->update([
                'is_active' => true,
                'updated_at' => now()
            ]);

        if ($updated) {
            $this->info("✅ Élève {$studentId} réactivé avec succès dans le club {$clubId}.");
            return 0;
        } else {
            $this->error("Erreur lors de la réactivation de l'élève.");
            return 1;
        }
    }
}
