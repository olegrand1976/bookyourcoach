<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @OA\Schema(
 *     schema="Club",
 *     type="object",
 *     title="Club",
 *     description="Modèle de club équestre",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Club Équestre de Paris"),
 *     @OA\Property(property="description", type="string", nullable=true),
 *     @OA\Property(property="email", type="string", format="email", example="contact@club-paris.fr"),
 *     @OA\Property(property="phone", type="string", nullable=true, example="01 23 45 67 89"),
 *     @OA\Property(property="address", type="string", nullable=true),
 *     @OA\Property(property="city", type="string", nullable=true, example="Paris"),
 *     @OA\Property(property="postal_code", type="string", nullable=true, example="75001"),
 *     @OA\Property(property="country", type="string", example="France"),
 *     @OA\Property(property="website", type="string", nullable=true),
 *     @OA\Property(property="facilities", type="array", nullable=true, @OA\Items(type="string")),
 *     @OA\Property(property="disciplines", type="array", nullable=true, @OA\Items(type="string")),
 *     @OA\Property(property="max_students", type="integer", nullable=true),
 *     @OA\Property(property="subscription_price", type="number", format="float", nullable=true),
 *     @OA\Property(property="is_active", type="boolean", example=true),
 *     @OA\Property(property="terms_and_conditions", type="string", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class Club extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'company_number',
        'legal_representative_name',
        'legal_representative_role',
        'insurance_rc_company',
        'insurance_rc_policy_number',
        'insurance_additional_company',
        'insurance_additional_policy_number',
        'insurance_additional_details',
        'expense_reimbursement_type',
        'expense_reimbursement_details',
        'description',
        'email',
        'phone',
        'street',
        'street_number',
        'street_box',
        'address',
        'city',
        'postal_code',
        'country',
        'website',
        'facilities',
        'disciplines',
        'max_students',
        'subscription_price',
        'default_subscription_total_lessons',
        'default_subscription_free_lessons',
        'default_subscription_price',
        'default_subscription_validity_value',
        'default_subscription_validity_unit',
        'is_active',
        'terms_and_conditions',
        'activity_type_id',
        'seasonal_variation',
        'weather_dependency',
        'qr_code',
        'qr_code_generated_at'
    ];

    protected $casts = [
        'facilities' => 'array',
        'disciplines' => 'array',
        'subscription_price' => 'decimal:2',
        'default_subscription_price' => 'decimal:2',
        'is_active' => 'boolean',
        'max_students' => 'integer',
        'default_subscription_total_lessons' => 'integer',
        'default_subscription_free_lessons' => 'integer',
        'default_subscription_validity_value' => 'integer',
        'seasonal_variation' => 'decimal:2',
        'weather_dependency' => 'boolean',
        'qr_code_generated_at' => 'datetime'
    ];

    protected $attributes = [
        'country' => 'France',
        'is_active' => true
    ];

    /**
     * Boot method to automatically build the full address
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($club) {
            // Construire l'adresse complète automatiquement
            $addressParts = array_filter([
                $club->street,
                $club->street_number,
                $club->street_box
            ]);
            $club->address = implode(' ', $addressParts);
        });
    }

    /**
     * Get the recurring slots for this club (blocage long terme).
     */
    public function recurringSlots()
    {
        return $this->hasMany(RecurringSlot::class);
    }

    /**
     * Get the full address as a single string
     */
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->street,
            $this->street_number,
            $this->street_box,
            $this->postal_code,
            $this->city,
            $this->country
        ]);

        return implode(', ', $parts);
    }

    // Relations
    public function users()
    {
        return $this->belongsToMany(User::class, 'club_user')
            ->withPivot('role', 'is_admin', 'joined_at')
            ->withTimestamps();
    }

    public function admins()
    {
        return $this->users()->wherePivot('is_admin', true);
    }

    public function teachers()
    {
        return $this->belongsToMany(Teacher::class, 'club_teachers')
                    ->withPivot(['allowed_disciplines', 'restricted_disciplines', 'hourly_rate', 'is_active', 'joined_at', 'contract_type'])
                    ->withTimestamps();
    }

    public function students()
    {
        return $this->belongsToMany(Student::class, 'club_students')
                    ->withPivot(['level', 'goals', 'medical_info', 'preferred_disciplines', 'is_active', 'joined_at'])
                    ->withTimestamps();
    }

    /**
     * Get active teachers for this club
     */
    public function activeTeachers()
    {
        return $this->teachers()->wherePivot('is_active', true);
    }

    /**
     * Get active students for this club
     */
    public function activeStudents()
    {
        return $this->students()->wherePivot('is_active', true);
    }

    public function activityType()
    {
        return $this->belongsTo(ActivityType::class);
    }

    public function activityTypes()
    {
        return $this->belongsToMany(ActivityType::class, 'club_activity_types');
    }

    public function disciplines()
    {
        return $this->belongsToMany(Discipline::class, 'club_disciplines');
    }

    public function cashRegisters()
    {
        return $this->hasMany(CashRegister::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function customSpecialties()
    {
        return $this->hasMany(ClubCustomSpecialty::class);
    }

    /**
     * Get the club facilities
     */
    public function clubFacilities()
    {
        return $this->hasMany(ClubFacility::class);
    }

    /**
     * Get active club facilities
     */
    public function activeFacilities()
    {
        return $this->clubFacilities()->where('is_active', true);
    }

    /**
     * Get the course slots for this club
     */
    public function courseSlots()
    {
        return $this->hasMany(CourseSlot::class);
    }

    /**
     * Get active course slots for this club
     */
    public function activeCourseSlots()
    {
        return $this->courseSlots()->where('is_active', true);
    }

    /**
     * Get teacher contracts for this club
     */
    public function teacherContracts()
    {
        return $this->hasMany(TeacherContract::class);
    }

    /**
     * Get active teacher contracts for this club
     */
    public function activeTeacherContracts()
    {
        return $this->teacherContracts()->where('is_active', true);
    }

    /**
     * Get course assignments for this club
     */
    public function courseAssignments()
    {
        return $this->hasManyThrough(CourseAssignment::class, CourseSlot::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCity($query, $city)
    {
        return $query->where('city', $city);
    }
}
