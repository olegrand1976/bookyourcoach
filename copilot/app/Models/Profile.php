<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @OA\Schema(
 *     schema="Profile",
 *     type="object",
 *     title="Profile",
 *     description="Profil utilisateur avec informations personnelles et de contact",
 *     @OA\Property(property="id", type="integer", format="int64", description="Identifiant unique du profil", example=1),
 *     @OA\Property(property="user_id", type="integer", format="int64", description="ID de l'utilisateur associé", example=1),
 *     @OA\Property(property="first_name", type="string", maxLength=255, description="Prénom", example="Jean"),
 *     @OA\Property(property="last_name", type="string", maxLength=255, description="Nom de famille", example="Dupont"),
 *     @OA\Property(property="phone", type="string", maxLength=20, description="Numéro de téléphone", example="+33123456789"),
 *     @OA\Property(property="address", type="string", description="Adresse complète", example="123 Rue de la Paix"),
 *     @OA\Property(property="city", type="string", maxLength=100, description="Ville", example="Paris"),
 *     @OA\Property(property="postal_code", type="string", maxLength=10, description="Code postal", example="75001"),
 *     @OA\Property(property="country", type="string", maxLength=100, description="Pays", example="France"),
 *     @OA\Property(property="date_of_birth", type="string", format="date", description="Date de naissance", example="1990-05-15"),
 *     @OA\Property(property="emergency_contact_name", type="string", maxLength=255, description="Nom du contact d'urgence", example="Marie Dupont"),
 *     @OA\Property(property="emergency_contact_phone", type="string", maxLength=20, description="Téléphone du contact d'urgence", example="+33987654321"),
 *     @OA\Property(property="medical_notes", type="string", description="Notes médicales", example="Allergie aux abeilles"),
 *     @OA\Property(property="preferences", type="object", description="Préférences utilisateur (JSON)", example={"language": "fr", "notifications": true}),
 *     @OA\Property(property="created_at", type="string", format="datetime", description="Date de création"),
 *     @OA\Property(property="updated_at", type="string", format="datetime", description="Date de dernière mise à jour")
 * )
 */
class Profile extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'phone',
        'date_of_birth',
        'gender',
        'address',
        'city',
        'postal_code',
        'country',
        'emergency_contact_name',
        'emergency_contact_phone',
        'medical_notes',
        'avatar',
        'bio',
        'preferences'
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'preferences' => 'array'
    ];

    /**
     * Get the user that owns the profile.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the full name attribute.
     */
    public function getFullNameAttribute(): string
    {
        return trim(preg_replace('/\s+/', ' ', trim($this->first_name) . ' ' . trim($this->last_name)));
    }
}
