<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Teacher extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        // Assigner automatiquement une couleur lors de la création si aucune n'est définie
        static::created(function (Teacher $teacher) {
            // Vérifier si la colonne color existe avant d'essayer de l'utiliser
            if (\Illuminate\Support\Facades\Schema::hasColumn('teachers', 'color')) {
                if (!$teacher->color) {
                    $teacher->assignColorFromPalette();
                }
            }
        });
    }

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
        'color',
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

    /**
     * Generate a pastel color based on teacher ID
     * Utilisé pour générer une couleur si aucune n'est définie
     */
    public function generateColor(): string
    {
        // Générer une couleur pastel unique basée sur l'ID de l'enseignant
        // Utiliser un hash de l'ID pour garantir la cohérence
        $hash = crc32($this->id . 'teacher_color');
        
        // Générer des valeurs RGB pastel (150-255 pour avoir des couleurs claires)
        $r = 150 + (abs($hash) % 105); // 150-255
        $g = 150 + (abs($hash >> 8) % 105); // 150-255
        $b = 150 + (abs($hash >> 16) % 105); // 150-255
        
        // Convertir en hexadécimal
        return sprintf('#%02X%02X%02X', $r, $g, $b);
    }

    /**
     * Get a pastel color palette (20 couleurs pastel prédéfinies)
     * Utilisé pour assigner des couleurs aux enseignants
     */
    public static function getPastelColorPalette(): array
    {
        return [
            '#FFB6C1', // Light Pink
            '#FFD700', // Gold
            '#98FB98', // Pale Green
            '#87CEEB', // Sky Blue
            '#DDA0DD', // Plum
            '#F0E68C', // Khaki
            '#FFA07A', // Light Salmon
            '#20B2AA', // Light Sea Green
            '#FFB347', // Pastel Orange
            '#B0E0E6', // Powder Blue
            '#FFCCCB', // Light Red
            '#E6E6FA', // Lavender
            '#F5DEB3', // Wheat
            '#AFEEEE', // Pale Turquoise
            '#FFDAB9', // Peach Puff
            '#D8BFD8', // Thistle
            '#F0F8FF', // Alice Blue
            '#FFF8DC', // Cornsilk
            '#E0FFFF', // Light Cyan
            '#FFE4E1', // Misty Rose
        ];
    }

    /**
     * Assign a color from the palette if not already set
     */
    public function assignColorFromPalette(): void
    {
        // Vérifier si la colonne color existe
        if (!\Illuminate\Support\Facades\Schema::hasColumn('teachers', 'color')) {
            return; // Colonne n'existe pas encore, ne rien faire
        }
        
        if ($this->color && !$this->isDirty('color')) {
            return; // Déjà assignée
        }

        $palette = self::getPastelColorPalette();
        
        // Si l'enseignant a un club_id, chercher les couleurs utilisées dans ce club
        // Sinon, chercher globalement
        $usedColorsQuery = self::whereNotNull('color');
        if ($this->club_id) {
            $usedColorsQuery->where('club_id', $this->club_id);
        }
        $usedColors = $usedColorsQuery->pluck('color')->toArray();

        // Trouver la première couleur disponible dans la palette
        foreach ($palette as $color) {
            if (!in_array($color, $usedColors)) {
                $this->color = $color;
                $this->save();
                return;
            }
        }

        // Si toutes les couleurs sont utilisées, générer une couleur unique basée sur l'ID
        $this->color = $this->generateColor();
        $this->save();
    }
}
