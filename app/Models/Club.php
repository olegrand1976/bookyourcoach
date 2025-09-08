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
        'description',
        'email',
        'phone',
        'address',
        'city',
        'postal_code',
        'country',
        'website',
        'facilities',
        'disciplines',
        'max_students',
        'subscription_price',
        'is_active',
        'terms_and_conditions',
        'activity_type_id',
        'seasonal_variation',
        'weather_dependency'
    ];

    protected $casts = [
        'facilities' => 'array',
        'disciplines' => 'array',
        'subscription_price' => 'decimal:2',
        'is_active' => 'boolean',
        'max_students' => 'integer',
        'seasonal_variation' => 'decimal:2',
        'weather_dependency' => 'boolean'
    ];

    protected $attributes = [
        'country' => 'France',
        'is_active' => true
    ];

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
        return $this->users()->wherePivot('role', 'teacher');
    }

    public function students()
    {
        return $this->users()->wherePivot('role', 'student');
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
