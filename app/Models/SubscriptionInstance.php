<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use App\Models\CourseType;

class SubscriptionInstance extends Model
{
    use HasFactory;

    protected $fillable = [
        'subscription_id',
        'lessons_used',
        'manual_lessons_used',
        'started_at',
        'expires_at',
        'status',
        'stripe_checkout_session_id',
        // Champs pour le calcul des commissions
        'est_legacy',
        'date_paiement',
        'montant',
        'teacher_id',
    ];

    protected $casts = [
        'lessons_used' => 'integer',
        'manual_lessons_used' => 'integer',
        'started_at' => 'date',
        'expires_at' => 'date',
        'est_legacy' => 'boolean',
        'date_paiement' => 'date',
        'montant' => 'decimal:2',
    ];

    /**
     * Boot method pour calculer automatiquement expires_at si non fourni
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($instance) {
            // Si expires_at n'est pas défini, le calculer depuis validity_months du template
            if (!$instance->expires_at && $instance->subscription && $instance->subscription->validity_months) {
                $startDate = $instance->started_at ? Carbon::parse($instance->started_at) : Carbon::now();
                $instance->expires_at = $startDate->copy()->addMonths($instance->subscription->validity_months);
            }
        });
    }

    /**
     * L'abonnement (modèle) associé
     */
    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * L'enseignant qui doit recevoir la commission pour cet abonnement
     */
    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    /**
     * Les élèves qui partagent cet abonnement (relation many-to-many)
     */
    public function students()
    {
        return $this->belongsToMany(Student::class, 'subscription_instance_students', 'subscription_instance_id', 'student_id')
            ->withTimestamps();
    }

    /**
     * Les cours consommés dans le cadre de cet abonnement
     */
    public function lessons()
    {
        return $this->belongsToMany(Lesson::class, 'subscription_lessons', 'subscription_instance_id', 'lesson_id')
            ->withTimestamps();
    }

    /**
     * Les créneaux récurrents bloqués pour cet abonnement (ancien modèle)
     */
    public function legacyRecurringSlots()
    {
        return $this->hasMany(SubscriptionRecurringSlot::class);
    }

    /**
     * Les créneaux récurrents (nouveau modèle) liés à cet abonnement
     */
    public function recurringSlotSubscriptions()
    {
        return $this->hasMany(RecurringSlotSubscription::class);
    }

    /**
     * Calcule automatiquement lessons_used en comptant les cours réellement consommés
     * (exclut les cours annulés)
     * 
     * ⚠️ IMPORTANT : Si aucun cours n'est attaché et qu'une valeur manuelle existe,
     * on préserve la valeur manuelle pour permettre l'initialisation avec lessons_used > 0
     */
    public function recalculateLessonsUsed(): void
    {
        $consumedLessons = $this->getConsumedLessonsCount();
        $oldValue = $this->lessons_used;
        $manualLessonsUsed = $this->resolveManualLessonsUsed();
        $newLessonsUsed = $manualLessonsUsed + $consumedLessons;

        \Log::info("🔍 Recalcul lessons_used pour subscription_instance {$this->id}", [
            'old_lessons_used' => $oldValue,
            'manual_lessons_used' => $manualLessonsUsed,
            'consumed_lessons' => $consumedLessons,
            'new_calculated' => $newLessonsUsed,
            'will_update' => ($oldValue !== $newLessonsUsed),
            'subscription_id' => $this->subscription_id,
            'subscription_instance_id' => $this->id
        ]);

        if ($this->lessons_used !== $newLessonsUsed) {
            $this->lessons_used = $newLessonsUsed;
            $this->saveQuietly();

            \Log::info("✅ Lessons_used recalculé avec base manuelle pour subscription_instance {$this->id}", [
                'old_value' => $oldValue,
                'manual_lessons_used' => $manualLessonsUsed,
                'consumed_lessons' => $consumedLessons,
                'new_value' => $newLessonsUsed,
            ]);

            $this->checkAndUpdateStatus();
        }
    }

    /**
     * Cours passés consommés + annulations tardives comptées (pivot conservé).
     * Source de vérité pour lessons_used = manual_lessons_used + ce compteur.
     */
    public function getConsumedLessonsCount(): int
    {
        $hasCountColumn = \Illuminate\Support\Facades\Schema::hasColumn('lessons', 'cancellation_count_in_subscription');

        return (int) $this->buildAttachedLessonsQuery()
            ->where(function ($q) use ($hasCountColumn) {
                $q->where(function ($q2) {
                    $q2->whereIn('lessons.status', ['pending', 'confirmed', 'completed'])
                        ->where('lessons.start_time', '<=', Carbon::now());
                });
                if ($hasCountColumn) {
                    $q->orWhere(function ($q2) {
                        $q2->where('lessons.status', 'cancelled')
                            ->where('lessons.cancellation_count_in_subscription', true);
                    });
                }
            })
            ->count();
    }

