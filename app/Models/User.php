<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
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

        // If it's already a string in YYYY-MM-DD format, return it directly
        if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return $value;
        }

        // If it's a Carbon instance or DateTime, format it as YYYY-MM-DD
        if ($value instanceof \Carbon\Carbon || $value instanceof \DateTime) {
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
