<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Club;
use App\Models\ClubOpenSlot;
use App\Models\Lesson;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

/**
 * Contrôleur pour les fonctionnalités avancées du planning club
 * Gère les suggestions optimales, les statistiques et les vérifications
 */
class ClubPlanningController extends Controller
{
    /**
     * Suggérer un créneau optimal pour minimiser les coûts
     * 
     * @param Request $request
     * @param int $clubId
     * @return JsonResponse
     */
    public function suggestOptimalSlot(Request $request, $clubId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
            'duration' => 'nullable|integer|min:5|max:240',
            'discipline_id' => 'nullable|exists:disciplines,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $club = Club::findOrFail($clubId);
        $date = $request->date;
        $duration = $request->duration ?? 60;
        $disciplineId = $request->discipline_id;

        $dayOfWeek = Carbon::parse($date)->dayOfWeek;

        // Récupérer les créneaux ouverts pour ce jour
        $openSlots = $club->openSlots()
            ->with(['courseTypes', 'discipline'])
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->get();

        // Filtrer par discipline si demandé
        if ($disciplineId) {
            $openSlots = $openSlots->where('discipline_id', $disciplineId);
        }

        // Récupérer les cours existants pour cette date
        $existingLessons = Lesson::where('club_id', $clubId)
            ->whereDate('start_time', $date)
            ->get();

        $suggestions = [];

        foreach ($openSlots as $slot) {
            $slotStartMinutes = $this->timeToMinutes($slot->start_time);
            $slotEndMinutes = $this->timeToMinutes($slot->end_time);

            // Calculer le pas de temps basé sur les types de cours
            $timeStep = $this->calculateTimeStep($slot->courseTypes);
            
            // Compter les cours existants dans ce créneau
            $lessonsInSlot = $existingLessons->filter(function($lesson) use ($slot, $slotStartMinutes, $slotEndMinutes) {
                $lessonStart = $this->timeToMinutes(Carbon::parse($lesson->start_time)->format('H:i'));
                return $lessonStart >= $slotStartMinutes && $lessonStart < $slotEndMinutes;
            });

            $totalLessonsInSlot = $lessonsInSlot->count();
            $hasLessons = $totalLessonsInSlot > 0;

            // Trouver les enseignants dans ce créneau
            $teachersInSlot = $lessonsInSlot->pluck('teacher_id')->unique()->values()->toArray();

            // Générer les suggestions pour chaque heure possible
            for ($startMinutes = $slotStartMinutes; $startMinutes < $slotEndMinutes; $startMinutes += $timeStep) {
                $endMinutes = $startMinutes + $duration;

                // Vérifier que le cours peut tenir
                if ($endMinutes > $slotEndMinutes) continue;

                $timeStr = $this->minutesToTime($startMinutes);

                // Compter les cours à cette heure précise
                $lessonsAtThisTime = $existingLessons->filter(function($lesson) use ($date, $timeStr) {
                    $lessonDate = Carbon::parse($lesson->start_time)->format('Y-m-d');
                    $lessonTime = Carbon::parse($lesson->start_time)->format('H:i');
                    return $lessonDate === $date && $lessonTime === $timeStr;
                })->count();

                // Vérifier la capacité
                if ($lessonsAtThisTime >= $slot->max_capacity) continue;

                // Calculer la priorité
                $priority = 3; // Basse par défaut (plage vide)
                $status = 'empty';

                if ($hasLessons && $lessonsAtThisTime === 0) {
                    $priority = 1; // PRIORITÉ MAXIMALE : plage occupée, créneau vide
                    $status = 'priority_empty';
                } elseif ($hasLessons && $lessonsAtThisTime > 0) {
                    $priority = 2; // MOYEN : plage occupée, créneau utilisé mais pas plein
                    $status = 'priority_used';
                }

                $suggestions[] = [
                    'time' => $timeStr,
                    'slot_id' => $slot->id,
                    'slot_name' => $slot->discipline->name ?? 'Créneau',
                    'slot_range' => substr($slot->start_time, 0, 5) . ' - ' . substr($slot->end_time, 0, 5),
                    'priority' => $priority,
                    'status' => $status,
                    'used_capacity' => $lessonsAtThisTime,
                    'max_capacity' => $slot->max_capacity,
                    'available_capacity' => $slot->max_capacity - $lessonsAtThisTime,
                    'total_lessons_in_slot' => $totalLessonsInSlot,
                    'teachers_in_slot' => $teachersInSlot,
                    'minutes_from_slot_start' => $startMinutes - $slotStartMinutes
                ];
            }
        }

        // Trier par priorité, puis par nombre de cours dans la plage, puis par position dans la plage
        usort($suggestions, function($a, $b) {
            if ($a['priority'] !== $b['priority']) {
                return $a['priority'] - $b['priority'];
            }
            if ($a['total_lessons_in_slot'] !== $b['total_lessons_in_slot']) {
                return $b['total_lessons_in_slot'] - $a['total_lessons_in_slot'];
            }
            return $a['minutes_from_slot_start'] - $b['minutes_from_slot_start'];
        });

        return response()->json([
            'success' => true,
            'suggestions' => $suggestions,
            'total_found' => count($suggestions)
        ]);
    }