    /**
     * Cours attachés qui réservent une place : consommés + futurs non annulés.
     * Utilisé pour le plafond d'attachement (les futurs ne consomment pas lessons_used mais bloquent la capacité).
     */
    public function getAttachedCountableLessonsCount(): int
    {
        return (int) $this->buildAttachedLessonsQuery()
            ->where(function ($q) {
                $q->where(function ($q2) {
                    $q2->whereIn('lessons.status', ['pending', 'confirmed', 'completed'])
                        ->where('lessons.start_time', '>', Carbon::now());
                })
                    ->orWhere(function ($q2) {
                        $q2->whereIn('lessons.status', ['pending', 'confirmed', 'completed'])
                            ->where('lessons.start_time', '<=', Carbon::now());
                    });

                if (\Illuminate\Support\Facades\Schema::hasColumn('lessons', 'cancellation_count_in_subscription')) {
                    $q->orWhere(function ($q2) {
                        $q2->where('lessons.status', 'cancelled')
                            ->where('lessons.cancellation_count_in_subscription', true);
                    });
                }
            })
            ->count();
    }

    /**
     * Places restantes pour attacher un nouveau cours (futur ou passé).
     */
    public function getRemainingAttachmentSlots(): int
    {
        $total = $this->subscription->total_available_lessons;
        $manual = $this->resolveManualLessonsUsed();
        $attached = $this->getAttachedCountableLessonsCount();

        return max(0, $total - $manual - $attached);
    }

    /**
     * Capacité restante pour planifier des cours récurrents (génération auto).
     * Fallback illimité si le total est indéterminé et aucun cours attaché (legacy).
     */
    public function resolveRemainingAttachmentSlotsForPlanning(): int
    {
        $instance = $this->fresh();
        $slots = $instance->getRemainingAttachmentSlots();
        $total = $instance->subscription->total_available_lessons;

        if ($total <= 0 && $slots === 0 && $instance->getAttachedCountableLessonsCount() === 0) {
            return PHP_INT_MAX;
        }

        return $slots;
    }

    private function buildAttachedLessonsQuery()
    {
        $subscriptionStudentIds = $this->students()->pluck('students.id')->map(fn ($id) => (int) $id)->all();

        $query = \Illuminate\Support\Facades\DB::table('subscription_lessons')
            ->join('lessons', 'subscription_lessons.lesson_id', '=', 'lessons.id')
            ->where('subscription_lessons.subscription_instance_id', $this->id);

        if ($subscriptionStudentIds !== []) {
            $query->where(function ($q) use ($subscriptionStudentIds) {
                $q->whereIn('lessons.student_id', $subscriptionStudentIds)
                    ->orWhereExists(function ($sub) use ($subscriptionStudentIds) {
                        $sub->select(\Illuminate\Support\Facades\DB::raw(1))
                            ->from('lesson_student')
                            ->whereColumn('lesson_student.lesson_id', 'lessons.id')
                            ->whereIn('lesson_student.student_id', $subscriptionStudentIds);
                    })
                    ->orWhere(function ($q2) {
                        $q2->whereNull('lessons.student_id')
                            ->whereNotExists(function ($sub) {
                                $sub->select(\Illuminate\Support\Facades\DB::raw(1))
                                    ->from('lesson_student')
                                    ->whereColumn('lesson_student.lesson_id', 'lessons.id');
                            });
                    });
            });
        }

        return $query;
    }

    private function resolveManualLessonsUsed(): int
    {
        return max(0, (int) ($this->manual_lessons_used ?? 0));
    }

    /**
     * Nombre de cours restants
     * 
     * ⚠️ Ne recalcule PAS automatiquement pour préserver les valeurs manuelles.
     * Le recalcul se fait automatiquement lors de l'ajout/suppression de cours via les observers.
     */
    public function getRemainingLessonsAttribute()
    {
        // Utiliser directement lessons_used sans recalculer pour préserver les valeurs manuelles
        // Le recalcul se fait automatiquement quand des cours sont attachés/détachés
        $total = $this->subscription->total_available_lessons;
        return max(0, $total - $this->lessons_used);
    }

