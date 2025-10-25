<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Teacher;
use App\Models\Lesson;
use App\Models\LessonReplacement;
use Carbon\Carbon;

class CreateMoreReplacementsForMarie extends Command
{
    protected $signature = 'test:replacements-marie';
    protected $description = 'Crée des demandes de remplacement POUR Marie (elle est le remplaçant)';

    public function handle()
    {
        $this->info("🔄 Création de demandes de remplacement pour Marie Leroy");
        $this->info("=======================================================\n");

        try {
            // Récupérer Marie
            $marie = Teacher::whereHas('user', function($q) {
                $q->where('email', 'marie.leroy@centre-Équestre-des-Étoiles.fr');
            })->first();

            // Récupérer Jean, Sophie et Thomas
            $jean = Teacher::whereHas('user', function($q) {
                $q->where('email', 'jean.moreau@centre-Équestre-des-Étoiles.fr');
            })->first();

            $sophie = Teacher::whereHas('user', function($q) {
                $q->where('email', 'sophie.rousseau@centre-equestre-des-etoiles.fr');
            })->first();

            $thomas = Teacher::whereHas('user', function($q) {
                $q->where('email', 'thomas.girard@centre-equestre-des-etoiles.fr');
            })->first();

            if (!$marie || !$jean || !$sophie || !$thomas) {
                $this->error("❌ Enseignants introuvables");
                return 1;
            }

            $this->line("👨‍🏫 Enseignants:");
            $this->line("  - Marie (ID: {$marie->id})");
            $this->line("  - Jean (ID: {$jean->id})");
            $this->line("  - Sophie (ID: {$sophie->id})");
            $this->line("  - Thomas (ID: {$thomas->id})\n");

            $replacementsCreated = 0;

            // Demande 1: Jean demande à Marie de le remplacer
            $lesson1 = Lesson::where('club_id', 3)
                ->where('teacher_id', $jean->id)
                ->where('start_time', '>', Carbon::now())
                ->where('status', 'confirmed')
                ->skip(2) // Prendre un autre cours
                ->first();

            if ($lesson1) {
                $existing = LessonReplacement::where('lesson_id', $lesson1->id)->first();
                if (!$existing) {
                    LessonReplacement::create([
                        'lesson_id' => $lesson1->id,
                        'original_teacher_id' => $jean->id,
                        'replacement_teacher_id' => $marie->id, // Marie est le remplaçant
                        'reason' => 'Rendez-vous médical',
                        'notes' => 'Salut Marie, peux-tu me remplacer pour ce cours ? Merci !',
                        'status' => 'pending',
                        'requested_at' => now()
                    ]);

                    $replacementsCreated++;
                    $this->line("✓ Jean → Marie : " . $lesson1->start_time->format('d/m à H:i'));
                }
            }

            // Demande 2: Sophie demande à Marie de la remplacer
            $lesson2 = Lesson::where('club_id', 3)
                ->where('teacher_id', $sophie->id)
                ->where('start_time', '>', Carbon::now())
                ->where('status', 'confirmed')
                ->first();

            if ($lesson2) {
                $existing = LessonReplacement::where('lesson_id', $lesson2->id)->first();
                if (!$existing) {
                    LessonReplacement::create([
                        'lesson_id' => $lesson2->id,
                        'original_teacher_id' => $sophie->id,
                        'replacement_teacher_id' => $marie->id, // Marie est le remplaçant
                        'reason' => 'Urgence familiale',
                        'notes' => 'Bonjour Marie, j\'ai une urgence, peux-tu me remplacer ?',
                        'status' => 'pending',
                        'requested_at' => now()
                    ]);

                    $replacementsCreated++;
                    $this->line("✓ Sophie → Marie : " . $lesson2->start_time->format('d/m à H:i'));
                }
            }

            // Demande 3: Thomas demande à Marie de le remplacer
            $lesson3 = Lesson::where('club_id', 3)
                ->where('teacher_id', $thomas->id)
                ->where('start_time', '>', Carbon::now())
                ->where('status', 'confirmed')
                ->first();

            if ($lesson3) {
                $existing = LessonReplacement::where('lesson_id', $lesson3->id)->first();
                if (!$existing) {
                    LessonReplacement::create([
                        'lesson_id' => $lesson3->id,
                        'original_teacher_id' => $thomas->id,
                        'replacement_teacher_id' => $marie->id, // Marie est le remplaçant
                        'reason' => 'Problème de santé',
                        'notes' => 'Marie, je ne me sens pas bien, pourrais-tu me remplacer ?',
                        'status' => 'pending',
                        'requested_at' => now()
                    ]);

                    $replacementsCreated++;
                    $this->line("✓ Thomas → Marie : " . $lesson3->start_time->format('d/m à H:i'));
                }
            }

            // Demande 4: Jean demande encore à Marie (acceptée automatiquement)
            $lesson4 = Lesson::where('club_id', 3)
                ->where('teacher_id', $jean->id)
                ->where('start_time', '>', Carbon::now())
                ->where('status', 'confirmed')
                ->skip(3)
                ->first();

            if ($lesson4) {
                $existing = LessonReplacement::where('lesson_id', $lesson4->id)->first();
                if (!$existing) {
                    $replacement = LessonReplacement::create([
                        'lesson_id' => $lesson4->id,
                        'original_teacher_id' => $jean->id,
                        'replacement_teacher_id' => $marie->id, // Marie est le remplaçant
                        'reason' => 'Conflit d\'horaire',
                        'notes' => 'Merci Marie d\'avoir accepté !',
                        'status' => 'accepted',
                        'requested_at' => now()->subDays(2),
                        'responded_at' => now()->subDay()
                    ]);

                    // Mettre à jour le cours
                    $lesson4->teacher_id = $marie->id;
                    $lesson4->save();

                    $replacementsCreated++;
                    $this->line("✓ Jean → Marie (ACCEPTÉE) : " . $lesson4->start_time->format('d/m à H:i'));
                }
            }

            $this->newLine();
            $this->info("✅ {$replacementsCreated} demandes créées\n");

            // Statistiques
            $sentByMarie = LessonReplacement::where('original_teacher_id', $marie->id)->count();
            $receivedByMarie = LessonReplacement::where('replacement_teacher_id', $marie->id)->count();
            $pendingForMarie = LessonReplacement::where('replacement_teacher_id', $marie->id)
                ->where('status', 'pending')
                ->count();

            $this->info("📊 STATISTIQUES MARIE LEROY");
            $this->info("===========================");
            $this->line("Demandes envoyées: {$sentByMarie}");
            $this->line("Demandes reçues: {$receivedByMarie}");
            $this->line("En attente de sa réponse: {$pendingForMarie}\n");

            $this->info("🎉 Marie peut maintenant accepter/refuser {$pendingForMarie} demandes !");

            return 0;

        } catch (\Exception $e) {
            $this->error("❌ ERREUR: " . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
    }
}