    /**
     * Vérifier la disponibilité d'un créneau
     * 
     * @param Request $request
     * @param int $clubId
     * @return JsonResponse
     */
    public function checkAvailability(Request $request, $clubId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
            'time' => 'required|date_format:H:i',
            'duration' => 'required|integer|min:5|max:240',
            'slot_id' => 'nullable|exists:club_open_slots,id',
            'teacher_id' => 'nullable|exists:teachers,id',
            'student_id' => 'nullable|exists:students,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $club = Club::findOrFail($clubId);
        $date = $request->date;
        $time = $request->time;
        $duration = $request->duration;

        $startDateTime = Carbon::parse("$date $time");
        $endDateTime = $startDateTime->copy()->addMinutes($duration);

        $conflicts = [];
        $warnings = [];

        // Vérifier si un créneau ouvert est spécifié
        if ($request->slot_id) {
            $slot = ClubOpenSlot::find($request->slot_id);
            
            if ($slot) {
                // Vérifier la capacité
                $lessonsAtThisTime = Lesson::where('club_id', $clubId)
                    ->whereDate('start_time', $date)
                    ->whereTime('start_time', $time)
                    ->count();

                if ($lessonsAtThisTime >= $slot->max_capacity) {
                    $conflicts[] = "Le créneau est complet ($lessonsAtThisTime/{$slot->max_capacity})";
                } elseif ($lessonsAtThisTime >= $slot->max_capacity - 1) {
                    $warnings[] = "Le créneau sera bientôt complet (" . ($lessonsAtThisTime + 1) . "/{$slot->max_capacity})";
                }

                // Vérifier que le cours ne dépasse pas la fin du créneau
                $slotEnd = Carbon::parse($date . ' ' . $slot->end_time);
                if ($endDateTime->gt($slotEnd)) {
                    $conflicts[] = "Le cours dépasserait la fin du créneau ({$slot->end_time})";
                }
            }
        }

        // Vérifier la disponibilité de l'enseignant
        if ($request->teacher_id) {
            $teacherConflict = Lesson::where('teacher_id', $request->teacher_id)
                ->where(function($query) use ($startDateTime, $endDateTime) {
                    $query->where(function($q) use ($startDateTime, $endDateTime) {
                        $q->where('start_time', '<', $endDateTime)
                          ->whereRaw('DATE_ADD(start_time, INTERVAL duration MINUTE) > ?', [$startDateTime]);
                    });
                })
                ->exists();

            if ($teacherConflict) {
                $conflicts[] = "L'enseignant a déjà un cours à cette heure";
            }
        }

        // Vérifier la disponibilité de l'élève
        if ($request->student_id) {
            $studentConflict = Lesson::where('student_id', $request->student_id)
                ->where(function($query) use ($startDateTime, $endDateTime) {
                    $query->where(function($q) use ($startDateTime, $endDateTime) {
                        $q->where('start_time', '<', $endDateTime)
                          ->whereRaw('DATE_ADD(start_time, INTERVAL duration MINUTE) > ?', [$startDateTime]);
                    });
                })
                ->exists();

            if ($studentConflict) {
                $conflicts[] = "L'élève a déjà un cours à cette heure";
            }
        }

        $available = empty($conflicts);

        return response()->json([
            'success' => true,
            'available' => $available,
            'conflicts' => $conflicts,
            'warnings' => $warnings
        ]);
    }

