<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @OA\Schema(
 *     schema="Student",
 *     type="object",
 *     title="Student",
 *     description="Profil étudiant sur la plateforme",
 *     @OA\Property(property="id", type="integer", format="int64", description="Identifiant unique de l'étudiant", example=1),
 *     @OA\Property(property="user_id", type="integer", description="ID de l'utilisateur associé", example=1),
 *     @OA\Property(property="level", type="string", description="Niveau de l'élève", example="débutant"),
 *     @OA\Property(property="goals", type="string", description="Objectifs de l'élève", example="Apprendre les bases du dressage"),
 *     @OA\Property(property="medical_info", type="string", description="Informations médicales", example="Aucune allergie connue"),
 *     @OA\Property(property="emergency_contact", type="string", description="Contact d'urgence", example="Marie Dupont - 06.12.34.56.78"),
 *     @OA\Property(property="created_at", type="string", format="datetime", description="Date de création"),
 *     @OA\Property(property="updated_at", type="string", format="datetime", description="Date de dernière mise à jour")
 * )
 */
class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'level',
        'goals',
        'medical_info',
        'emergency_contact'
    ];

    /**
     * Get the user that owns the student profile.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the lessons for this student.
     */
    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class);
    }

    /**
     * Get the subscriptions for this student.
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}
