<?php

namespace App\Jobs;

use App\Models\Lesson;
use App\Models\SubscriptionInstance;
use App\Models\SubscriptionRecurringSlot;
use App\Notifications\LessonBookedNotification;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job qui traite les actions post-création d'un cours de manière asynchrone
 * - Consommation d'abonnement
 * - Création de créneaux récurrents
 * - Envoi des notifications
 * - Programmation des rappels
 */
class ProcessLessonPostCreationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Lesson $lesson;

    /**
     * Create a new job instance.
     */
    public function __construct(Lesson $lesson)
    {
        $this->lesson = $lesson;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info("🚀 [ProcessLessonPostCreation] Début traitement asynchrone pour le cours {$this->lesson->id}");

            // 1. Essayer de consommer un abonnement si l'élève en a un actif
            if ($this->lesson->student_id) {
                $this->tryConsumeSubscription();
                
                // 2. Créer un créneau récurrent si l'élève a un abonnement
                $this->createRecurringSlotIfSubscription();
            }

            // 3. Envoyer les notifications
            $this->sendBookingNotifications();

            // 4. Programmer un rappel 24h avant le cours
            $this->scheduleReminder();

            Log::info("✅ [ProcessLessonPostCreation] Traitement asynchrone terminé pour le cours {$this->lesson->id}");
        } catch (\Exception $e) {
            Log::error("❌ [ProcessLessonPostCreation] Erreur lors du traitement asynchrone du cours {$this->lesson->id}: " . $e->getMessage(), [
                'lesson_id' => $this->lesson->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            // Ne pas faire échouer le job, juste logger l'erreur
        }
    }

    /**
     * Essaie de consommer un abonnement actif pour ce cours
     */
    private function tryConsumeSubscription(): void
    {
        try {
            if (!$this->lesson->course_type_id) {
                return;
            }

            $studentIds = [];
            if ($this->lesson->student_id) {
                $studentIds[] = $this->lesson->student_id;
            }
            
            $lessonStudents = $this->lesson->students()->pluck('students.id')->toArray();
            $studentIds = array_unique(array_merge($studentIds, $lessonStudents));
            
            if (empty($studentIds)) {
                return;
            }

            foreach ($studentIds as $studentId) {
                $subscriptionInstances = SubscriptionInstance::where('status', 'active')
                    ->whereHas('students', function ($query) use ($studentId) {
                        $query->where('students.id', $studentId);
                    })
                    ->with(['subscription.courseTypes', 'students'])
                    ->orderBy('started_at', 'asc')
                    ->get();

                Log::info("🔍 Recherche d'abonnement pour le cours {$this->lesson->id}", [
                    'student_id' => $studentId,
                    'course_type_id' => $this->lesson->course_type_id,
                    'subscriptions_found' => $subscriptionInstances->count()
                ]);

                foreach ($subscriptionInstances as $subscriptionInstance) {
                    $subscriptionInstance->checkAndUpdateStatus();
                    
                    if ($subscriptionInstance->status !== 'active') {
                        continue;
                    }

                    $courseTypeIds = $subscriptionInstance->subscription->courseTypes->pluck('id')->toArray();
                    $subscriptionInstance->recalculateLessonsUsed();
                    
                    // 🐛 DEBUG : Log avant de vérifier les conditions
                    Log::info("🔍 [DEBUG] Vérification conditions pour subscription_instance {$subscriptionInstance->id}", [
                        'course_type_id' => $this->lesson->course_type_id,
                        'allowed_course_types' => $courseTypeIds,
                        'type_match' => in_array($this->lesson->course_type_id, $courseTypeIds),
                        'remaining_lessons_check_started' => true
                    ]);
                    
                    if (in_array($this->lesson->course_type_id, $courseTypeIds)) {
                        // Vérifier remaining_lessons SANS appeler l'attribut qui pourrait causer des problèmes
                        // Calculer manuellement pour éviter les effets de bord
                        $totalLessons = $subscriptionInstance->subscription->total_available_lessons;
                        $lessonsUsed = $subscriptionInstance->lessons_used;
                        $remainingLessons = max(0, $totalLessons - $lessonsUsed);
                        
                        Log::info("🔍 [DEBUG] Calcul manuel remaining_lessons", [
                            'subscription_instance_id' => $subscriptionInstance->id,
                            'total_lessons' => $totalLessons,
                            'lessons_used' => $lessonsUsed,
                            'remaining_lessons' => $remainingLessons
                        ]);
                        
                        if ($remainingLessons > 0) {
                            try {
                                Log::info("🎯 [DEBUG] Tentative de consommation du cours {$this->lesson->id} pour l'abonnement {$subscriptionInstance->id}");
                                
                                $subscriptionInstance->consumeLesson($this->lesson);
                                
                                $studentNames = $subscriptionInstance->students->map(function ($student) {
                                    if ($student->user) {
                                        return $student->user->name;
                                    }
                                    $firstName = $student->first_name ?? '';
                                    $lastName = $student->last_name ?? '';
                                    $name = trim($firstName . ' ' . $lastName);
                                    return !empty($name) ? $name : 'Élève sans nom';
                                })->filter()->join(', ');
                                
                                $subscriptionInstance->refresh();
                                
                                $totalLessons = $subscriptionInstance->subscription->total_available_lessons;
                                $isFullyUsed = $subscriptionInstance->lessons_used >= $totalLessons;
                                
                                if ($isFullyUsed && $subscriptionInstance->status === 'active') {
                                    $subscriptionInstance->status = 'completed';
                                    $subscriptionInstance->save();
                                    
                                    Log::info("📦 Abonnement {$subscriptionInstance->id} ARCHIVÉ (100% utilisé)", [
                                        'subscription_instance_id' => $subscriptionInstance->id,
                                        'lessons_used' => $subscriptionInstance->lessons_used,
                                        'total_lessons' => $totalLessons,
                                        'students' => $studentNames
                                    ]);
                                }
                                
                                Log::info("✅ Cours {$this->lesson->id} consommé depuis l'abonnement {$subscriptionInstance->id} (FIFO)", [
                                    'lesson_id' => $this->lesson->id,
                                    'subscription_instance_id' => $subscriptionInstance->id,
                                    'student_id' => $studentId,
                                    'lessons_used' => $subscriptionInstance->lessons_used,
                                    'remaining_lessons' => $subscriptionInstance->remaining_lessons
                                ]);
                                
                                break;
                            } catch (\Exception $e) {
                                Log::error("❌ Erreur lors de la consommation du cours {$this->lesson->id} pour l'abonnement {$subscriptionInstance->id}", [
                                    'error' => $e->getMessage(),
                                    'trace' => $e->getTraceAsString(),
                                    'line' => $e->getLine(),
                                    'file' => $e->getFile()
                                ]);
                                continue;
                            }
                        } else {
                            Log::info("⚠️ [DEBUG] Pas de cours restants pour l'abonnement {$subscriptionInstance->id}");
                        }
                    } else {
                        Log::info("⚠️ [DEBUG] Type de cours {$this->lesson->course_type_id} non compatible avec l'abonnement {$subscriptionInstance->id}");
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error("Erreur tryConsumeSubscription: " . $e->getMessage());
        }
    }

    /**
     * Crée un créneau récurrent si l'élève a un abonnement actif
     */
    private function createRecurringSlotIfSubscription(): void
    {
        try {
            if (!$this->lesson->student_id || !$this->lesson->teacher_id) {
                return;
            }

            $activeSubscription = SubscriptionInstance::where('status', 'active')
                ->whereHas('students', function ($query) {
                    $query->where('students.id', $this->lesson->student_id);
                })
                ->with('subscription')
                ->orderBy('started_at', 'asc')
                ->first();

            if (!$activeSubscription) {
                Log::info("🔄 Pas de récurrence créée : aucun abonnement actif pour l'élève {$this->lesson->student_id}");
                return;
            }

            $startTime = Carbon::parse($this->lesson->start_time);
            $dayOfWeek = $startTime->dayOfWeek;
            $timeStart = $startTime->format('H:i:s');
            $timeEnd = $startTime->copy()->addMinutes($this->lesson->duration ?? 60)->format('H:i:s');

            $recurringStartDate = Carbon::parse($this->lesson->start_time)->startOfDay();
            $recurringEndDate = now()->addMonths(6);
            
            if ($activeSubscription->expires_at && Carbon::parse($activeSubscription->expires_at)->lessThan($recurringEndDate)) {
                $recurringEndDate = Carbon::parse($activeSubscription->expires_at);
            }

            $existingRecurring = SubscriptionRecurringSlot::where('subscription_instance_id', $activeSubscription->id)
                ->where('student_id', $this->lesson->student_id)
                ->where('teacher_id', $this->lesson->teacher_id)
                ->where('day_of_week', $dayOfWeek)
                ->where('start_time', $timeStart)
                ->where('status', 'active')
                ->first();

            if ($existingRecurring) {
                Log::info("🔄 Récurrence déjà existante pour ce créneau");
                return;
            }

            // ✅ OPTIMISATION : Ne pas vérifier les conflits - les créer directement
            // Les conflits seront gérés manuellement par le club via l'interface
            $recurringSlot = SubscriptionRecurringSlot::create([
                'subscription_instance_id' => $activeSubscription->id,
                'open_slot_id' => null,
                'teacher_id' => $this->lesson->teacher_id,
                'student_id' => $this->lesson->student_id,
                'day_of_week' => $dayOfWeek,
                'start_time' => $timeStart,
                'end_time' => $timeEnd,
                'start_date' => $recurringStartDate,
                'end_date' => $recurringEndDate,
                'status' => 'active',
                'notes' => "Créneau récurrent RÉSERVÉ automatiquement pour le cours #{$this->lesson->id}",
            ]);

            Log::info("✅ Créneau récurrent RÉSERVÉ", [
                'recurring_slot_id' => $recurringSlot->id,
                'lesson_id' => $this->lesson->id,
                'student_id' => $this->lesson->student_id,
                'teacher_id' => $this->lesson->teacher_id,
                'day_of_week' => $dayOfWeek
            ]);

        } catch (\Exception $e) {
            Log::error("Erreur createRecurringSlotIfSubscription: " . $e->getMessage());
        }
    }

    /**
     * Envoie les notifications de réservation
     */
    private function sendBookingNotifications(): void
    {
        try {
            // Recharger les relations pour avoir les données à jour
            $this->lesson->load(['teacher.user', 'student.user']);

            if ($this->lesson->teacher && $this->lesson->teacher->user) {
                $this->lesson->teacher->user->notify(new LessonBookedNotification($this->lesson));
            }

            if ($this->lesson->student && $this->lesson->student->user) {
                $this->lesson->student->user->notify(new LessonBookedNotification($this->lesson));
            }

            Log::info("✅ Notifications envoyées pour le cours {$this->lesson->id}");
        } catch (\Exception $e) {
            Log::error("Erreur sendBookingNotifications: " . $e->getMessage());
        }
    }

    /**
     * Programme un rappel 24h avant le cours
     */
    private function scheduleReminder(): void
    {
        try {
            $reminderTime = Carbon::parse($this->lesson->start_time)->subHours(24);
            if ($reminderTime->isFuture()) {
                SendLessonReminderJob::dispatch($this->lesson)->delay($reminderTime);
                Log::info("✅ Rappel programmé pour le cours {$this->lesson->id} à {$reminderTime}");
            }
        } catch (\Exception $e) {
            Log::warning("Impossible de programmer le rappel pour le cours {$this->lesson->id}: " . $e->getMessage());
        }
    }
}



