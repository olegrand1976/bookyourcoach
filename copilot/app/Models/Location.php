<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *     schema="Location",
 *     type="object",
 *     title="Location",
 *     description="Lieu de cours sur la plateforme",
 *     @OA\Property(property="id", type="integer", format="int64", description="Identifiant unique du lieu", example=1),
 *     @OA\Property(property="name", type="string", maxLength=255, description="Nom du lieu", example="Centre Équestre de Paris"),
 *     @OA\Property(property="address", type="string", maxLength=500, description="Adresse complète", example="123 Rue de la Paix"),
 *     @OA\Property(property="city", type="string", maxLength=100, description="Ville", example="Paris"),
 *     @OA\Property(property="postal_code", type="string", maxLength=20, description="Code postal", example="75001"),
 *     @OA\Property(property="country", type="string", maxLength=100, description="Pays", example="France"),
 *     @OA\Property(property="latitude", type="number", format="float", description="Latitude GPS", example=48.8566),
 *     @OA\Property(property="longitude", type="number", format="float", description="Longitude GPS", example=2.3522),
 *     @OA\Property(property="description", type="string", description="Description du lieu", example="Centre équestre moderne"),
 *     @OA\Property(property="facilities", type="array", @OA\Items(type="string"), description="Équipements disponibles", example={"manège couvert", "carrière", "parking"}),
 *     @OA\Property(property="created_at", type="string", format="datetime", description="Date de création"),
 *     @OA\Property(property="updated_at", type="string", format="datetime", description="Date de dernière mise à jour")
 * )
 */
class Location extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'city',
        'postal_code',
        'country',
        'latitude',
        'longitude',
        'description',
        'facilities'
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'facilities' => 'array'
    ];

    /**
     * Get the lessons at this location.
     */
    public function lessons()
    {
        return $this->hasMany(Lesson::class);
    }

    /**
     * Get the full address as a single string.
     */
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address,
            $this->postal_code,
            $this->city,
            $this->country
        ]);
        
        return implode(', ', $parts);
    }
}