    /**
     * Pourcentage d'utilisation
     * 
     * ⚠️ Ne recalcule PAS automatiquement pour préserver les valeurs manuelles.
     * Le recalcul se fait automatiquement lors de l'ajout/suppression de cours via les observers.
     */
    public function getUsagePercentageAttribute()
    {
        // Utiliser directement lessons_used sans recalculer pour préserver les valeurs manuelles
        // Le recalcul se fait automatiquement quand des cours sont attachés/détachés
        $total = $this->subscription->total_available_lessons;
        if ($total === 0) return 0;
        return round(($this->lessons_used / $total) * 100, 1);
    }

    /**
     * Calculer expires_at depuis validity_months du template
     */
    public function calculateExpiresAt()
    {
        if (!$this->started_at) {
            $this->started_at = Carbon::now();
        }
        
        $startDate = Carbon::parse($this->started_at);
        $validityMonths = $this->subscription->validity_months ?? 12;
        $this->expires_at = $startDate->copy()->addMonths($validityMonths);
    }

    /**
     * Est-ce que l'abonnement est proche de la fin (< 20% restant)
     */
    public function getIsNearingEndAttribute()
    {
        return $this->usage_percentage >= 80;
    }

    /**
     * Est-ce que l'abonnement arrive à expiration bientôt (moins de 7 jours)
     */
    public function getIsExpiringAttribute()
    {
        if (!$this->expires_at) return false;
        return Carbon::now()->diffInDays($this->expires_at, false) <= 7 && Carbon::now()->diffInDays($this->expires_at, false) >= 0;
    }

    /**
     * Obtenir les noms des élèves partagés
     */
    public function getStudentNamesAttribute()
    {
        return $this->students->map(function ($student) {
            // Gérer le cas où l'élève n'a pas de compte utilisateur
            if ($student->user) {
                return $student->user->name;
            }
            // Utiliser first_name et last_name de la table students si user n'existe pas
            $firstName = $student->first_name ?? '';
            $lastName = $student->last_name ?? '';
            $name = trim($firstName . ' ' . $lastName);
            return !empty($name) ? $name : 'Élève sans nom';
        })->filter()->join(', ');
    }

    /**
     * Vérifier et mettre à jour le statut si nécessaire
     * 📦 ARCHIVAGE : Les abonnements pleins (100% utilisés) passent en 'completed'
     * 🔄 RÉOUVERTURE : Si un abonnement completed redevient disponible (après annulation), le réouvrir
     */
    public function checkAndUpdateStatus()
    {
        // Ne pas recalculer ici pour éviter la récursion (recalculé ailleurs avant l'appel)
        
        $totalAvailable = $this->subscription->total_available_lessons;

        if ($totalAvailable <= 0) {
            return $this->status;
        }
        
        // 🔄 RÉOUVERTURE : Si l'abonnement est completed mais qu'il redevient disponible
        // (par exemple après annulation d'un cours), le remettre en active
        if ($this->status === 'completed' && $this->lessons_used < $totalAvailable) {
            $this->status = 'active';
            $this->saveQuietly();
            
            \Log::info("🔄 Abonnement {$this->id} réouvert (completed -> active)", [
                'subscription_instance_id' => $this->id,
                'old_status' => 'completed',
                'new_status' => 'active',
                'reason' => 'Abonnement redevient disponible après annulation',
                'lessons_used' => $this->lessons_used,
                'total_available' => $totalAvailable,
                'remaining' => $totalAvailable - $this->lessons_used
            ]);
            return 'active';
        }
        
        // 📦 ARCHIVAGE : Si tous les cours sont utilisés, marquer comme completed (archive)
        if ($this->lessons_used >= $totalAvailable) {
            if ($this->status !== 'completed') {
                $oldStatus = $this->status;
                $this->status = 'completed';
                $this->saveQuietly();
                
                \Log::info("📦 Abonnement {$this->id} archivé automatiquement", [
                    'subscription_instance_id' => $this->id,
                    'old_status' => $oldStatus,
                    'new_status' => 'completed',
                    'reason' => '100% des cours utilisés',
                    'lessons_used' => $this->lessons_used,
                    'total_available' => $totalAvailable
                ]);
            }
            return 'completed';
        }

        // Si la date d'expiration est dépassée
        if ($this->expires_at && Carbon::now()->isAfter($this->expires_at)) {
            if ($this->status !== 'expired') {
                $this->status = 'expired';
                $this->saveQuietly();
            }
            return 'expired';
        }

        // Si l'abonnement est actif et qu'il reste des cours, s'assurer qu'il est bien en active
        if ($this->status !== 'active' && $this->lessons_used < $totalAvailable) {
            // Ne pas réouvrir automatiquement les abonnements cancelled ou expired
            // Seulement si c'était completed
            if ($this->status === 'completed') {
                $this->status = 'active';
                $this->saveQuietly();
            }
        }

        return $this->status;
    }

