<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Club;
use App\Models\CourseSlot;
use App\Models\CourseAssignment;
use App\Models\TeacherContract;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

/**
 * @OA\Tag(
 *     name="Teacher Assignment",
 *     description="Système d'affectation des enseignants"
 * )
 */
class TeacherAssignmentController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/clubs/{clubId}/assignments",
     *     summary="Récupérer les affectations du club",
     *     tags={"Teacher Assignment"},
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
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Statut des affectations",
     *         @OA\Schema(type="string", enum={"assigned", "confirmed", "completed", "cancelled", "no_show"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Liste des affectations",
     *         @OA\JsonContent(
     *             @OA\Property(property="assignments", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="stats", type="object")
     *         )
     *     )
     * )
     */
    public function getAssignments(Request $request, $clubId): JsonResponse
    {
        $club = Club::findOrFail($clubId);
        
        $startDate = $request->get('start_date', Carbon::now()->startOfWeek()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->endOfWeek()->format('Y-m-d'));
        $status = $request->get('status');

        $query = $club->courseAssignments()
            ->with(['courseSlot.facility', 'courseSlot.courseType', 'teacher.user', 'contract'])
            ->whereBetween('assignment_date', [$startDate, $endDate]);

        if ($status) {
            $query->where('status', $status);
        }

        $assignments = $query->orderBy('assignment_date')
            ->orderBy('courseSlot.start_time')
            ->get();

        $stats = $this->calculateAssignmentStats($assignments);

        return response()->json([
            'assignments' => $assignments,
            'stats' => $stats,
            'date_range' => [
                'start' => $startDate,
                'end' => $endDate
            ]
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/clubs/{clubId}/assignments/auto-assign",
     *     summary="Affectation automatique des enseignants",
     *     tags={"Teacher Assignment"},
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
     *             required={"start_date", "end_date"},
     *             @OA\Property(property="start_date", type="string", format="date"),
     *             @OA\Property(property="end_date", type="string", format="date"),
     *             @OA\Property(property="force_reassign", type="boolean", description="Forcer la réaffectation des cours déjà assignés")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Résultat de l'affectation automatique",
     *         @OA\JsonContent(
     *             @OA\Property(property="assigned_count", type="integer"),
     *             @OA\Property(property="unassigned_count", type="integer"),
     *             @OA\Property(property="assignments", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="unassigned_slots", type="array", @OA\Items(type="object"))
     *         )
     *     )
     * )
     */
    public function autoAssign(Request $request, $clubId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'force_reassign' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        $club = Club::findOrFail($clubId);
        $startDate = $request->start_date;
        $endDate = $request->end_date;
        $forceReassign = $request->get('force_reassign', false);

        // Récupérer les plages de cours non assignées
        $courseSlots = $club->activeCourseSlots()
            ->with(['facility', 'courseType'])
            ->where(function($query) use ($startDate, $endDate) {
                $query->whereNull('end_date')
                      ->orWhere('end_date', '>=', $startDate);
            })
            ->where('start_date', '<=', $endDate)
            ->get();

        $assignments = [];
        $unassignedSlots = [];

        foreach ($courseSlots as $slot) {
            $dates = $slot->generateDatesForRange($startDate, $endDate);
            
            foreach ($dates as $date) {
                $existingAssignment = CourseAssignment::where('course_slot_id', $slot->id)
                    ->where('assignment_date', $date)
                    ->first();

                if ($existingAssignment && !$forceReassign) {
                    continue;
                }

                // Trouver le meilleur enseignant pour cette plage
                $teacher = $this->findBestTeacherForSlot($clubId, $slot, $date);

                if ($teacher) {
                    $contract = $teacher->getContractForClub($clubId);
                    
                    if ($existingAssignment) {
                        $existingAssignment->update([
                            'teacher_id' => $teacher->id,
                            'contract_id' => $contract->id,
                            'hourly_rate' => $contract->hourly_rate,
                            'status' => 'assigned'
                        ]);
                        $assignment = $existingAssignment;
                    } else {
                        $assignment = CourseAssignment::create([
                            'course_slot_id' => $slot->id,
                            'teacher_id' => $teacher->id,
                            'contract_id' => $contract->id,
                            'assignment_date' => $date,
                            'status' => 'assigned',
                            'hourly_rate' => $contract->hourly_rate
                        ]);
                    }

                    $assignments[] = $assignment->load(['teacher.user', 'contract']);
                } else {
                    $unassignedSlots[] = [
                        'slot' => $slot,
                        'date' => $date,
                        'reason' => 'Aucun enseignant disponible'
                    ];
                }
            }
        }

        return response()->json([
            'message' => 'Affectation automatique terminée',
            'assigned_count' => count($assignments),
            'unassigned_count' => count($unassignedSlots),
            'assignments' => $assignments,
            'unassigned_slots' => $unassignedSlots
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/clubs/{clubId}/assignments/{assignmentId}/assign",
     *     summary="Affecter manuellement un enseignant",
     *     tags={"Teacher Assignment"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="clubId",
     *         in="path",
     *         required=true,
     *         description="ID du club",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="assignmentId",
     *         in="path",
     *         required=true,
     *         description="ID de l'affectation",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"teacher_id"},
     *             @OA\Property(property="teacher_id", type="integer"),
     *             @OA\Property(property="hourly_rate", type="number", format="float")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Enseignant affecté avec succès"),
     *     @OA\Response(response=422, description="Erreur de validation")
     * )
     */
    public function assignTeacher(Request $request, $clubId, $assignmentId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'teacher_id' => 'required|exists:teachers,id',
            'hourly_rate' => 'nullable|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        $assignment = CourseAssignment::whereHas('courseSlot', function($query) use ($clubId) {
            $query->where('club_id', $clubId);
        })->findOrFail($assignmentId);

        $teacher = Teacher::findOrFail($request->teacher_id);
        $contract = $teacher->getContractForClub($clubId);

        if (!$contract) {
            return response()->json([
                'message' => 'Aucun contrat actif trouvé pour cet enseignant dans ce club'
            ], 422);
        }

        // Vérifier les contraintes
        $slot = $assignment->courseSlot;
        $canTeach = $teacher->canTeachAt(
            $clubId,
            $slot->day_of_week,
            $slot->start_time->format('H:i'),
            $slot->end_time->format('H:i'),
            $assignment->assignment_date
        );

        if (!$canTeach) {
            return response()->json([
                'message' => 'Cet enseignant ne peut pas enseigner à cette heure selon son contrat'
            ], 422);
        }

        $hourlyRate = $request->hourly_rate ?? $contract->hourly_rate;

        $assignment->update([
            'teacher_id' => $teacher->id,
            'contract_id' => $contract->id,
            'hourly_rate' => $hourlyRate,
            'status' => 'assigned'
        ]);

        return response()->json([
            'message' => 'Enseignant affecté avec succès',
            'assignment' => $assignment->load(['teacher.user', 'contract'])
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/clubs/{clubId}/assignments/{assignmentId}/confirm",
     *     summary="Confirmer une affectation",
     *     tags={"Teacher Assignment"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="clubId",
     *         in="path",
     *         required=true,
     *         description="ID du club",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="assignmentId",
     *         in="path",
     *         required=true,
     *         description="ID de l'affectation",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Affectation confirmée avec succès")
     * )
     */
    public function confirmAssignment($clubId, $assignmentId): JsonResponse
    {
        $assignment = CourseAssignment::whereHas('courseSlot', function($query) use ($clubId) {
            $query->where('club_id', $clubId);
        })->findOrFail($assignmentId);

        if ($assignment->confirm()) {
            return response()->json([
                'message' => 'Affectation confirmée avec succès',
                'assignment' => $assignment->load(['teacher.user', 'contract'])
            ]);
        }

        return response()->json([
            'message' => 'Impossible de confirmer cette affectation'
        ], 422);
    }

    /**
     * @OA\Get(
     *     path="/api/clubs/{clubId}/teachers/available",
     *     summary="Récupérer les enseignants disponibles",
     *     tags={"Teacher Assignment"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="clubId",
     *         in="path",
     *         required=true,
     *         description="ID du club",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="day_of_week",
     *         in="query",
     *         description="Jour de la semaine",
     *         @OA\Schema(type="string", enum={"monday", "tuesday", "wednesday", "thursday", "friday", "saturday", "sunday"})
     *     ),
     *     @OA\Parameter(
     *         name="start_time",
     *         in="query",
     *         description="Heure de début (format H:i)",
     *         @OA\Schema(type="string", format="time")
     *     ),
     *     @OA\Parameter(
     *         name="end_time",
     *         in="query",
     *         description="Heure de fin (format H:i)",
     *         @OA\Schema(type="string", format="time")
     *     ),
     *     @OA\Parameter(
     *         name="date",
     *         in="query",
     *         description="Date spécifique (format Y-m-d)",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Liste des enseignants disponibles",
     *         @OA\JsonContent(
     *             @OA\Property(property="teachers", type="array", @OA\Items(type="object"))
     *         )
     *     )
     * )
     */
    public function getAvailableTeachers(Request $request, $clubId): JsonResponse
    {
        $club = Club::findOrFail($clubId);
        
        $dayOfWeek = $request->get('day_of_week');
        $startTime = $request->get('start_time');
        $endTime = $request->get('end_time');
        $date = $request->get('date');

        $teachers = $club->activeTeachers()
            ->with(['user', 'contracts' => function($query) use ($clubId) {
                $query->where('club_id', $clubId)->where('is_active', true);
            }])
            ->get()
            ->filter(function($teacher) use ($clubId, $dayOfWeek, $startTime, $endTime, $date) {
                if ($dayOfWeek && $startTime && $endTime) {
                    return $teacher->canTeachAt($clubId, $dayOfWeek, $startTime, $endTime, $date);
                }
                return true;
            });

        return response()->json([
            'teachers' => $teachers->map(function($teacher) {
                $contract = $teacher->getContractForClub($teacher->clubs->first()->id);
                return [
                    'id' => $teacher->id,
                    'name' => $teacher->user->name,
                    'email' => $teacher->user->email,
                    'specialties' => $teacher->specialties,
                    'hourly_rate' => $contract ? $contract->hourly_rate : $teacher->hourly_rate,
                    'contract_type' => $contract ? $contract->contract_type : null,
                    'is_available' => $teacher->is_available
                ];
            })
        ]);
    }

    /**
     * Trouver le meilleur enseignant pour une plage donnée
     */
    private function findBestTeacherForSlot($clubId, $slot, $date): ?Teacher
    {
        $club = Club::find($clubId);
        $teachers = $club->activeTeachers()
            ->with(['contracts' => function($query) use ($clubId) {
                $query->where('club_id', $clubId)->where('is_active', true);
            }])
            ->get();

        $availableTeachers = $teachers->filter(function($teacher) use ($clubId, $slot, $date) {
            return $teacher->canTeachAt(
                $clubId,
                $slot->day_of_week,
                $slot->start_time->format('H:i'),
                $slot->end_time->format('H:i'),
                $date
            );
        });

        if ($availableTeachers->isEmpty()) {
            return null;
        }

        // Prioriser selon les critères suivants :
        // 1. Enseignants avec moins d'heures cette semaine
        // 2. Enseignants avec contrat permanent
        // 3. Enseignants avec meilleure note
        return $availableTeachers->sortBy(function($teacher) use ($clubId, $date) {
            $contract = $teacher->getContractForClub($clubId);
            $hoursThisWeek = $contract ? $contract->getHoursWorkedInWeek($date) : 0;
            
            $priority = 0;
            $priority += $hoursThisWeek * 10; // Moins d'heures = meilleure priorité
            $priority += $contract && $contract->contract_type === 'permanent' ? -5 : 0;
            $priority += (5 - $teacher->rating) * 2; // Meilleure note = meilleure priorité
            
            return $priority;
        })->first();
    }

    /**
     * Calculer les statistiques des affectations
     */
    private function calculateAssignmentStats($assignments): array
    {
        $total = $assignments->count();
        $byStatus = $assignments->groupBy('status')->map->count();
        
        $totalCost = $assignments->sum('total_cost');
        $totalHours = $assignments->sum(function($assignment) {
            return ($assignment->actual_duration ?? $assignment->courseSlot->duration) / 60;
        });

        return [
            'total_assignments' => $total,
            'by_status' => $byStatus,
            'total_cost' => $totalCost,
            'total_hours' => $totalHours,
            'average_hourly_rate' => $totalHours > 0 ? $totalCost / $totalHours : 0
        ];
    }
}
