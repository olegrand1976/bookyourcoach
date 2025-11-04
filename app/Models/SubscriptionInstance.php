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
    ];

    protected $casts = [
        'lessons_used' => 'integer',
        'started_at' => 'date',
        'expires_at' => 'date',
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
     * Calcule automatiquement lessons_used en comptant les cours réellement consommés
     * (exclut les cours annulés)
     */
    public function recalculateLessonsUsed(): void
    {
        // Compter directement dans la table subscription_lessons avec un JOIN sur lessons
        // pour être sûr d'avoir les données à jour (évite les problèmes de cache Eloquent)
        $consumedLessons = \Illuminate\Support\Facades\DB::table('subscription_lessons')
            ->join('lessons', 'subscription_lessons.lesson_id', '=', 'lessons.id')
            ->where('subscription_lessons.subscription_instance_id', $this->id)
            ->whereIn('lessons.status', ['pending', 'confirmed', 'completed'])
            ->where('lessons.status', '!=', 'cancelled')
            ->count();

        $oldValue = $this->lessons_used;
        
        // Log AVANT la mise à jour pour debug
        \Log::info("🔍 Recalcul lessons_used pour subscription_instance {$this->id}", [
            'old_lessons_used' => $oldValue,
            'new_calculated' => $consumedLessons,
            'will_update' => ($oldValue != $consumedLessons),
            'subscription_id' => $this->subscription_id,
            'subscription_instance_id' => $this->id
        ]);

        // Mettre à jour seulement si différent pour éviter les requêtes inutiles
        if ($this->lessons_used != $consumedLessons) {
            $this->lessons_used = $consumedLessons;
            $this->saveQuietly(); // saveQuietly() évite de déclencher les events
            
            \Log::info("✅ Lessons_used mis à jour pour subscription_instance {$this->id}", [
                'old_value' => $oldValue,
                'new_value' => $consumedLessons,
                'diff' => ($consumedLessons - $oldValue)
            ]);
            
            // Vérifier et mettre à jour le statut (mais éviter la récursion infinie)
            $this->checkAndUpdateStatus();
        } else {
            \Log::info("ℹ️ Aucune mise à jour nécessaire pour subscription_instance {$this->id} (déjà à jour)");
        }
    }

    /**
     * Nombre de cours restants
     */
    public function getRemainingLessonsAttribute()
    {
        // Recalculer avant de retourner pour être sûr d'avoir la valeur à jour
        $this->recalculateLessonsUsed();
        $total = $this->subscription->total_available_lessons;
        return max(0, $total - $this->lessons_used);
    }

    /**
     * Pourcentage d'utilisation
     */
    public function getUsagePercentageAttribute()
    {
        // Recalculer avant de calculer le pourcentage
        $this->recalculateLessonsUsed();
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
     */
    public function checkAndUpdateStatus()
    {
        // Ne pas recalculer ici pour éviter la récursion (recalculé ailleurs avant l'appel)
        
        // Si tous les cours sont utilisés
        if ($this->lessons_used >= $this->subscription->total_available_lessons) {
            if ($this->status !== 'completed') {
                $this->status = 'completed';
                $this->saveQuietly();
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

        // Vérifier qu'il reste des cours (recalculer d'abord pour avoir la valeur à jour)
        $this->recalculateLessonsUsed();
        if ($this->remaining_lessons <= 0) {
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

        // Créer la liaison dans subscription_lessons
        if (!$this->lessons()->where('lesson_id', $lesson->id)->exists()) {
            $this->lessons()->attach($lesson->id);
            
            // Forcer le rafraîchissement de la relation pour que le recalcul fonctionne
            $this->load('lessons');
        }
        
        // Recalculer automatiquement le compteur APRÈS l'attachement
        // Utiliser une requête directe pour s'assurer que le cours est bien dans la table
        $this->recalculateLessonsUsed();
        
        // Vérifier et mettre à jour le statut
        $this->checkAndUpdateStatus();
        
        \Log::info("Cours {$lesson->id} consommé depuis l'abonnement {$this->id}", [
            'lesson_id' => $lesson->id,
            'subscription_instance_id' => $this->id,
            'lessons_used_after' => $this->lessons_used,
            'remaining_lessons' => $this->remaining_lessons
        ]);
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
}

