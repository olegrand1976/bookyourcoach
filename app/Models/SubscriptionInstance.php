<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

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
     * Nombre de cours restants
     */
    public function getRemainingLessonsAttribute()
    {
        $total = $this->subscription->total_available_lessons;
        return max(0, $total - $this->lessons_used);
    }

    /**
     * Pourcentage d'utilisation
     */
    public function getUsagePercentageAttribute()
    {
        $total = $this->subscription->total_available_lessons;
        if ($total === 0) return 0;
        return round(($this->lessons_used / $total) * 100, 1);
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
        return $this->students->pluck('user.name')->join(', ');
    }

    /**
     * Vérifier et mettre à jour le statut si nécessaire
     */
    public function checkAndUpdateStatus()
    {
        // Si tous les cours sont utilisés
        if ($this->lessons_used >= $this->subscription->total_available_lessons) {
            $this->status = 'completed';
            $this->save();
            return 'completed';
        }

        // Si la date d'expiration est dépassée
        if ($this->expires_at && Carbon::now()->isAfter($this->expires_at)) {
            $this->status = 'expired';
            $this->save();
            return 'expired';
        }

        return $this->status;
    }

    /**
     * Consommer un cours (incrémenter le compteur)
     */
    public function consumeLesson(Lesson $lesson)
    {
        // Vérifier qu'il reste des cours
        if ($this->remaining_lessons <= 0) {
            throw new \Exception('Aucun cours restant dans cet abonnement');
        }

        // Vérifier que le cours est bien du bon type
        $disciplineIds = $this->subscription->courseTypes()->pluck('disciplines.id')->toArray();
        if (!in_array($lesson->course_type_id, $disciplineIds)) {
            throw new \Exception('Ce cours n\'est pas inclus dans cet abonnement');
        }

        // Vérifier que l'élève fait partie de cet abonnement
        $studentIds = $this->students()->pluck('students.id')->toArray();
        if (!in_array($lesson->student_id, $studentIds)) {
            throw new \Exception('Cet élève ne fait pas partie de cet abonnement');
        }

        // Créer la liaison
        $this->lessons()->attach($lesson->id);
        
        // Incrémenter le compteur
        $this->increment('lessons_used');
        
        // Vérifier et mettre à jour le statut
        $this->checkAndUpdateStatus();
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

