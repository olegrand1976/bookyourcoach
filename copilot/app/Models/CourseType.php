<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @OA\Schema(
 *     schema="CourseType",
 *     type="object",
 *     title="Course Type",
 *     description="Type de cours disponible sur la plateforme",
 *     @OA\Property(property="id", type="integer", format="int64", description="Identifiant unique du type de cours", example=1),
 *     @OA\Property(property="name", type="string", maxLength=255, description="Nom du type de cours", example="Dressage"),
 *     @OA\Property(property="description", type="string", description="Description du type de cours", example="Cours de dressage classique"),
 *     @OA\Property(property="duration", type="integer", description="Durée standard en minutes", example=60),
 *     @OA\Property(property="price", type="number", format="float", description="Prix de base", example=45.00),
 *     @OA\Property(property="created_at", type="string", format="datetime", description="Date de création"),
 *     @OA\Property(property="updated_at", type="string", format="datetime", description="Date de dernière mise à jour")
 * )
 */
class CourseType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'duration',
        'price'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'duration' => 'integer'
    ];

    /**
     * Get the teachers that offer this course type.
     */
    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(Teacher::class, 'teacher_course_types');
    }

    /**
     * Get the lessons of this course type.
     */
    public function lessons()
    {
        return $this->hasMany(Lesson::class);
    }
}