    /**
     * Consommer un cours (attacher au abonnement)
     * Le compteur lessons_used sera recalculé automatiquement par l'observer
     */
    public function consumeLesson(Lesson $lesson)
    {
        // Vérifier que le cours n'est pas déjà attaché à cet abonnement
        if ($this->lessons()->where('lesson_id', $lesson->id)->exists()) {
            // Le cours est déjà attaché, juste recalculer
            $this->recalculateLessonsUsed();
            return;
        }

        if ($this->getRemainingAttachmentSlots() <= 0) {
            throw new \Exception('Aucun cours restant dans cet abonnement');
        }

        // Vérifier que le cours est bien du bon type
        // Vérifier si on utilise course_type_id ou discipline_id
        $courseTypeIds = [];
        $courseTypes = $this->subscription->courseTypes;
        
        foreach ($courseTypes as $courseType) {
            // Si c'est un CourseType, utiliser son id directement
            if ($courseType instanceof CourseType) {
                $courseTypeIds[] = $courseType->id;
            } 
            // Si c'est une Discipline (ancien système), récupérer les course_types liés
            else {
                $disciplineCourseTypes = CourseType::where('discipline_id', $courseType->id)->pluck('id')->toArray();
                $courseTypeIds = array_merge($courseTypeIds, $disciplineCourseTypes);
            }
        }
        
        if (!in_array($lesson->course_type_id, $courseTypeIds)) {
            throw new \Exception('Ce cours n\'est pas inclus dans cet abonnement');
        }

        // Vérifier que l'élève fait partie de cet abonnement (intersection participants du cours ∩ bénéficiaires de l'instance)
        $subscriptionStudentIds = $this->students()->pluck('students.id')->map(fn ($id) => (int) $id)->all();
        $lessonParticipantIds = array_values(array_unique(array_filter(array_map(
            'intval',
            array_merge(
                $lesson->student_id ? [(int) $lesson->student_id] : [],
                $lesson->students()->pluck('students.id')->all()
            )
        ))));

        if ($lessonParticipantIds === []) {
            throw new \Exception('Impossible de déterminer l\'élève participant pour ce cours');
        }

        $hasValidStudent = count(array_intersect($lessonParticipantIds, $subscriptionStudentIds)) > 0;

        if (! $hasValidStudent && $subscriptionStudentIds !== []) {
            throw new \Exception('Cet élève ne fait pas partie de cet abonnement');
        }

        // Vérifier que le cours n'est pas annulé
        if ($lesson->status === 'cancelled') {
            throw new \Exception('Un cours annulé ne peut pas être consommé depuis un abonnement');
        }

        // 📅 LOGIQUE : Mettre à jour started_at si c'est le premier cours réellement pris
        // La date de début doit être basée sur le premier cours, pas sur la création de l'abonnement
        $isFirstLesson = $this->lessons()->count() === 0;
        if ($isFirstLesson && $lesson->start_time) {
            $lessonDate = Carbon::parse($lesson->start_time)->startOfDay();
            // Pour le premier cours, toujours mettre à jour started_at avec la date du cours
            // (peu importe si c'est dans le passé ou le futur)
            $oldStartedAt = $this->started_at;
            $this->started_at = $lessonDate;
            \Log::info("📅 Date de début mise à jour pour subscription_instance {$this->id}", [
                'old_started_at' => $oldStartedAt,
                'new_started_at' => $this->started_at,
                'based_on_lesson' => $lesson->id,
                'lesson_date' => $lesson->start_time,
                'is_first_lesson' => true
            ]);
            
            // 🔄 Recalculer expires_at à partir de la nouvelle date de début
            $this->calculateExpiresAt();
            \Log::info("🔄 Date d'expiration recalculée pour subscription_instance {$this->id}", [
                'new_expires_at' => $this->expires_at,
                'based_on_started_at' => $this->started_at
            ]);
        }
        
        // 🔄 LOGIQUE : Si l'abonnement est completed, le réouvrir avant d'ajouter le cours
        // Cela permet de gérer les cas où un cours est annulé puis un nouveau cours est pris
        if ($this->status === 'completed') {
            $this->status = 'active';
            \Log::info("🔄 Abonnement {$this->id} réouvert (completed -> active)", [
                'subscription_instance_id' => $this->id,
                'reason' => 'Nouveau cours ajouté après clôture',
                'lesson_id' => $lesson->id
            ]);
        }
        
        $this->lessons()->attach($lesson->id);
        $this->load('lessons');

        if ($this->est_legacy !== null) {
            $lesson->est_legacy = $this->est_legacy;
            $lesson->saveQuietly();
            \Log::info("🔄 Statut DCL/NDCL propagé de l'abonnement au cours", [
                'lesson_id' => $lesson->id,
                'subscription_instance_id' => $this->id,
                'est_legacy' => $this->est_legacy,
                'status' => $this->est_legacy ? 'NDCL' : 'DCL',
            ]);
        }

        if ($this->isDirty()) {
            $this->saveQuietly();
        }

        $this->recalculateLessonsUsed();
        $this->checkAndUpdateStatus();
        
        \Log::info("Cours {$lesson->id} consommé depuis l'abonnement {$this->id}", [
            'lesson_id' => $lesson->id,
            'subscription_instance_id' => $this->id,
            'lessons_used_after' => $this->lessons_used,
            'remaining_lessons' => $this->remaining_lessons,
            'is_first_lesson' => $isFirstLesson,
            'started_at' => $this->started_at
        ]);
    }

