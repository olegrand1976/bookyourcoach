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
}