    /**
     * Obtenir les statistiques du planning
     * 
     * @param Request $request
     * @param int $clubId
     * @return JsonResponse
     */
    public function getStatistics(Request $request, $clubId): JsonResponse
    {
        $club = Club::findOrFail($clubId);
        $startDate = $request->get('start_date', Carbon::now()->startOfWeek()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->endOfWeek()->format('Y-m-d'));

        // Récupérer les créneaux ouverts
        $openSlots = $club->openSlots()->with('courseTypes')->where('is_active', true)->get();

        // Récupérer les cours de la période
        $lessons = Lesson::where('club_id', $clubId)
            ->whereBetween('start_time', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->get();

        $slotStats = [];

        foreach ($openSlots as $slot) {
            $dayOfWeek = $slot->day_of_week;
            
            // Générer les dates pour ce jour de la semaine dans la période
            $dates = $this->getDatesByDayOfWeek($startDate, $endDate, $dayOfWeek);

            foreach ($dates as $date) {
                $lessonsForDate = $lessons->filter(function($lesson) use ($date, $slot) {
                    $lessonDate = Carbon::parse($lesson->start_time)->format('Y-m-d');
                    $lessonTime = $this->timeToMinutes(Carbon::parse($lesson->start_time)->format('H:i'));
                    $slotStart = $this->timeToMinutes($slot->start_time);
                    $slotEnd = $this->timeToMinutes($slot->end_time);
                    
                    return $lessonDate === $date && $lessonTime >= $slotStart && $lessonTime < $slotEnd;
                });

                $lessonsCount = $lessonsForDate->count();
                $revenue = $lessonsForDate->sum('price');

                // Calculer le nombre de créneaux possibles
                $timeStep = $this->calculateTimeStep($slot->courseTypes);
                $slotDuration = $this->timeToMinutes($slot->end_time) - $this->timeToMinutes($slot->start_time);
                $possibleSlots = floor($slotDuration / $timeStep);
                $totalCapacity = $slot->max_capacity * $possibleSlots;
                $occupancyRate = $totalCapacity > 0 ? round(($lessonsCount / $totalCapacity) * 100, 2) : 0;

                $slotStats[] = [
                    'slot_id' => $slot->id,
                    'slot_name' => $slot->discipline->name ?? 'Créneau',
                    'date' => $date,
                    'day_of_week' => $dayOfWeek,
                    'time_range' => substr($slot->start_time, 0, 5) . ' - ' . substr($slot->end_time, 0, 5),
                    'lessons_count' => $lessonsCount,
                    'max_capacity' => $slot->max_capacity,
                    'possible_slots' => $possibleSlots,
                    'total_capacity' => $totalCapacity,
                    'occupancy_rate' => $occupancyRate,
                    'revenue' => round($revenue, 2)
                ];
            }
        }

        // Statistiques globales
        $summary = [
            'total_lessons' => $lessons->count(),
            'total_revenue' => round($lessons->sum('price'), 2),
            'average_lesson_price' => $lessons->count() > 0 ? round($lessons->avg('price'), 2) : 0,
            'unique_students' => $lessons->pluck('student_id')->unique()->filter()->count(),
            'unique_teachers' => $lessons->pluck('teacher_id')->unique()->filter()->count()
        ];

        return response()->json([
            'success' => true,
            'by_slot' => $slotStats,
            'summary' => $summary,
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate
            ]
        ]);
    }

    /**
     * Convertir une heure "HH:MM:SS" ou "HH:MM" en minutes depuis minuit
     */
    private function timeToMinutes(string $time): int
    {
        $parts = explode(':', $time);
        $hours = (int)$parts[0];
        $minutes = (int)($parts[1] ?? 0);
        return $hours * 60 + $minutes;
    }

    /**
     * Convertir des minutes depuis minuit en heure "HH:MM"
     */
    private function minutesToTime(int $minutes): string
    {
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        return sprintf('%02d:%02d', $hours, $mins);
    }

    /**
     * Calculer le PGCD de deux nombres
     */
    private function gcd(int $a, int $b): int
    {
        return $b === 0 ? $a : $this->gcd($b, $a % $b);
    }

    /**
     * Calculer le PGCD d'un tableau de nombres
     */
    private function gcdArray(array $numbers): int
    {
        if (empty($numbers)) return 30;
        if (count($numbers) === 1) return $numbers[0];

        $result = $numbers[0];
        for ($i = 1; $i < count($numbers); $i++) {
            $result = $this->gcd($result, $numbers[$i]);
        }

        return $result;
    }

    /**
     * Calculer le pas de temps basé sur les durées des types de cours
     */
    private function calculateTimeStep($courseTypes): int
    {
        if (!$courseTypes || $courseTypes->count() === 0) {
            return 30; // Valeur par défaut
        }

        $durations = $courseTypes->pluck('duration_minutes')->filter()->toArray();
        
        if (empty($durations)) {
            return 30;
        }

        $step = $this->gcdArray($durations);

        // Assurer que le pas est raisonnable (entre 5 et 60 minutes)
        return max(5, min($step, 60));
    }

    /**
     * Obtenir les dates correspondant à un jour de la semaine dans une période
     */
    private function getDatesByDayOfWeek(string $startDate, string $endDate, int $dayOfWeek): array
    {
        $dates = [];
        $current = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        // Trouver le premier jour correspondant
        while ($current->dayOfWeek !== $dayOfWeek && $current->lte($end)) {
            $current->addDay();
        }

        // Ajouter tous les jours correspondants
        while ($current->lte($end)) {
            $dates[] = $current->format('Y-m-d');
            $current->addWeek();
        }

        return $dates;
    }
}


