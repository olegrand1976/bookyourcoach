<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

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
        'birth_date' => 'date',
        'experience_start_date' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * Set the birth_date attribute.
     * Force the date to be saved as UTC midnight to avoid timezone shift issues.
     */
    public function setBirthDateAttribute($value)
    {
        if (empty($value) || $value === null) {
            $this->attributes['birth_date'] = null;
            return;
        }

        // If it's already a Carbon instance, extract the date part
        if ($value instanceof \Carbon\Carbon) {
            $dateString = $value->format('Y-m-d');
        } elseif (is_string($value)) {
            // Extract just the date part (YYYY-MM-DD) from string
            $dateString = substr($value, 0, 10);
        } else {
            $dateString = $value;
        }

        // Create a Carbon date at UTC midnight to avoid timezone conversion issues
        // This ensures the date is stored exactly as provided (no day shift)
        try {
            $date = \Carbon\Carbon::createFromFormat('Y-m-d', $dateString, 'UTC')
                ->startOfDay()
                ->setTimezone('UTC');
            
            $this->attributes['birth_date'] = $date->format('Y-m-d');
        } catch (\Exception $e) {
            // Fallback: try to parse the value directly
            \Log::warning('User::setBirthDateAttribute - Error parsing date', [
                'value' => $value,
                'error' => $e->getMessage()
            ]);
            $this->attributes['birth_date'] = $value;
        }
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
}
