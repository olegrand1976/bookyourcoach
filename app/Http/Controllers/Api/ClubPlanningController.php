<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Club;
use App\Models\ClubOpenSlot;
use App\Models\Lesson;
use App\Models\SubscriptionRecurringSlot;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
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
    public function suggestOptimalSlot(Request $request): JsonResponse
    {
        $club = Auth::user()->getFirstClub();
        if (!$club) {
            return response()->json(['success' => false, 'message' => 'Aucun club associé à cet utilisateur'], 404);
        }
        $clubId = $club->id;
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
        $date = $request->date;
        $duration = $request->duration ?? 60;
        $disciplineId = $request->discipline_id;

        $dayOfWeek = Carbon::parse($date)->dayOfWeek;

        // Récupérer les créneaux ouverts pour ce jour
        $openSlots = ClubOpenSlot::where('club_id', $clubId)
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

                // Vérifier la capacité (utiliser max_slots = nombre de plages simultanées)
                $maxSlots = $slot->max_slots ?? 1;
                if ($lessonsAtThisTime >= $maxSlots) continue;

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

                $maxSlots = $slot->max_slots ?? 1; // Nombre de plages simultanées
                $suggestions[] = [
                    'time' => $timeStr,
                    'slot_id' => $slot->id,
                    'slot_name' => $slot->discipline->name ?? 'Créneau',
                    'slot_range' => substr($slot->start_time, 0, 5) . ' - ' . substr($slot->end_time, 0, 5),
                    'priority' => $priority,
                    'status' => $status,
                    'used_capacity' => $lessonsAtThisTime,
                    'max_slots' => $maxSlots, // Nombre de plages simultanées
                    'max_capacity' => $slot->max_capacity, // Participants par créneau (conservé pour compatibilité)
                    'available_capacity' => $maxSlots - $lessonsAtThisTime,
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
    public function checkAvailability(Request $request): JsonResponse
    {
        $club = Auth::user()->getFirstClub();
        if (!$club) {
            return response()->json(['success' => false, 'message' => 'Aucun club associé à cet utilisateur'], 404);
        }
        $clubId = $club->id;
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
                // Vérifier la capacité (utiliser max_slots = nombre de plages simultanées)
                // Utiliser une comparaison compatible avec SQLite et MySQL
                $timeStart = Carbon::parse("$date $time")->format('Y-m-d H:i:s');
                $lessonsAtThisTime = Lesson::where('club_id', $clubId)
                    ->whereDate('start_time', $date)
                    ->whereRaw("TIME(start_time) = TIME(?)", [$timeStart])
                    ->count();

                $maxSlots = $slot->max_slots ?? 1;
                if ($lessonsAtThisTime >= $maxSlots) {
                    $conflicts[] = "Le créneau est complet ($lessonsAtThisTime/{$maxSlots} plages simultanées)";
                } elseif ($lessonsAtThisTime >= $maxSlots - 1) {
                    $warnings[] = "Le créneau sera bientôt complet (" . ($lessonsAtThisTime + 1) . "/{$maxSlots} plages simultanées)";
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
            // Récupérer les cours qui pourraient entrer en conflit
            $teacherLessons = Lesson::where('teacher_id', $request->teacher_id)
                ->where('start_time', '<', $endDateTime)
                ->get();
            
            $teacherConflict = false;
            foreach ($teacherLessons as $lesson) {
                $lessonEndTime = Carbon::parse($lesson->start_time)->addMinutes($lesson->duration ?? 60);
                if ($lessonEndTime > $startDateTime) {
                    $teacherConflict = true;
                    break;
                }
            }

            if ($teacherConflict) {
                $conflicts[] = "L'enseignant a déjà un cours à cette heure";
            }
        }

        // Vérifier la disponibilité de l'élève
        if ($request->student_id) {
            // Récupérer les cours qui pourraient entrer en conflit
            $studentLessons = Lesson::where('student_id', $request->student_id)
                ->where('start_time', '<', $endDateTime)
                ->get();
            
            $studentConflict = false;
            foreach ($studentLessons as $lesson) {
                $lessonEndTime = Carbon::parse($lesson->start_time)->addMinutes($lesson->duration ?? 60);
                if ($lessonEndTime > $startDateTime) {
                    $studentConflict = true;
                    break;
                }
            }

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
    public function getStatistics(Request $request): JsonResponse
    {
        $club = Auth::user()->getFirstClub();
        if (!$club) {
            return response()->json(['success' => false, 'message' => 'Aucun club associé à cet utilisateur'], 404);
        }
        $clubId = $club->id;
        $startDate = $request->get('start_date', Carbon::now()->startOfWeek()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->endOfWeek()->format('Y-m-d'));

        // Récupérer les créneaux ouverts
        $openSlots = ClubOpenSlot::where('club_id', $clubId)
            ->with('courseTypes')
            ->where('is_active', true)
            ->get();

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
                $maxSlots = $slot->max_slots ?? 1; // Nombre de plages simultanées
                $totalCapacity = $maxSlots * $possibleSlots;
                $occupancyRate = $totalCapacity > 0 ? round(($lessonsCount / $totalCapacity) * 100, 2) : 0;

                $slotStats[] = [
                    'slot_id' => $slot->id,
                    'slot_name' => $slot->discipline->name ?? 'Créneau',
                    'date' => $date,
                    'day_of_week' => $dayOfWeek,
                    'time_range' => substr($slot->start_time, 0, 5) . ' - ' . substr($slot->end_time, 0, 5),
                    'lessons_count' => $lessonsCount,
                    'max_slots' => $maxSlots, // Nombre de plages simultanées
                    'max_capacity' => $slot->max_capacity, // Participants par créneau (conservé pour compatibilité)
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
     * Plages restant disponibles par semaine et par créneau.
     * Pour chaque semaine, chaque créneau ouvert, chaque date concernée : liste des plages horaires avec (occupé, max, restant).
     *
     * @param Request $request weeks (nombre de semaines à partir d'aujourd'hui, défaut 4)
     * @return JsonResponse
     */
    public function availabilityByWeek(Request $request): JsonResponse
    {
        $user = Auth::user();
        $club = $user->getFirstClub();
        if (!$club) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun club associé à cet utilisateur',
            ], 404);
        }
        $clubId = $club->id;
        $weeksCount = (int) $request->get('weeks', 4);
        $weeksCount = max(1, min(12, $weeksCount));

        $today = Carbon::now()->startOfDay();
        $weekStart = $today->copy()->startOfWeek(Carbon::MONDAY);
        if ($weekStart->gt($today)) {
            $weekStart->subWeek();
        }

        $openSlots = ClubOpenSlot::where('club_id', $clubId)
            ->with(['courseTypes', 'discipline'])
            ->where('is_active', true)
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        $horizonEnd = $today->copy()->addWeeks(26)->format('Y-m-d');
        $slotIds = $openSlots->pluck('id')->all();

        // Récurrences actives (créneau + plage) sur 26 semaines : une requête, indexée par open_slot_id
        $recurringBySlot = SubscriptionRecurringSlot::where('status', 'active')
            ->whereNotNull('open_slot_id')
            ->whereIn('open_slot_id', $slotIds)
            ->where('start_date', '<=', $horizonEnd)
            ->where('end_date', '>=', $today->format('Y-m-d'))
            ->whereHas('subscriptionInstance.subscription', fn ($q) => $q->where('club_id', $clubId))
            ->get(['open_slot_id', 'day_of_week', 'start_time', 'end_time'])
            ->groupBy('open_slot_id');

        $result = [];

        for ($w = 0; $w < $weeksCount; $w++) {
            $startDate = $weekStart->copy()->addWeeks($w)->format('Y-m-d');
            $endDate = Carbon::parse($startDate)->endOfWeek()->format('Y-m-d');

            $lessons = Lesson::where('club_id', $clubId)
                ->where('status', '!=', 'cancelled')
                ->whereBetween('start_time', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                ->get();

            $weeksSlots = [];

            foreach ($openSlots as $slot) {
                $timeStep = $this->calculateTimeStep($slot->courseTypes);
                $slotStartMinutes = $this->timeToMinutes($slot->start_time);
                $slotEndMinutes = $this->timeToMinutes($slot->end_time);
                $maxSlots = (int) ($slot->max_slots ?? 1);

                $recurringForSlot = $recurringBySlot->get($slot->id, collect())->filter(
                    fn ($rec) => (int) $rec->day_of_week === (int) $slot->day_of_week
                );

                $dates = $this->getDatesByDayOfWeek($startDate, $endDate, $slot->day_of_week);
                $slotLabel = $slot->discipline->name ?? 'Créneau';
                $timeRange = substr($slot->start_time, 0, 5) . ' - ' . substr($slot->end_time, 0, 5);

                $datesDetail = [];

                foreach ($dates as $date) {
                    $plages = [];
                    for ($min = $slotStartMinutes; $min + $timeStep <= $slotEndMinutes; $min += $timeStep) {
                        $timeStr = $this->minutesToTime($min);
                        $occupied = $lessons->filter(function ($lesson) use ($date, $timeStr) {
                            $d = Carbon::parse($lesson->start_time)->format('Y-m-d');
                            $t = Carbon::parse($lesson->start_time)->format('H:i');
                            return $d === $date && $t === $timeStr;
                        })->count();
                        $remaining = max(0, $maxSlots - $occupied);
                        $isRecurringPlage = $this->isTimeInRecurringRanges($timeStr, $recurringForSlot);
                        $plages[] = [
                            'time' => $timeStr,
                            'max_slots' => $maxSlots,
                            'occupied' => $occupied,
                            'remaining' => $remaining,
                            'is_recurring' => $isRecurringPlage,
                        ];
                    }
                    $datesDetail[] = [
                        'date' => $date,
                        'day_of_week' => $slot->day_of_week,
                        'plages' => $plages,
                    ];
                }

                $weeksSlots[] = [
                    'slot_id' => $slot->id,
                    'slot_name' => $slotLabel,
                    'time_range' => $timeRange,
                    'day_of_week' => $slot->day_of_week,
                    'dates' => $datesDetail,
                ];
            }

            $result[] = [
                'week_start' => $startDate,
                'week_end' => $endDate,
                'slots' => $weeksSlots,
            ];
        }

        return response()->json([
            'success' => true,
            'weeks' => $result,
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
     * Vérifier si une heure (HH:mm) tombe dans l'une des plages récurrentes (start_time / end_time).
     */
    private function isTimeInRecurringRanges(string $timeStr, $recurringSlots): bool
    {
        $min = $this->timeToMinutes($timeStr);
        foreach ($recurringSlots as $rec) {
            $startMin = $this->timeToMinutes($rec->start_time ?? '00:00');
            $endMin = $this->timeToMinutes($rec->end_time ?? '23:59');
            if ($min >= $startMin && $min < $endMin) {
                return true;
            }
        }
        return false;
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


