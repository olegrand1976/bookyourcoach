<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Auth\Passwords\CanResetPassword;
use App\Notifications\ResetPasswordNotification;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, CanResetPassword;

    /**
     * Role constants
     */
    public const ROLE_ADMIN = 'admin';
    public const ROLE_TEACHER = 'teacher';
    public const ROLE_STUDENT = 'student';
    public const ROLE_CLUB = 'club';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'first_name',
        'last_name',
        'email',
        'password',
        'role',
        'phone',
        'birth_date',
        'niss',
        'address',
        'street',
        'street_number',
        'street_box',
        'postal_code',
        'city',
        'country',
        'bank_account_number',
        'experience_start_date',
        'is_active',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        // birth_date n'est PAS casté en 'date' pour éviter les problèmes de fuseau horaire
        // Il est géré manuellement via setBirthDateAttribute et getBirthDateAttribute
        'experience_start_date' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * Set the birth_date attribute.
     * Force the date to be saved as a pure date string (YYYY-MM-DD) without timezone conversion.
     */
    public function setBirthDateAttribute($value)
    {
        if (empty($value) || $value === null) {
            $this->attributes['birth_date'] = null;
            return;
        }

        // Extract just the date part (YYYY-MM-DD) from any format
        $dateString = null;
        
        if ($value instanceof \Carbon\Carbon) {
            $dateString = $value->format('Y-m-d');
        } elseif (is_string($value)) {
            // Extract just the date part (YYYY-MM-DD) from string
            // Handle both "YYYY-MM-DD" and "YYYY-MM-DDTHH:MM:SS..." formats
            if (strpos($value, 'T') !== false) {
                $dateString = substr($value, 0, 10);
            } elseif (strlen($value) >= 10) {
                $dateString = substr($value, 0, 10);
            } else {
                $dateString = $value;
            }
        } else {
            $dateString = $value;
        }

        // Validate the date format
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateString)) {
            // Store directly as string to avoid any Carbon timezone conversion
            // This ensures the date is stored exactly as provided (no day shift)
            $this->attributes['birth_date'] = $dateString;
            
            \Log::info('User::setBirthDateAttribute - Date sauvegardée:', [
                'input' => $value,
                'extracted' => $dateString,
                'stored' => $this->attributes['birth_date']
            ]);
        } else {
            \Log::warning('User::setBirthDateAttribute - Format de date invalide', [
                'value' => $value,
                'extracted' => $dateString
            ]);
            $this->attributes['birth_date'] = $dateString;
        }
    }

    /**
     * Get the birth_date attribute.
     * Return as a pure date string (YYYY-MM-DD) without timezone conversion.
     */
    public function getBirthDateAttribute($value)
    {
        if (empty($value)) {
            return null;
        }

        // If it's already a string, return it directly (may be YYYY-MM-DD or YYYY-MM-DD HH:MM:SS)
        if (is_string($value)) {
            // Extract just the date part (YYYY-MM-DD) if it contains time
            if (strpos($value, ' ') !== false || strpos($value, 'T') !== false) {
                return substr($value, 0, 10);
            }
            // If it's already in YYYY-MM-DD format, return it directly
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                return $value;
            }
            // If it's a valid date string but in another format, try to parse it
            try {
                $date = \Carbon\Carbon::parse($value);
                return $date->format('Y-m-d');
            } catch (\Exception $e) {
                // If parsing fails, return the original value
                return $value;
            }
        }

        // If it's a Carbon instance or DateTime, format it as YYYY-MM-DD
        if ($value instanceof \Carbon\Carbon || $value instanceof \DateTime || $value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        return $value;
    }

    /**
     * Get the clubs associated with this user (for club managers).
     */
    public function clubs()
    {
        return $this->belongsToMany(Club::class, 'club_user')
            ->withPivot('role', 'is_admin', 'joined_at')
            ->withTimestamps();
    }

    /**
     * Get the first club associated with this user (helper method).
     */
    public function getFirstClub()
    {
        return $this->clubs()->first();
    }

    /**
     * Get the teacher profile for this user.
     */
    public function teacher()
    {
        return $this->hasOne(Teacher::class);
    }

    /**
     * Get the student profile for this user.
     */
    public function student()
    {
        return $this->hasOne(Student::class);
    }

    /**
     * All student profiles attached to this user (same email / compte parent).
     * hasOne(student) is not sufficient when several rows share the same user_id.
     */
    public function students()
    {
        return $this->hasMany(Student::class);
    }

    /**
     * Get the profile for this user.
     */
    public function profile()
    {
        return $this->hasOne(Profile::class);
    }

    /**
     * Envoyer la notification de réinitialisation de mot de passe
     * Surcharge pour utiliser l'URL du frontend au lieu de la route Laravel
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    /**
     * Check if user has a specific role
     */
    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    /**
     * Get or create student profile for this user
     * Returns the student profile, creating it if it doesn't exist
     */
    public function getOrCreateStudent()
    {
        if ($this->role !== 'student') {
            return null;
        }

        if (!$this->student) {
            $this->student = \App\Models\Student::create([
                'user_id' => $this->id,
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'phone' => $this->phone,
            ]);

            // Si l'étudiant a des clubs dans la table pivot, définir le premier comme club principal
            $firstClub = \Illuminate\Support\Facades\DB::table('club_students')
                ->where('student_id', $this->student->id)
                ->where('is_active', true)
                ->orderBy('joined_at', 'asc')
                ->first();

            if ($firstClub && !$this->student->club_id) {
                $this->student->club_id = $firstClub->club_id;
                $this->student->save();
            }
        }

        return $this->student;
    }

    /**
     * Get all linked students for this user.
     * Méthode pour récupérer les étudiants liés via le compte student.
     * Agrège les liens famille pour chaque profil élève ayant ce user_id (plusieurs abonnés / fiches).
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\Student>
     */
    public function getLinkedStudents()
    {
        if ($this->role !== self::ROLE_STUDENT) {
            return collect();
        }

        $profiles = Student::query()->where('user_id', $this->id)->get();
        if ($profiles->isEmpty()) {
            return collect();
        }

        $linked = collect();
        foreach ($profiles as $student) {
            $linked = $linked->merge($student->getAllLinkedStudents());
        }

        return $linked->unique('id')->values();
    }

    /**
     * Max bénéficiaires sur une instance pour étendre le foyer via l'abonnement (évite fuites si pivot mal configurée).
     */
    private const HOUSEHOLD_SUBSCRIPTION_INSTANCE_MAX_STUDENTS = 24;

    /**
     * IDs de tous les élèves « du foyer » :
     * - profils students avec ce user_id ;
     * - élèves liés student_family_links ;
     * - co-bénéficiaires sur les mêmes instances d'abonnement (abonnement familial sans lien famille explicite).
     *
     * @return array<int>
     */
    public function getHouseholdStudentIds(): array
    {
        if ($this->role !== self::ROLE_STUDENT) {
            return [];
        }

        $profiles = Student::query()->where('user_id', $this->id)->get();
        if ($profiles->isEmpty()) {
            return [];
        }

        $all = $profiles->pluck('id')->map(fn ($id) => (int) $id);
        foreach ($profiles as $student) {
            $all = $all->merge($student->getAllLinkedStudents()->pluck('id')->map(fn ($id) => (int) $id));
        }

        $seedIds = $all->unique()->values()->all();
        if ($seedIds !== []) {
            $all = $this->mergeCoBeneficiaryStudentIdsFromSharedSubscriptions($all, $seedIds);
        }

        return $all->unique()->sort()->values()->all();
    }

    /**
     * Ajoute les élèves présents sur les mêmes subscription_instances que les IDs de départ (ex. pack familial).
     *
     * @param  \Illuminate\Support\Collection<int, int>  $all
     * @param  array<int>  $seedIds
     * @return \Illuminate\Support\Collection<int, int>
     */
    private function mergeCoBeneficiaryStudentIdsFromSharedSubscriptions(\Illuminate\Support\Collection $all, array $seedIds): \Illuminate\Support\Collection
    {
        $instanceIds = DB::table('subscription_instance_students')
            ->whereIn('student_id', $seedIds)
            ->distinct()
            ->pluck('subscription_instance_id');

        if ($instanceIds->isEmpty()) {
            return $all;
        }

        $max = self::HOUSEHOLD_SUBSCRIPTION_INSTANCE_MAX_STUDENTS;
        $eligibleInstanceIds = collect();
        foreach ($instanceIds as $instanceId) {
            $count = (int) DB::table('subscription_instance_students')
                ->where('subscription_instance_id', $instanceId)
                ->count();
            if ($count <= $max) {
                $eligibleInstanceIds->push($instanceId);
            }
        }

        if ($eligibleInstanceIds->isEmpty()) {
            return $all;
        }

        $coIds = DB::table('subscription_instance_students')
            ->whereIn('subscription_instance_id', $eligibleInstanceIds->all())
            ->pluck('student_id');

        return $all->merge($coIds->map(fn ($id) => (int) $id));
    }

    /**
     * Check if user is admin
     */
    public function isAdmin(): bool
    {
        return $this->hasRole(self::ROLE_ADMIN);
    }

    /**
     * Check if user is teacher
     */
    public function isTeacher(): bool
    {
        return $this->hasRole(self::ROLE_TEACHER);
    }

    /**
     * Check if user is student
     */
    public function isStudent(): bool
    {
        return $this->hasRole(self::ROLE_STUDENT);
    }

    /**
     * Check if user is club
     */
    public function isClub(): bool
    {
        return $this->hasRole(self::ROLE_CLUB);
    }

    /**
     * Check if user can act as teacher (has teacher role)
     */
    public function canActAsTeacher(): bool
    {
        return $this->isTeacher();
    }

    /**
     * Check if user can act as student (has student role)
     */
    public function canActAsStudent(): bool
    {
        return $this->isStudent();
    }
}
