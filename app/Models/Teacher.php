<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Teacher extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'club_id',
        'specialties',
        'experience_years',
        'certifications',
        'hourly_rate',
        'bio',
        'is_available',
        'max_travel_distance',
        'preferred_locations',
        'stripe_account_id',
        'rating',
        'total_lessons',
    ];

    protected $casts = [
        'specialties' => 'array',
        'certifications' => 'array',
        'preferred_locations' => 'array',
        'hourly_rate' => 'decimal:2',
        'rating' => 'decimal:2',
        'is_available' => 'boolean',
    ];

    /**
     * Get the user that owns the teacher profile
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the clubs that the teacher belongs to
     */
    public function clubs()
    {
        return $this->belongsToMany(Club::class, 'club_teachers')
                    ->withPivot(['allowed_disciplines', 'restricted_disciplines', 'hourly_rate', 'is_active', 'joined_at'])
                    ->withTimestamps();
    }

    /**
     * Get the disciplines that the teacher can teach
     */
    public function disciplines()
    {
        return $this->belongsToMany(Discipline::class, 'teacher_disciplines')
                    ->withPivot(['level', 'certifications', 'is_primary'])
                    ->withTimestamps();
    }

    /**
     * Get the primary discipline
     */
    public function primaryDiscipline()
    {
        return $this->disciplines()->wherePivot('is_primary', true)->first();
    }

    /**
     * Get the teacher's skills
     */
    public function skills()
    {
        return $this->hasMany(TeacherSkill::class);
    }

    /**
     * Get the teacher's certifications
     */
    public function certifications()
    {
        return $this->hasMany(TeacherCertification::class);
    }

    /**
     * Get the teacher's availabilities
     */
    public function availabilities()
    {
        return $this->hasMany(Availability::class);
    }

    /**
     * Get the teacher's lessons
     */
    public function lessons()
    {
        return $this->hasMany(Lesson::class);
    }

    /**
     * Get the teacher's course types
     */
    public function courseTypes()
    {
        return $this->belongsToMany(CourseType::class, 'teacher_course_types');
    }

    /**
     * Get the teacher's payouts
     */
    public function payouts()
    {
        return $this->hasMany(Payout::class);
    }

    /**
     * Get the teacher's time blocks
     */
    public function timeBlocks()
    {
        return $this->hasMany(TimeBlock::class);
    }

    /**
     * Get the teacher's contracts
     */
    public function contracts()
    {
        return $this->hasMany(TeacherContract::class);
    }

    /**
     * Get active contracts for this teacher
     */
    public function activeContracts()
    {
        return $this->contracts()->where('is_active', true);
    }

    /**
     * Get course assignments for this teacher
     */
    public function courseAssignments()
    {
        return $this->hasMany(CourseAssignment::class);
    }

    /**
     * Get active course assignments for this teacher
     */
    public function activeCourseAssignments()
    {
        return $this->courseAssignments()->whereIn('status', ['assigned', 'confirmed']);
    }

    /**
     * Get teacher's contract for a specific club
     */
    public function getContractForClub($clubId)
    {
        return $this->contracts()
            ->where('club_id', $clubId)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Check if teacher can teach at a specific time and date
     */
    public function canTeachAt($clubId, $dayOfWeek, $startTime, $endTime, $date = null)
    {
        $contract = $this->getContractForClub($clubId);
        
        if (!$contract) {
            return false;
        }

        // Vérifier les contraintes du contrat
        if (!$contract->canTeachOnDay($dayOfWeek)) {
            return false;
        }

        if (!$contract->canTeachAtTime($startTime, $endTime)) {
            return false;
        }

        if ($date && !$contract->isActiveForDate($date)) {
            return false;
        }

        // Vérifier les heures max par semaine
        if ($date && $contract->hasReachedMaxHoursForWeek($date)) {
            return false;
        }

        return true;
    }

    /**
     * Get the recurring slots for this teacher (blocage long terme).
     */
    public function recurringSlots()
    {
        return $this->hasMany(RecurringSlot::class);
    }

    /**
     * Accessor pour calculer automatiquement les années d'expérience
     * à partir de experience_start_date de l'utilisateur ou de la date de création du profil
     */
    public function getExperienceYearsAttribute($value)
    {
        // Si on a déjà une valeur stockée et qu'il n'y a pas de date de début, retourner la valeur stockée
        if ($value !== null && $value > 0) {
            // Si l'utilisateur a une date de début d'expérience, recalculer dynamiquement
            if ($this->user && $this->user->experience_start_date) {
                $startDate = \Carbon\Carbon::parse($this->user->experience_start_date);
                return max(0, $startDate->diffInYears(now()));
            }
            return $value;
        }

        // Si l'utilisateur a une date de début d'expérience, calculer à partir de celle-ci
        if ($this->user && $this->user->experience_start_date) {
            $startDate = \Carbon\Carbon::parse($this->user->experience_start_date);
            return max(0, $startDate->diffInYears(now()));
        }

        // Sinon, utiliser la date de création du profil Teacher comme référence
        if ($this->created_at) {
            $startDate = \Carbon\Carbon::parse($this->created_at);
            return max(0, $startDate->diffInYears(now()));
        }

        return 0;
    }
}
