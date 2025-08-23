<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="AppSettingResource",
 *     type="object",
 *     title="App Setting Resource",
 *     description="Ressource des paramètres d'application",
 *     @OA\Property(property="id", type="integer", description="ID unique"),
 *     @OA\Property(property="app_name", type="string", description="Nom de l'application"),
 *     @OA\Property(property="primary_color", type="string", description="Couleur principale"),
 *     @OA\Property(property="secondary_color", type="string", description="Couleur secondaire"),
 *     @OA\Property(property="accent_color", type="string", description="Couleur d'accent"),
 *     @OA\Property(property="logo_url", type="string", nullable=true, description="URL du logo"),
 *     @OA\Property(property="logo_path", type="string", nullable=true, description="Chemin du logo"),
 *     @OA\Property(property="app_description", type="string", nullable=true, description="Description"),
 *     @OA\Property(property="contact_email", type="string", nullable=true, description="Email de contact"),
 *     @OA\Property(property="contact_phone", type="string", nullable=true, description="Téléphone de contact"),
 *     @OA\Property(property="social_links", type="object", nullable=true, description="Liens réseaux sociaux"),
 *     @OA\Property(property="is_active", type="boolean", description="Actif"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class AppSettingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'app_name' => $this->app_name,
            'primary_color' => $this->primary_color,
            'secondary_color' => $this->secondary_color,
            'accent_color' => $this->accent_color,
            'logo_url' => $this->logo_url,
            'logo_path' => $this->logo_path,
            'app_description' => $this->app_description,
            'contact_email' => $this->contact_email,
            'contact_phone' => $this->contact_phone,
            'social_links' => $this->social_links,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
