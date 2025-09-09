<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * @OA\Schema(
 *     schema="User",
 *     type="object",
 *     title="User",
 *     description="Modèle utilisateur",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="John Doe"),
 *     @OA\Property(property="first_name", type="string", example="John"),
 *     @OA\Property(property="last_name", type="string", example="Doe"),
 *     @OA\Property(property="email", type="string", format="email", example="john@example.com"),
 *     @OA\Property(property="role", type="string", enum={"admin", "teacher", "student", "club"}, example="student"),
 *     @OA\Property(property="phone", type="string", nullable=true, example="+33123456789"),
 *     @OA\Property(property="street", type="string", nullable=true, example="Rue de la Paix"),
 *     @OA\Property(property="street_number", type="string", nullable=true, example="123"),
 *     @OA\Property(property="postal_code", type="string", nullable=true, example="1000"),
 *     @OA\Property(property="city", type="string", nullable=true, example="Bruxelles"),
 *     @OA\Property(property="country", type="string", nullable=true, example="Belgium"),
 *     @OA\Property(property="birth_date", type="string", format="date", nullable=true, example="1990-01-01"),
 *     @OA\Property(property="status", type="string", example="active"),
 *     @OA\Property(property="email_verified_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    const ROLE_ADMIN = 'admin';
    const ROLE_TEACHER = 'teacher';
    const ROLE_STUDENT = 'student';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'first_name',
        'last_name',
        'email',
        'password',
        'role',
        'phone',
        'street',
        'street_number',
        'postal_code',
        'city',
        'country',
        'birth_date',
        'status',
        'is_active',
        'qr_code',
        'qr_code_generated_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
        'qr_code_generated_at' => 'datetime',
        ];
    }

    /**
     * Check if user has specific role
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
     * Check if user is teacher (has teacher profile)
     */
    public function isTeacher(): bool
    {
        return $this->hasRole(self::ROLE_TEACHER) || $this->teacher()->exists();
    }

    /**
     * Check if user is student (has student profile)
     */
    public function isStudent(): bool
    {
        return $this->hasRole(self::ROLE_STUDENT) || $this->student()->exists();
    }

    /**
     * Check if user can act as teacher (admin or has teacher profile)
     */
    public function canActAsTeacher(): bool
    {
        return $this->isAdmin() || $this->teacher()->exists();
    }

    /**
     * Check if user can act as student (admin or has student profile)
     */
    public function canActAsStudent(): bool
    {
        return $this->isAdmin() || $this->student()->exists();
    }

    /**
     * Get the user's profile
     */
    public function profile()
    {
        return $this->hasOne(Profile::class);
    }

    /**
     * Get teacher profile if user is teacher
     */
    public function teacher()
    {
        return $this->hasOne(Teacher::class);
    }

    /**
     * Get student profile if user is student
     */
    public function student()
    {
        return $this->hasOne(Student::class);
    }

    /**
     * Get clubs associated with this user
     */
    public function clubs()
    {
        return $this->belongsToMany(Club::class, 'club_user')
            ->withPivot('role', 'is_admin', 'joined_at')
            ->withTimestamps();
    }

    /**
     * Check if user is club manager
     */
    public function isClub(): bool
    {
        return $this->hasRole('club') || $this->clubs()->exists();
    }
}
