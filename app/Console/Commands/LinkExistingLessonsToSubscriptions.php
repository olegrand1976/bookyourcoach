<?php

namespace App\Console\Commands;

use App\Models\Lesson;
use App\Models\SubscriptionInstance;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LinkExistingLessonsToSubscriptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:link-existing-lessons 
                            {--dry-run : Afficher les changements sans les appliquer}
                            {--student= : ID de l\'étudiant spécifique (optionnel)}
                            {--subscription= : Numéro d\'abonnement spécifique (optionnel)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Lie rétroactivement les cours existants aux abonnements actifs';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $studentId = $this->option('student');
        $subscriptionNumber = $this->option('subscription');
        
        $this->info('🔍 Recherche des cours non liés aux abonnements...');
        
        if ($dryRun) {
            $this->warn('⚠️  MODE DRY-RUN : Aucune modification ne sera effectuée');
        }
        
        // Récupérer tous les cours confirmés/completed/pending qui ne sont PAS annulés
        $lessonsQuery = Lesson::whereIn('status', ['pending', 'confirmed', 'completed'])
            ->where('status', '!=', 'cancelled');
        
        // Filtrer par étudiant si spécifié
        if ($studentId) {
            $lessonsQuery->where(function($query) use ($studentId) {
                $query->where('student_id', $studentId)
                      ->orWhereHas('students', function($q) use ($studentId) {
                          $q->where('students.id', $studentId);
                      });
            });
            $this->info("📌 Filtrage par étudiant ID: {$studentId}");
        }
        
        $lessons = $lessonsQuery->with(['students'])->get();
        
        $this->info("📊 {$lessons->count()} cours trouvés");
        
        $stats = [
            'total_checked' => 0,
            'total_linked' => 0,
            'total_already_linked' => 0,
            'total_no_subscription' => 0,
            'details' => []
        ];
        
        $progressBar = $this->output->createProgressBar($lessons->count());
        $progressBar->start();
        
        foreach ($lessons as $lesson) {
            $stats['total_checked']++;
            
            // Récupérer les IDs des étudiants pour ce cours
            $studentIds = [];
            if ($lesson->student_id) {
                $studentIds[] = $lesson->student_id;
            }
            
            $lessonStudents = $lesson->students()->pluck('students.id')->toArray();
            $studentIds = array_unique(array_merge($studentIds, $lessonStudents));
            
            // Si aucun étudiant, passer
            if (empty($studentIds)) {
                $progressBar->advance();
                continue;
            }
            
            // Pour chaque étudiant du cours
            foreach ($studentIds as $currentStudentId) {
                // Récupérer les instances d'abonnements actifs où l'élève est inscrit
                $subscriptionInstancesQuery = SubscriptionInstance::where('status', 'active')
                    ->whereHas('students', function ($query) use ($currentStudentId) {
                        $query->where('students.id', $currentStudentId);
                    })
                    ->with(['subscription.template.courseTypes', 'students']);
                
                // Filtrer par numéro d'abonnement si spécifié
                if ($subscriptionNumber) {
                    $subscriptionInstancesQuery->whereHas('subscription', function($query) use ($subscriptionNumber) {
                        $query->where('subscription_number', $subscriptionNumber);
                    });
                }
                
                $subscriptionInstances = $subscriptionInstancesQuery
                    ->orderBy('started_at', 'asc') // FIFO
                    ->get();
                
                if ($subscriptionInstances->isEmpty()) {
                    $stats['total_no_subscription']++;
                    continue;
                }
                
                // Trouver la première instance valide pour ce type de cours
                $linked = false;
                foreach ($subscriptionInstances as $subscriptionInstance) {
                    // Vérifier si le cours est déjà lié à cet abonnement
                    if ($subscriptionInstance->lessons()->where('lesson_id', $lesson->id)->exists()) {
                        $stats['total_already_linked']++;
                        $linked = true;
                        break;
                    }
                    
                    // Vérifier si ce cours fait partie de l'abonnement
                    // Utiliser le template pour obtenir les course_types
                    if (!$subscriptionInstance->subscription->template) {
                        continue;
                    }
                    $courseTypeIds = $subscriptionInstance->subscription->template->courseTypes->pluck('id')->toArray();
                    
                    if (!in_array($lesson->course_type_id, $courseTypeIds)) {
                        continue;
                    }
                    
                    // Recalculer les cours utilisés
                    $subscriptionInstance->recalculateLessonsUsed();
                    
                    if ($subscriptionInstance->remaining_lessons <= 0) {
                        continue;
                    }
                    
                    // On a trouvé un abonnement valide !
                    if (!$dryRun) {
                        try {
                            // Lier le cours à l'abonnement
                            $subscriptionInstance->lessons()->attach($lesson->id);
                            $subscriptionInstance->recalculateLessonsUsed();
                            
                            $stats['total_linked']++;
                            $stats['details'][] = [
                                'lesson_id' => $lesson->id,
                                'lesson_date' => $lesson->start_time->format('d/m/Y H:i'),
                                'student_id' => $currentStudentId,
                                'subscription_number' => $subscriptionInstance->subscription->subscription_number,
                                'subscription_instance_id' => $subscriptionInstance->id,
                                'lessons_used_after' => $subscriptionInstance->lessons_used
                            ];
                            
                            Log::info("✅ Cours {$lesson->id} lié à l'abonnement {$subscriptionInstance->id}", [
                                'lesson_id' => $lesson->id,
                                'student_id' => $currentStudentId,
                                'subscription_instance_id' => $subscriptionInstance->id,
                                'subscription_number' => $subscriptionInstance->subscription->subscription_number
                            ]);
                        } catch (\Exception $e) {
                            Log::error("❌ Erreur lors du lien du cours {$lesson->id}: " . $e->getMessage());
                        }
                    } else {
                        $stats['total_linked']++;
                        $this->line("\n  → Cours #{$lesson->id} ({$lesson->start_time->format('d/m/Y')}) → Abonnement {$subscriptionInstance->subscription->subscription_number}");
                    }
                    
                    $linked = true;
                    break; // Un seul abonnement par étudiant
                }
                
                if (!$linked && !$subscriptionInstances->isEmpty()) {
                    $stats['total_no_subscription']++;
                }
            }
            
            $progressBar->advance();
        }
        
        $progressBar->finish();
        $this->newLine(2);
        
        // Afficher les statistiques
        $this->info('📊 Résultats :');
        $this->table(
            ['Métrique', 'Valeur'],
            [
                ['Cours vérifiés', $stats['total_checked']],
                ['Cours déjà liés', $stats['total_already_linked']],
                ['Cours nouvellement liés', $stats['total_linked']],
                ['Cours sans abonnement compatible', $stats['total_no_subscription']],
            ]
        );
        
        if (!empty($stats['details']) && !$dryRun) {
            $this->info("\n📋 Détails des cours liés :");
            $this->table(
                ['ID Cours', 'Date', 'Étudiant ID', 'N° Abonnement', 'Cours utilisés après'],
                array_map(function($detail) {
                    return [
                        $detail['lesson_id'],
                        $detail['lesson_date'],
                        $detail['student_id'],
                        $detail['subscription_number'],
                        $detail['lessons_used_after']
                    ];
                }, array_slice($stats['details'], 0, 20)) // Limiter à 20 pour ne pas surcharger l'affichage
            );
            
            if (count($stats['details']) > 20) {
                $this->info("... et " . (count($stats['details']) - 20) . " autres");
            }
        }
        
        if ($dryRun && $stats['total_linked'] > 0) {
            $this->warn("\n⚠️  Exécutez la commande sans --dry-run pour appliquer les changements");
        }
        
        $this->newLine();
        $this->info('✅ Terminé !');
        
        return Command::SUCCESS;
    }
}
