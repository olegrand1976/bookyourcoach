<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @OA\Schema(
 *     schema="ClubFacility",
 *     type="object",
 *     title="Club Facility",
 *     description="Installation spécifique d'un club équestre",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="club_id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Manège principal"),
 *     @OA\Property(property="type", type="string", example="manège"),
 *     @OA\Property(property="description", type="string", nullable=true),
 *     @OA\Property(property="capacity", type="integer", example=2),
 *     @OA\Property(property="equipment", type="array", @OA\Items(type="string")),
 *     @OA\Property(property="is_indoor", type="boolean", example=true),
 *     @OA\Property(property="is_active", type="boolean", example=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class ClubFacility extends Model
{
    use HasFactory;

    protected $fillable = [
        'club_id',
        'name',
        'type',
        'description',
        'capacity',
        'equipment',
        'is_indoor',
        'is_active'
    ];

    protected $casts = [
        'equipment' => 'array',
        'is_indoor' => 'boolean',
        'is_active' => 'boolean',
        'capacity' => 'integer'
    ];

    /**
     * Get the club that owns this facility
     */
    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    /**
     * Get the course slots for this facility
     */
    public function courseSlots(): HasMany
    {
        return $this->hasMany(CourseSlot::class, 'facility_id');
    }

    /**
     * Get active course slots for this facility
     */
    public function activeCourseSlots(): HasMany
    {
        return $this->courseSlots()->where('is_active', true);
    }

    /**
     * Get the facility type options
     */
    public static function getTypeOptions(): array
    {
        return [
            'manège' => 'Manège',
            'carrière' => 'Carrière',
            'paddock' => 'Paddock',
            'obstacles' => 'Parcours d\'obstacles',
            'dressage' => 'Carrière de dressage',
            'cross' => 'Cross',
            'voltige' => 'Piste de voltige',
            'attelage' => 'Piste d\'attelage',
            'autre' => 'Autre'
        ];
    }

    /**
     * Check if facility can accommodate multiple courses simultaneously
     */
    public function canAccommodateMultipleCourses(): bool
    {
        return $this->capacity > 1;
    }

    /**
     * Get available capacity for a specific time slot
     */
    public function getAvailableCapacityForSlot($startTime, $endTime, $date): int
    {
        $assignedSlots = $this->courseSlots()
            ->whereHas('assignments', function ($query) use ($date) {
                $query->where('assignment_date', $date);
            })
            ->where(function ($query) use ($startTime, $endTime) {
                $query->where(function ($q) use ($startTime, $endTime) {
                    $q->where('start_time', '<', $endTime)
                      ->where('end_time', '>', $startTime);
                });
            })
            ->count();

        return max(0, $this->capacity - $assignedSlots);
    }
}
