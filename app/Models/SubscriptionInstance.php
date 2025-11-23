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
        'started_at',
        'expires_at',
        'status',
        // Champs pour le calcul des commissions
        'est_legacy',
        'date_paiement',
        'montant',
        'teacher_id',
    ];

    protected $casts = [
        'lessons_used' => 'integer',
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
        // Compter directement dans la table subscription_lessons avec un JOIN sur lessons
        // pour être sûr d'avoir les données à jour (évite les problèmes de cache Eloquent)
        // ⚠️ IMPORTANT : Ne compter QUE les cours passés (dont la date/heure est passée)
        // Les cours futurs planifiés sont attachés mais ne sont pas encore comptabilisés
        // Ils seront comptabilisés automatiquement quand leur date/heure sera passée
        $now = Carbon::now();
        $consumedLessons = \Illuminate\Support\Facades\DB::table('subscription_lessons')
            ->join('lessons', 'subscription_lessons.lesson_id', '=', 'lessons.id')
            ->where('subscription_lessons.subscription_instance_id', $this->id)
            ->whereIn('lessons.status', ['pending', 'confirmed', 'completed'])
            ->where('lessons.status', '!=', 'cancelled')
            ->where('lessons.start_time', '<=', $now) // ⚠️ Seulement les cours passés
            ->count();
        
        // ⚠️ NOTE : 
        // - Les cours passés sont automatiquement consommés lors de l'attachement via consumeLesson()
        // - Les cours futurs planifiés sont attachés mais ne sont PAS encore comptabilisés dans lessons_used
        // - Ils seront comptabilisés automatiquement quand leur date/heure sera passée
        // - Cette méthode compte uniquement les cours passés pour avoir le total réellement utilisé

        $oldValue = $this->lessons_used;
        
        // Log AVANT la mise à jour pour debug
        \Log::info("🔍 Recalcul lessons_used pour subscription_instance {$this->id}", [
            'old_lessons_used' => $oldValue,
            'new_calculated' => $consumedLessons,
            'has_attached_lessons' => $consumedLessons > 0,
            'will_update' => ($oldValue != $consumedLessons && $consumedLessons > 0),
            'subscription_id' => $this->subscription_id,
            'subscription_instance_id' => $this->id
        ]);

        // ⚠️ LOGIQUE CRITIQUE : Ne compter que les cours passés dans lessons_used
        // 
        // RÈGLE : lessons_used doit refléter uniquement les cours passés (réellement consommés)
        // Les cours futurs planifiés sont attachés mais ne sont pas encore comptabilisés
        // 
        // Exception : Si oldValue est significativement supérieur à consumedLessons ET qu'il n'y a pas de cours attachés,
        // on considère que c'est une valeur manuelle et on la préserve
        // 
        // Exemple : Abonnement créé avec 5 cours utilisés manuellement (pas de cours attachés) → préserver 5
        //          Abonnement avec 30 cours utilisés mais seulement 10 cours passés → utiliser 10 (corriger)
        
        if ($consumedLessons > 0) {
            // Des cours passés sont attachés
            // Utiliser le comptage réel des cours passés
            if ($this->lessons_used != $consumedLessons) {
                $this->lessons_used = $consumedLessons;
                $this->saveQuietly();
                
                \Log::info("✅ Lessons_used mis à jour (cours passés uniquement) pour subscription_instance {$this->id}", [
                    'old_value' => $oldValue,
                    'consumed_lessons' => $consumedLessons,
                    'new_value' => $consumedLessons,
                    'note' => 'Mise à jour avec uniquement les cours passés'
                ]);
                
                $this->checkAndUpdateStatus();
            }
        } else {
            // Aucun cours passé attaché
            // Si oldValue est > 0 et qu'il n'y a pas de cours attachés du tout, préserver la valeur manuelle
            $totalAttachedLessons = \Illuminate\Support\Facades\DB::table('subscription_lessons')
                ->where('subscription_instance_id', $this->id)
                ->count();
            
            if ($totalAttachedLessons === 0 && $oldValue > 0) {
                // Aucun cours attaché et valeur manuelle → préserver
                \Log::info("🔒 Valeur manuelle préservée pour subscription_instance {$this->id}", [
                    'manual_value' => $oldValue,
                    'calculated_value' => $consumedLessons,
                    'reason' => 'Aucun cours attaché, préservation de la valeur manuelle'
                ]);
            } else {
                // Des cours sont attachés mais tous sont futurs → mettre à 0
                if ($this->lessons_used != 0) {
                    $this->lessons_used = 0;
                    $this->saveQuietly();
                    
                    \Log::info("✅ Lessons_used mis à 0 (seulement des cours futurs attachés) pour subscription_instance {$this->id}", [
                        'old_value' => $oldValue,
                        'total_attached_lessons' => $totalAttachedLessons,
                        'note' => 'Seulement des cours futurs attachés, lessons_used mis à 0'
                    ]);
                    
                    $this->checkAndUpdateStatus();
                }
            }
        }
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

        // Vérifier qu'il reste des cours
        // ⚠️ Ne pas recalculer ici pour préserver les valeurs manuelles
        // On utilise directement lessons_used (qui peut être une valeur manuelle)
        $total = $this->subscription->total_available_lessons;
        $remaining = max(0, $total - $this->lessons_used);
        if ($remaining <= 0) {
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

        // Vérifier que l'élève fait partie de cet abonnement
        $studentIds = $this->students()->pluck('students.id')->toArray();
        // Vérifier aussi via lesson_student (many-to-many)
        $lessonStudentIds = $lesson->students()->pluck('students.id')->toArray();
        // Vérifier aussi via student_id du cours (pour compatibilité)
        if ($lesson->student_id) {
            $lessonStudentIds[] = $lesson->student_id;
        }
        $allStudentIds = array_unique(array_merge($studentIds, $lessonStudentIds));
        
        // Vérifier si au moins un des élèves du cours est dans l'abonnement
        $hasValidStudent = false;
        foreach ($allStudentIds as $studentId) {
            if (in_array($studentId, $studentIds)) {
                $hasValidStudent = true;
                break;
            }
        }
        
        if (!$hasValidStudent && !empty($studentIds)) {
            throw new \Exception('Cet élève ne fait pas partie de cet abonnement');
        }

        // Vérifier que le cours n'est pas annulé
        if ($lesson->status === 'cancelled') {
            throw new \Exception('Un cours annulé ne peut pas être consommé depuis un abonnement');
        }

        // 📅 LOGIQUE : Mettre à jour started_at si c'est le premier cours réellement pris
        // La date de début doit être basée sur le premier cours, pas sur la création de l'abonnement
        $isFirstLesson = $this->lessons()->count() === 0;
        $startedAtChanged = false;
        if ($isFirstLesson && $lesson->start_time) {
            $lessonDate = Carbon::parse($lesson->start_time)->startOfDay();
            // Pour le premier cours, toujours mettre à jour started_at avec la date du cours
            // (peu importe si c'est dans le passé ou le futur)
            $oldStartedAt = $this->started_at;
            $this->started_at = $lessonDate;
            $startedAtChanged = true;
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
        
        // ⚠️ LOGIQUE CRITIQUE : Vérifier si le cours est déjà attaché AVANT l'attachement
        // pour savoir si on doit incrémenter ou recalculer
        $wasAlreadyAttached = $this->lessons()->where('lesson_id', $lesson->id)->exists();
        
        // Créer la liaison dans subscription_lessons
        if (!$wasAlreadyAttached) {
            $this->lessons()->attach($lesson->id);
            
            // Forcer le rafraîchissement de la relation
            $this->load('lessons');
            
            // 🔄 PROPAGATION DCL/NDCL : Propager le statut est_legacy de l'abonnement au cours
            if ($this->est_legacy !== null) {
                $lesson->est_legacy = $this->est_legacy;
                $lesson->saveQuietly();
                \Log::info("🔄 Statut DCL/NDCL propagé de l'abonnement au cours", [
                    'lesson_id' => $lesson->id,
                    'subscription_instance_id' => $this->id,
                    'est_legacy' => $this->est_legacy,
                    'status' => $this->est_legacy ? 'NDCL' : 'DCL'
                ]);
            }
            
            // ⚠️ LOGIQUE CRITIQUE : Consommer l'abonnement si le cours est passé
            // Si le cours est dans le futur, on l'attache mais on ne consomme pas encore
            $lessonStartTime = Carbon::parse($lesson->start_time);
            $isPastLesson = $lessonStartTime->isPast();
            
            if (!$isPastLesson) {
                // Cours futur : juste attacher, ne pas consommer
                \Log::info("📅 Cours futur attaché à l'abonnement (non consommé)", [
                    'lesson_id' => $lesson->id,
                    'lesson_start_time' => $lesson->start_time,
                    'subscription_instance_id' => $this->id,
                    'note' => 'Le cours sera consommé automatiquement quand sa date/heure sera passée'
                ]);
                // Recalculer quand même pour mettre à jour les autres valeurs si nécessaire
                $this->recalculateLessonsUsed();
                return;
            }
            
            // ✅ COURS PASSÉ : Consommer immédiatement l'abonnement
            \Log::info("📅 Cours passé détecté - consommation immédiate de l'abonnement", [
                'lesson_id' => $lesson->id,
                'lesson_start_time' => $lesson->start_time,
                'subscription_instance_id' => $this->id,
                'is_past' => true,
                'note' => 'Cours planifié dans le passé, consommation immédiate'
            ]);
            
            // ⚠️ LOGIQUE CRITIQUE : Incrémenter directement lessons_used au lieu de recalculer
            // Cela préserve la valeur manuelle initiale
            // Exemple : 5 (manuel) + 1 (nouveau cours) = 6 (et non 1)
            $oldLessonsUsed = $this->lessons_used;
            $this->lessons_used = $this->lessons_used + 1;
            
            \Log::info("➕ Cours {$lesson->id} ajouté à l'abonnement {$this->id} (incrémentation directe)", [
                'lesson_id' => $lesson->id,
                'subscription_instance_id' => $this->id,
                'old_lessons_used' => $oldLessonsUsed,
                'new_lessons_used' => $this->lessons_used,
                'calculation' => "{$oldLessonsUsed} + 1 = {$this->lessons_used}",
                'note' => 'Incrémentation directe pour préserver la valeur manuelle'
            ]);
        } else {
            // Cours déjà attaché : mettre à jour le statut DCL/NDCL si nécessaire
            if ($this->est_legacy !== null && $lesson->est_legacy !== $this->est_legacy) {
                $lesson->est_legacy = $this->est_legacy;
                $lesson->saveQuietly();
                \Log::info("🔄 Statut DCL/NDCL mis à jour pour le cours déjà attaché", [
                    'lesson_id' => $lesson->id,
                    'subscription_instance_id' => $this->id,
                    'est_legacy' => $this->est_legacy,
                    'status' => $this->est_legacy ? 'NDCL' : 'DCL'
                ]);
            }
            // Juste recalculer pour vérifier la cohérence
            \Log::info("ℹ️ Cours {$lesson->id} déjà attaché à l'abonnement {$this->id}, recalcul...");
            $this->recalculateLessonsUsed();
        }
        
        // Sauvegarder les modifications (started_at, status, lessons_used)
        if ($this->isDirty()) {
            $this->saveQuietly();
        }
        
        // Vérifier et mettre à jour le statut (peut passer en completed si plein)
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
            // ⚠️ Ne pas recalculer ici pour préserver les valeurs manuelles
            // Utiliser directement lessons_used (qui peut être une valeur manuelle)
            // Le recalcul se fera automatiquement quand des cours seront attachés
            $total = $instance->subscription->total_available_lessons;
            $remaining = max(0, $total - $instance->lessons_used);
            
            // Vérifier qu'il reste des cours disponibles
            if ($remaining > 0) {
                return $instance;
            }
        }

        // Aucun abonnement disponible
        return null;
    }
}

