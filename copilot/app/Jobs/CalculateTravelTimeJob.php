<?php

namespace App\Jobs;

use App\Models\Teacher;
use App\Models\Location;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CalculateTravelTimeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Teacher $teacher,
        public Location $location
    ) {}

    public function handle(): void
    {
        try {
            // Utiliser une API de cartographie (exemple avec OpenRouteService)
            $teacherAddress = $this->teacher->user->profile->address ?? 'Brussels, Belgium';
            $locationAddress = $this->location->address ?? "{$this->location->latitude},{$this->location->longitude}";

            // Simuler le calcul (en production, utiliser une vraie API)
            $travelTime = $this->calculateDistance($teacherAddress, $locationAddress);

            // Mettre à jour les informations de l'enseignant
            $preferredLocations = $this->teacher->preferred_locations ?? [];
            $preferredLocations[$this->location->id] = [
                'location_id' => $this->location->id,
                'travel_time_minutes' => $travelTime,
                'updated_at' => now()->toISOString(),
            ];

            $this->teacher->update([
                'preferred_locations' => $preferredLocations
            ]);

            Log::info("Travel time calculated for teacher {$this->teacher->id} to location {$this->location->id}: {$travelTime} minutes");

        } catch (\Exception $e) {
            Log::error("Failed to calculate travel time: " . $e->getMessage());
        }
    }

    private function calculateDistance(string $origin, string $destination): int
    {
        // Simulation basique - en production, utiliser une vraie API
        $distances = [
            'short' => rand(15, 30),
            'medium' => rand(30, 60),
            'long' => rand(60, 120),
        ];

        return $distances[array_rand($distances)];
    }
}
