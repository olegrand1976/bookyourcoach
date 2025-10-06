<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Club;
use App\Models\ClubFacility;
use App\Models\CourseSlot;
use App\Models\CourseType;
use App\Models\TeacherContract;
use App\Models\CourseAssignment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

/**
 * @OA\Tag(
 *     name="Club Course Management",
 *     description="Gestion des cours pour les clubs"
 * )
 */
class ClubCourseController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/clubs/{clubId}/course-dashboard",
     *     summary="Tableau de bord des cours du club",
     *     tags={"Club Course Management"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="clubId",
     *         in="path",
     *         required=true,
     *         description="ID du club",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="start_date",
     *         in="query",
     *         description="Date de début (format Y-m-d)",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="end_date",
     *         in="query",
     *         description="Date de fin (format Y-m-d)",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Données du tableau de bord",
     *         @OA\JsonContent(
     *             @OA\Property(property="club", type="object"),
     *             @OA\Property(property="facilities", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="course_slots", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="assignments", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="calendar_data", type="array", @OA\Items(type="object"))
     *         )
     *     )
     * )
     */
    public function getCourseDashboard(Request $request, $clubId): JsonResponse
    {
        $club = Club::findOrFail($clubId);
        
        $startDate = $request->get('start_date', Carbon::now()->startOfWeek()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->endOfWeek()->format('Y-m-d'));

        // Récupérer les installations du club
        $facilities = $club->activeFacilities()->with(['courseSlots' => function($query) use ($startDate, $endDate) {
            $query->where('is_active', true)
                  ->where(function($q) use ($startDate, $endDate) {
                      $q->whereNull('end_date')
                        ->orWhere('end_date', '>=', $startDate);
                  })
                  ->where('start_date', '<=', $endDate);
        }])->get();

        // Récupérer les plages de cours
        $courseSlots = $club->activeCourseSlots()
            ->with(['facility', 'courseType', 'assignments' => function($query) use ($startDate, $endDate) {
                $query->whereBetween('assignment_date', [$startDate, $endDate]);
            }])
            ->where(function($query) use ($startDate, $endDate) {
                $query->whereNull('end_date')
                      ->orWhere('end_date', '>=', $startDate);
            })
            ->where('start_date', '<=', $endDate)
            ->get();

        // Générer les données du calendrier
        $calendarData = $this->generateCalendarData($courseSlots, $startDate, $endDate);

        // Statistiques
        $stats = $this->calculateDashboardStats($courseSlots, $startDate, $endDate);

        return response()->json([
            'club' => $club,
            'facilities' => $facilities,
            'course_slots' => $courseSlots,
            'calendar_data' => $calendarData,
            'stats' => $stats,
            'date_range' => [
                'start' => $startDate,
                'end' => $endDate
            ]
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/clubs/{clubId}/course-slots",
     *     summary="Créer une nouvelle plage de cours",
     *     tags={"Club Course Management"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="clubId",
     *         in="path",
     *         required=true,
     *         description="ID du club",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"facility_id", "course_type_id", "name", "start_time", "end_time", "day_of_week", "start_date"},
     *             @OA\Property(property="facility_id", type="integer"),
     *             @OA\Property(property="course_type_id", type="integer"),
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="start_time", type="string", format="time"),
     *             @OA\Property(property="end_time", type="string", format="time"),
     *             @OA\Property(property="day_of_week", type="string", enum={"monday", "tuesday", "wednesday", "thursday", "friday", "saturday", "sunday"}),
     *             @OA\Property(property="start_date", type="string", format="date"),
     *             @OA\Property(property="end_date", type="string", format="date"),
     *             @OA\Property(property="max_students", type="integer"),
     *             @OA\Property(property="price", type="number", format="float"),
     *             @OA\Property(property="is_recurring", type="boolean"),
     *             @OA\Property(property="notes", type="string")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Plage de cours créée avec succès"),
     *     @OA\Response(response=422, description="Erreur de validation")
     * )
     */
    public function createCourseSlot(Request $request, $clubId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'facility_id' => 'required|exists:club_facilities,id',
            'course_type_id' => 'required|exists:course_types,id',
            'name' => 'required|string|max:255',
            'start_time' => 'required|date_format:H:i:s',
            'end_time' => 'required|date_format:H:i:s|after:start_time',
            'day_of_week' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'max_students' => 'required|integer|min:1|max:20',
            'price' => 'required|numeric|min:0',
            'is_recurring' => 'boolean',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        // Vérifier que l'installation appartient au club
        $facility = ClubFacility::where('id', $request->facility_id)
            ->where('club_id', $clubId)
            ->first();

        if (!$facility) {
            return response()->json([
                'message' => 'Installation non trouvée pour ce club'
            ], 404);
        }

        // Vérifier les conflits d'horaires
        $conflicts = $this->checkTimeConflicts($clubId, $request->facility_id, $request->day_of_week, $request->start_time, $request->end_time, $request->start_date, $request->end_date);
        
        if (!empty($conflicts)) {
            return response()->json([
                'message' => 'Conflit d\'horaires détecté',
                'conflicts' => $conflicts
            ], 422);
        }

        $courseSlot = CourseSlot::create([
            'club_id' => $clubId,
            'facility_id' => $request->facility_id,
            'course_type_id' => $request->course_type_id,
            'name' => $request->name,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'day_of_week' => $request->day_of_week,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'max_students' => $request->max_students,
            'price' => $request->price,
            'is_recurring' => $request->get('is_recurring', true),
            'notes' => $request->notes
        ]);

        return response()->json([
            'message' => 'Plage de cours créée avec succès',
            'course_slot' => $courseSlot->load(['facility', 'courseType'])
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/clubs/{clubId}/facilities",
     *     summary="Récupérer les installations du club",
     *     tags={"Club Course Management"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="clubId",
     *         in="path",
     *         required=true,
     *         description="ID du club",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Liste des installations",
     *         @OA\JsonContent(
     *             @OA\Property(property="facilities", type="array", @OA\Items(type="object"))
     *         )
     *     )
     * )
     */
    public function getFacilities($clubId): JsonResponse
    {
        $club = Club::findOrFail($clubId);
        $facilities = $club->activeFacilities()->get();

        return response()->json([
            'facilities' => $facilities
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/clubs/{clubId}/facilities",
     *     summary="Créer une nouvelle installation",
     *     tags={"Club Course Management"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="clubId",
     *         in="path",
     *         required=true,
     *         description="ID du club",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "type"},
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="type", type="string"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="capacity", type="integer"),
     *             @OA\Property(property="equipment", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="is_indoor", type="boolean")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Installation créée avec succès"),
     *     @OA\Response(response=422, description="Erreur de validation")
     * )
     */
    public function createFacility(Request $request, $clubId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'description' => 'nullable|string',
            'capacity' => 'required|integer|min:1|max:10',
            'equipment' => 'nullable|array',
            'equipment.*' => 'string|max:255',
            'is_indoor' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        $facility = ClubFacility::create([
            'club_id' => $clubId,
            'name' => $request->name,
            'type' => $request->type,
            'description' => $request->description,
            'capacity' => $request->capacity,
            'equipment' => $request->equipment,
            'is_indoor' => $request->get('is_indoor', false)
        ]);

        return response()->json([
            'message' => 'Installation créée avec succès',
            'facility' => $facility
        ], 201);
    }

    /**
     * Générer les données du calendrier pour la période donnée
     */
    private function generateCalendarData($courseSlots, $startDate, $endDate): array
    {
        $calendarData = [];
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        foreach ($courseSlots as $slot) {
            $dates = $slot->generateDatesForRange($startDate, $endDate);
            
            foreach ($dates as $date) {
                $assignment = $slot->assignments->where('assignment_date', $date)->first();
                
                $calendarData[] = [
                    'id' => $slot->id,
                    'name' => $slot->name,
                    'facility' => $slot->facility->name,
                    'course_type' => $slot->courseType->name,
                    'date' => $date,
                    'day_of_week' => $slot->day_of_week,
                    'start_time' => $slot->start_time->format('H:i'),
                    'end_time' => $slot->end_time->format('H:i'),
                    'duration' => $slot->duration,
                    'max_students' => $slot->max_students,
                    'price' => $slot->price,
                    'is_assigned' => !is_null($assignment),
                    'assignment' => $assignment ? [
                        'teacher_name' => $assignment->teacher->user->name,
                        'status' => $assignment->status,
                        'hourly_rate' => $assignment->hourly_rate
                    ] : null
                ];
            }
        }

        return $calendarData;
    }

    /**
     * Calculer les statistiques du tableau de bord
     */
    private function calculateDashboardStats($courseSlots, $startDate, $endDate): array
    {
        $totalSlots = $courseSlots->count();
        $assignedSlots = $courseSlots->filter(function($slot) use ($startDate, $endDate) {
            return $slot->assignments->whereBetween('assignment_date', [$startDate, $endDate])->isNotEmpty();
        })->count();
        
        $unassignedSlots = $totalSlots - $assignedSlots;
        $assignmentRate = $totalSlots > 0 ? round(($assignedSlots / $totalSlots) * 100, 2) : 0;

        return [
            'total_slots' => $totalSlots,
            'assigned_slots' => $assignedSlots,
            'unassigned_slots' => $unassignedSlots,
            'assignment_rate' => $assignmentRate,
            'facilities_count' => $courseSlots->pluck('facility_id')->unique()->count(),
            'course_types_count' => $courseSlots->pluck('course_type_id')->unique()->count()
        ];
    }

    /**
     * Vérifier les conflits d'horaires
     */
    private function checkTimeConflicts($clubId, $facilityId, $dayOfWeek, $startTime, $endTime, $startDate, $endDate): array
    {
        $conflicts = [];

        $existingSlots = CourseSlot::where('club_id', $clubId)
            ->where('facility_id', $facilityId)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->where(function($query) use ($startDate, $endDate) {
                $query->whereNull('end_date')
                      ->orWhere('end_date', '>=', $startDate);
            })
            ->where('start_date', '<=', $endDate)
            ->get();

        foreach ($existingSlots as $slot) {
            if ($this->timesOverlap($startTime, $endTime, $slot->start_time->format('H:i'), $slot->end_time->format('H:i'))) {
                $conflicts[] = [
                    'slot_id' => $slot->id,
                    'slot_name' => $slot->name,
                    'conflicting_time' => $slot->time_range
                ];
            }
        }

        return $conflicts;
    }

    /**
     * Vérifier si deux plages horaires se chevauchent
     */
    private function timesOverlap($start1, $end1, $start2, $end2): bool
    {
        $start1 = Carbon::parse($start1);
        $end1 = Carbon::parse($end1);
        $start2 = Carbon::parse($start2);
        $end2 = Carbon::parse($end2);

        return $start1->lt($end2) && $start2->lt($end1);
    }
}
