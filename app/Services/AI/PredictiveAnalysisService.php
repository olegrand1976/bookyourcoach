<?php

namespace App\Services\AI;

use App\Models\Club;
use App\Models\ClubOpenSlot;
use App\Models\Lesson;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PredictiveAnalysisService
{
    protected $gemini;

    public function __construct(GeminiService $gemini)
    {
        $this->gemini = $gemini;
    }

    /**
     * Analyser et prédire la disponibilité pour un club
     */
    public function analyzeAvailability(Club $club): ?array
    {
        // Utiliser le cache pour éviter trop d'appels API
        $cacheKey = "predictive_analysis_{$club->id}_" . now()->format('Y-m-d-H');
        
        return Cache::remember($cacheKey, 3600, function () use ($club) {
            $data = $this->collectHistoricalData($club);
            
            if (empty($data['lessons']) || empty($data['slots'])) {
                return null;
            }

            $instructions = <<<INSTRUCTIONS
Analyse les données historiques de réservation pour ce club sportif et génère des prédictions/recommandations :

1. PRÉDICTIONS :
   - Quels créneaux risquent d'être complets dans les prochains jours
   - Quels créneaux auront probablement des places disponibles
   - Tendances de réservation par jour de la semaine

2. RECOMMANDATIONS :
   - Nouveaux créneaux à ouvrir (avec horaire, jour, justification)
   - Créneaux à fermer ou déplacer (sous-utilisés)
   - Optimisations du planning

3. OPPORTUNITÉS :
   - Revenus potentiels perdus
   - Zones d'amélioration
   - Actions prioritaires

Fournis des insights CONCRETS et ACTIONNABLES avec des chiffres précis.
INSTRUCTIONS;

            return $this->gemini->analyzeData($data, 'availability_prediction', $instructions);
        });
    }

    /**
     * Collecter les données historiques pour l'analyse
     */
    protected function collectHistoricalData(Club $club): array
    {
        // Période d'analyse : 8 dernières semaines
        $startDate = now()->subWeeks(8);
        $endDate = now();

        // Récupérer tous les créneaux ouverts
        $slots = ClubOpenSlot::where('club_id', $club->id)
            ->where('is_active', true)
            ->get()
            ->map(function ($slot) {
                return [
                    'id' => $slot->id,
                    'day_of_week' => $slot->day_of_week,
                    'day_name' => $this->getDayName($slot->day_of_week),
                    'start_time' => $slot->start_time,
                    'end_time' => $slot->end_time,
                    'duration' => $slot->duration,
                    'max_capacity' => $slot->max_capacity,
                    'price' => (float) $slot->price,
                    'discipline_id' => $slot->discipline_id,
                ];
            });

        // Récupérer toutes les leçons sur la période
        $lessons = Lesson::where('club_id', $club->id)
            ->whereBetween('start_time', [$startDate, $endDate])
            ->whereIn('status', ['confirmed', 'completed'])
            ->get()
            ->map(function ($lesson) {
                $startTime = Carbon::parse($lesson->start_time);
                return [
                    'id' => $lesson->id,
                    'date' => $startTime->format('Y-m-d'),
                    'day_of_week' => $startTime->dayOfWeek,
                    'day_name' => $startTime->locale('fr')->dayName,
                    'time' => $startTime->format('H:i'),
                    'hour' => $startTime->hour,
                    'duration' => $lesson->duration,
                    'price' => (float) $lesson->price,
                    'status' => $lesson->status,
                    'discipline_id' => $lesson->discipline_id,
                    'created_at' => Carbon::parse($lesson->created_at)->format('Y-m-d H:i:s'),
                    'weeks_ago' => now()->diffInWeeks($startTime),
                ];
            });

        // Calculer des statistiques par créneau
        $slotStats = $slots->map(function ($slot) use ($lessons) {
            $slotLessons = $lessons->filter(function ($lesson) use ($slot) {
                return $lesson['day_of_week'] == $slot['day_of_week'] 
                    && $lesson['time'] >= $slot['start_time'] 
                    && $lesson['time'] < $slot['end_time'];
            });

            $weeklyBookings = [];
            for ($i = 0; $i < 8; $i++) {
                $weekLessons = $slotLessons->filter(fn($l) => $l['weeks_ago'] == $i);
                $weeklyBookings[] = [
                    'week_offset' => $i,
                    'bookings' => $weekLessons->count(),
                    'fill_rate' => $slot['max_capacity'] > 0 
                        ? round(($weekLessons->count() / $slot['max_capacity']) * 100, 1)
                        : 0
                ];
            }

            return [
                'slot' => $slot,
                'total_bookings' => $slotLessons->count(),
                'avg_weekly_bookings' => round($slotLessons->count() / 8, 1),
                'fill_rate' => $slot['max_capacity'] > 0 
                    ? round(($slotLessons->count() / (8 * $slot['max_capacity'])) * 100, 1)
                    : 0,
                'weekly_bookings' => $weeklyBookings,
                'revenue_generated' => $slotLessons->sum('price'),
                'avg_weekly_revenue' => round($slotLessons->sum('price') / 8, 2),
            ];
        });

        // Analyser les tendances par jour de la semaine
        $dayStats = [];
        for ($day = 0; $day <= 6; $day++) {
            $dayLessons = $lessons->where('day_of_week', $day);
            $daySlots = $slots->where('day_of_week', $day);
            
            $dayStats[] = [
                'day' => $day,
                'day_name' => $this->getDayName($day),
                'total_bookings' => $dayLessons->count(),
                'avg_weekly_bookings' => round($dayLessons->count() / 8, 1),
                'slots_count' => $daySlots->count(),
                'total_capacity' => $daySlots->sum('max_capacity') * 8, // 8 semaines
                'fill_rate' => $daySlots->sum('max_capacity') > 0 
                    ? round(($dayLessons->count() / ($daySlots->sum('max_capacity') * 8)) * 100, 1)
                    : 0,
            ];
        }

        // Identifier les heures populaires
        $hourlyStats = $lessons->groupBy('hour')->map(function ($hourLessons, $hour) {
            return [
                'hour' => $hour,
                'bookings' => $hourLessons->count(),
                'avg_per_week' => round($hourLessons->count() / 8, 1),
            ];
        })->values();

        return [
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
                'weeks' => 8,
            ],
            'slots' => $slots->values()->toArray(),
            'slot_statistics' => $slotStats->values()->toArray(),
            'lessons' => $lessons->values()->toArray(),
            'day_statistics' => $dayStats,
            'hourly_statistics' => $hourlyStats->sortBy('hour')->values()->toArray(),
            'summary' => [
                'total_slots' => $slots->count(),
                'total_lessons' => $lessons->count(),
                'total_capacity' => $slots->sum('max_capacity') * 8 * 7, // 8 semaines * 7 jours
                'overall_fill_rate' => $slots->sum('max_capacity') > 0
                    ? round(($lessons->count() / ($slots->sum('max_capacity') * 8)) * 100, 1)
                    : 0,
                'total_revenue' => $lessons->sum('price'),
                'avg_weekly_revenue' => round($lessons->sum('price') / 8, 2),
            ],
        ];
    }

    /**
     * Obtenir le nom du jour en français
     */
    protected function getDayName(int $dayOfWeek): string
    {
        $days = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
        return $days[$dayOfWeek] ?? 'Inconnu';
    }

    /**
     * Générer un rapport pour le dashboard
     */
    public function generateDashboardReport(Club $club): ?array
    {
        $analysis = $this->analyzeAvailability($club);
        
        if (!$analysis) {
            return null;
        }

        // Formater pour l'affichage dans le dashboard
        return [
            'insights' => $analysis['insights'] ?? [],
            'summary' => $analysis['summary'] ?? 'Aucune analyse disponible',
            'next_actions' => $analysis['nextActions'] ?? [],
            'generated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Obtenir les alertes critiques
     */
    public function getCriticalAlerts(Club $club): array
    {
        $analysis = $this->analyzeAvailability($club);
        
        if (!$analysis || empty($analysis['insights'])) {
            return [];
        }

        return collect($analysis['insights'])
            ->filter(fn($insight) => ($insight['priority'] ?? 'low') === 'high')
            ->take(5)
            ->values()
            ->toArray();
    }
}
