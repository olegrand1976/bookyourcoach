<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

/**
 * @OA\Schema(
 *     schema="AppSetting",
 *     type="object",
 *     title="Application Settings",
 *     description="Configuration des paramètres d'apparence de l'application",
 *     required={"app_name", "primary_color", "secondary_color", "accent_color"},
 *     @OA\Property(property="id", type="integer", description="ID unique"),
 *     @OA\Property(property="app_name", type="string", description="Nom de l'application"),
 *     @OA\Property(property="primary_color", type="string", description="Couleur principale (hex)"),
 *     @OA\Property(property="secondary_color", type="string", description="Couleur secondaire (hex)"),
 *     @OA\Property(property="accent_color", type="string", description="Couleur d'accent (hex)"),
 *     @OA\Property(property="logo_url", type="string", nullable=true, description="URL du logo"),
 *     @OA\Property(property="logo_path", type="string", nullable=true, description="Chemin local du logo"),
 *     @OA\Property(property="app_description", type="string", nullable=true, description="Description de l'application"),
 *     @OA\Property(property="contact_email", type="string", nullable=true, description="Email de contact"),
 *     @OA\Property(property="contact_phone", type="string", nullable=true, description="Téléphone de contact"),
 *     @OA\Property(property="social_links", type="object", nullable=true, description="Liens réseaux sociaux"),
 *     @OA\Property(property="is_active", type="boolean", description="Paramètres actifs"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class AppSetting extends Model
{
    protected $fillable = [
        'app_name',
        'primary_color',
        'secondary_color',
        'accent_color',
        'logo_url',
        'logo_path',
        'app_description',
        'contact_email',
        'contact_phone',
        'social_links',
        'is_active',
        // Nouveaux champs pour le système clé-valeur
        'key',
        'value',
        'type',
        'group'
    ];

    protected $casts = [
        'social_links' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Récupère les paramètres actifs de l'application
     */
    public static function getActiveSettings(): ?self
    {
        return self::where('is_active', true)->first();
    }

    /**
     * Récupère ou crée les paramètres par défaut
     */
    public static function getOrCreateDefault(): self
    {
        $settings = self::getActiveSettings();

        if (!$settings) {
            $settings = self::create([
                'app_name' => 'BookYourCoach',
                'primary_color' => '#2563eb',
                'secondary_color' => '#1e40af',
                'accent_color' => '#3b82f6',
                'is_active' => true
            ]);
        }

        return $settings;
    }

    /**
     * Valide qu'une couleur est au format hexadécimal
     */
    protected function primaryColor(): Attribute
    {
        return Attribute::make(
            set: fn($value) => $this->validateHexColor($value),
        );
    }

    protected function secondaryColor(): Attribute
    {
        return Attribute::make(
            set: fn($value) => $this->validateHexColor($value),
        );
    }

    protected function accentColor(): Attribute
    {
        return Attribute::make(
            set: fn($value) => $this->validateHexColor($value),
        );
    }

    /**
     * Valide le format hexadécimal d'une couleur
     */
    private function validateHexColor(string $color): string
    {
        if (!preg_match('/^#[a-fA-F0-9]{6}$/', $color)) {
            throw new \InvalidArgumentException('La couleur doit être au format hexadécimal (#000000)');
        }
        return $color;
    }
}
