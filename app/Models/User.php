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
 *     @OA\Property(property="email", type="string", format="email", example="john@example.com"),
 *     @OA\Property(property="role", type="string", enum={"admin", "teacher", "student"}, example="student"),
 *     @OA\Property(property="phone", type="string", nullable=true, example="+33123456789"),
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
        'email',
        'password',
        'role',
        'phone',
        'status',
        'is_active',
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
}
