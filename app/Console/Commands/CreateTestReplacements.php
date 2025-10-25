<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Teacher;
use App\Models\Lesson;
use App\Models\LessonReplacement;
use Carbon\Carbon;

class CreateTestReplacements extends Command
{
    protected $signature = 'test:replacements {club_id=3}';
    protected $description = 'Crée des demandes de remplacement de test entre enseignants';

    public function handle()
    {
        $clubId = $this->argument('club_id');

        $this->info("🔄 Création de demandes de remplacement de test");
        $this->info("=============================================\n");

        try {
            // Récupérer 3 enseignants du club
            $teachers = Teacher::whereHas('user', function($q) {
                $q->where('email', 'LIKE', '%centre-equestre-des-etoiles%')
                  ->orWhere('email', 'LIKE', '%centre-Équestre-des-Étoiles%');
            })->limit(3)->get();

            if ($teachers->count() < 3) {
                $this->error("❌ Pas assez d'enseignants (besoin de 3, trouvé {$teachers->count()})");
                return 1;
            }

            $this->info("👨‍🏫 Enseignants sélectionnés:");
            foreach ($teachers as $i => $teacher) {
                $this->line("  " . ($i + 1) . ". {$teacher->user->name} ({$teacher->user->email})");
            }
            $this->newLine();

            // Récupérer des cours futurs pour chaque enseignant
            $this->info("📚 Création des demandes de remplacement...\n");

            $replacementsCreated = 0;

            // Demande 1: Teacher 1 demande à Teacher 2 de le remplacer
            $lesson1 = Lesson::where('club_id', $clubId)
                ->where('teacher_id', $teachers[0]->id)
                ->where('start_time', '>', Carbon::now())
                ->where('status', 'confirmed')
                ->first();

            if ($lesson1) {
                // Vérifier qu'il n'existe pas déjà
                $existing = LessonReplacement::where('lesson_id', $lesson1->id)->first();
                if (!$existing) {
                    LessonReplacement::create([
                        'lesson_id' => $lesson1->id,
                        'original_teacher_id' => $teachers[0]->id,
                        'replacement_teacher_id' => $teachers[1]->id,
                        'reason' => 'Rendez-vous médical',
                        'notes' => 'Merci de me remplacer pour ce cours, j\'ai un rendez-vous important.',
                        'status' => 'pending',
                        'requested_at' => now()
                    ]);

                    $replacementsCreated++;
                    $this->line("✓ Demande 1: {$teachers[0]->user->name} → {$teachers[1]->user->name}");
                    $this->line("  Cours: " . $lesson1->start_time->format('d/m/Y à H:i') . " (ID: {$lesson1->id})");
                }
            }

            // Demande 2: Teacher 2 demande à Teacher 3 de le remplacer
            $lesson2 = Lesson::where('club_id', $clubId)
                ->where('teacher_id', $teachers[1]->id)
                ->where('start_time', '>', Carbon::now())
                ->where('status', 'confirmed')
                ->first();

            if ($lesson2) {
                $existing = LessonReplacement::where('lesson_id', $lesson2->id)->first();
                if (!$existing) {
                    LessonReplacement::create([
                        'lesson_id' => $lesson2->id,
                        'original_teacher_id' => $teachers[1]->id,
                        'replacement_teacher_id' => $teachers[2]->id,
                        'reason' => 'Urgence familiale',
                        'notes' => 'Désolé du court préavis, problème familial urgent.',
                        'status' => 'pending',
                        'requested_at' => now()
                    ]);

                    $replacementsCreated++;
                    $this->line("✓ Demande 2: {$teachers[1]->user->name} → {$teachers[2]->user->name}");
                    $this->line("  Cours: " . $lesson2->start_time->format('d/m/Y à H:i') . " (ID: {$lesson2->id})");
                }
            }

            // Demande 3: Teacher 3 demande à Teacher 1 de le remplacer
            $lesson3 = Lesson::where('club_id', $clubId)
                ->where('teacher_id', $teachers[2]->id)
                ->where('start_time', '>', Carbon::now())
                ->where('status', 'confirmed')
                ->first();

            if ($lesson3) {
                $existing = LessonReplacement::where('lesson_id', $lesson3->id)->first();
                if (!$existing) {
                    LessonReplacement::create([
                        'lesson_id' => $lesson3->id,
                        'original_teacher_id' => $teachers[2]->id,
                        'replacement_teacher_id' => $teachers[0]->id,
                        'reason' => 'Indisponibilité personnelle',
                        'notes' => 'J\'ai un conflit d\'horaire, peux-tu me remplacer s\'il te plaît ?',
                        'status' => 'pending',
                        'requested_at' => now()
                    ]);

                    $replacementsCreated++;
                    $this->line("✓ Demande 3: {$teachers[2]->user->name} → {$teachers[0]->user->name}");
                    $this->line("  Cours: " . $lesson3->start_time->format('d/m/Y à H:i') . " (ID: {$lesson3->id})");
                }
            }

            // Demande 4: Teacher 1 demande à Teacher 3 (autre cours)
            $lesson4 = Lesson::where('club_id', $clubId)
                ->where('teacher_id', $teachers[0]->id)
                ->where('start_time', '>', Carbon::now())
                ->where('id', '!=', $lesson1?->id ?? 0)
                ->where('status', 'confirmed')
                ->skip(1)
                ->first();

            if ($lesson4) {
                $existing = LessonReplacement::where('lesson_id', $lesson4->id)->first();
                if (!$existing) {
                    LessonReplacement::create([
                        'lesson_id' => $lesson4->id,
                        'original_teacher_id' => $teachers[0]->id,
                        'replacement_teacher_id' => $teachers[2]->id,
                        'reason' => 'Problème de santé',
                        'notes' => 'Je ne me sens pas bien, je préfère rester au repos.',
                        'status' => 'pending',
                        'requested_at' => now()
                    ]);

                    $replacementsCreated++;
                    $this->line("✓ Demande 4: {$teachers[0]->user->name} → {$teachers[2]->user->name}");
                    $this->line("  Cours: " . $lesson4->start_time->format('d/m/Y à H:i') . " (ID: {$lesson4->id})");
                }
            }

            // Demande 5: Teacher 2 demande à Teacher 1 (acceptée automatiquement pour test)
            $lesson5 = Lesson::where('club_id', $clubId)
                ->where('teacher_id', $teachers[1]->id)
                ->where('start_time', '>', Carbon::now())
                ->where('id', '!=', $lesson2?->id ?? 0)
                ->where('status', 'confirmed')
                ->skip(1)
                ->first();

            if ($lesson5) {
                $existing = LessonReplacement::where('lesson_id', $lesson5->id)->first();
                if (!$existing) {
                    $replacement = LessonReplacement::create([
                        'lesson_id' => $lesson5->id,
                        'original_teacher_id' => $teachers[1]->id,
                        'replacement_teacher_id' => $teachers[0]->id,
                        'reason' => 'Conflit d\'horaire',
                        'notes' => 'J\'ai un autre engagement ce jour-là.',
                        'status' => 'accepted',
                        'requested_at' => now()->subDays(2),
                        'responded_at' => now()->subDay()
                    ]);

                    // Mettre à jour le cours
                    $lesson5->teacher_id = $teachers[0]->id;
                    $lesson5->save();

                    $replacementsCreated++;
                    $this->line("✓ Demande 5 (ACCEPTÉE): {$teachers[1]->user->name} → {$teachers[0]->user->name}");
                    $this->line("  Cours: " . $lesson5->start_time->format('d/m/Y à H:i') . " (ID: {$lesson5->id})");
                    $this->line("  → Cours maintenant assigné à {$teachers[0]->user->name}");
                }
            }

            $this->newLine();
            $this->info("✅ {$replacementsCreated} demandes de remplacement créées\n");

            // Afficher les statistiques par enseignant
            $this->info("📊 STATISTIQUES PAR ENSEIGNANT");
            $this->info("==============================\n");

            foreach ($teachers as $teacher) {
                $sent = LessonReplacement::where('original_teacher_id', $teacher->id)->count();
                $received = LessonReplacement::where('replacement_teacher_id', $teacher->id)->count();
                $pending = LessonReplacement::where('replacement_teacher_id', $teacher->id)
                    ->where('status', 'pending')
                    ->count();

                $this->line("👤 {$teacher->user->name}");
                $this->line("   Email: {$teacher->user->email}");
                $this->line("   Demandes envoyées: {$sent}");
                $this->line("   Demandes reçues: {$received}");
                $this->line("   En attente de réponse: {$pending}");
                $this->newLine();
            }

            $this->info("🎉 Test de remplacements prêt !");
            $this->info("Connectez-vous avec les comptes ci-dessus (mot de passe: password)");

            return 0;

        } catch (\Exception $e) {
            $this->error("❌ ERREUR: " . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
    }
}

