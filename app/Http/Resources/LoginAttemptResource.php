<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoginAttemptResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'successful' => $this->successful,
            'failure_reason' => $this->failure_reason,
            'occurred_at' => $this->created_at?->toIso8601String(),
            'ip_address' => $this->ip_address,
            'device' => [
                'type' => $this->device_type,
                'browser' => $this->browser,
                'platform' => $this->platform,
            ],
            'location' => [
                'label' => $this->location_label,
                'country_code' => $this->country_code,
                'country' => $this->country,
                'region' => $this->region,
                'city' => $this->city,
                'time_zone' => $this->time_zone,
                'organisation' => $this->organisation,
                // Rayon annoncé par GeoLite2 : une ville reste une estimation.
                'accuracy_radius_km' => $this->accuracy_radius_km,
            ],
            // Réservé aux administrateurs : la chaîne brute et l'adresse visée ne
            // servent qu'à l'enquête.
            'email_attempted' => $this->when($request->user()?->isAdmin(), $this->email),
            'forwarded_for' => $this->when($request->user()?->isAdmin(), $this->forwarded_for),
            'user_agent' => $this->when($request->user()?->isAdmin(), $this->user_agent),
        ];
    }
}