    /**
     * Propager le statut DCL/NDCL (est_legacy) aux cours associés
     */
    public function propagateEstLegacyToLessons()
    {
        if ($this->est_legacy === null) {
            return;
        }

        $lessons = $this->lessons()->get();
        $updatedCount = 0;

        foreach ($lessons as $lesson) {
            if ($lesson->est_legacy !== $this->est_legacy) {
                $lesson->est_legacy = $this->est_legacy;
                $lesson->saveQuietly();
                $updatedCount++;
            }
        }

        \Log::info("🔄 Statut DCL/NDCL propagé aux cours associés", [
            'subscription_instance_id' => $this->id,
            'est_legacy' => $this->est_legacy,
            'status' => $this->est_legacy ? 'NDCL' : 'DCL',
            'total_lessons' => $lessons->count(),
            'updated_lessons' => $updatedCount
        ]);

        return $updatedCount;
    }

    /**
     * Ajouter un élève à cet abonnement
     */
    public function addStudent(Student $student)
    {
        if (!$this->students()->where('student_id', $student->id)->exists()) {
            $this->students()->attach($student->id);
        }
    }

    /**
     * Retirer un élève de cet abonnement
     */
    public function removeStudent(Student $student)
    {
        $this->students()->detach($student->id);
    }

    /**
     * Trouver le bon abonnement actif pour un élève et un type de cours
     * Retourne l'abonnement actif le plus ancien (par date de création) qui a encore des cours disponibles
     * 
     * @param int $studentId ID de l'élève
     * @param int $courseTypeId ID du type de cours
     * @param int|null $clubId ID du club (optionnel, pour filtrer)
     * @return SubscriptionInstance|null
     */
    /**
     * Recalcule les instances liées à un cours annulé (pivot ou cancelled_subscription_instance_ids).
     */
    public static function recalculateForCancelledLesson(Lesson $lesson): void
    {
        $instanceIds = collect($lesson->cancelled_subscription_instance_ids ?? [])
            ->merge(
                self::whereHas('lessons', function ($query) use ($lesson) {
                    $query->where('lesson_id', $lesson->id);
                })->pluck('id')
            )
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->filter()
            ->values()
            ->all();

        foreach (self::whereIn('id', $instanceIds)->get() as $instance) {
            $instance->recalculateLessonsUsed();
        }
    }

    public static function findActiveSubscriptionForLesson(int $studentId, int $courseTypeId, ?int $clubId = null): ?self
    {
        // Récupérer tous les abonnements actifs de l'élève qui acceptent ce type de cours
        $query = self::where('status', 'active')
            ->whereHas('students', function ($q) use ($studentId) {
                $q->where('students.id', $studentId);
            })
            ->whereHas('subscription.template.courseTypes', function ($q) use ($courseTypeId) {
                $q->where('course_types.id', $courseTypeId);
            })
            ->with(['subscription.template.courseTypes'])
            ->orderBy('created_at', 'asc'); // Les plus anciens en premier

        // Filtrer par club si fourni
        if ($clubId !== null) {
            $query->whereHas('subscription', function ($q) use ($clubId) {
                if (Subscription::hasClubIdColumn()) {
                    $q->where('club_id', $clubId);
                } else {
                    $q->whereHas('template', function ($tq) use ($clubId) {
                        $tq->where('club_id', $clubId);
                    });
                }
            });
        }

        $instances = $query->get();

        // Parcourir les abonnements du plus ancien au plus récent
        foreach ($instances as $instance) {
            if ($instance->getRemainingAttachmentSlots() > 0) {
                return $instance;
            }
        }

        // Aucun abonnement disponible
        return null;
    }
}

