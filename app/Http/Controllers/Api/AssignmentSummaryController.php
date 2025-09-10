<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Club;
use App\Models\CourseAssignment;
use App\Models\CourseSlot;
use App\Models\TeacherContract;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

/**
 * @OA\Tag(
 *     name="Assignment Summary",
 *     description="Tableau récapitulatif des affectations"
 * )
 */
class AssignmentSummaryController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/clubs/{clubId}/assignment-summary",
     *     summary="Tableau récapitulatif des affectations",
     *     tags={"Assignment Summary"},
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
     *         name="group_by",
     *         in="query",
     *         description="Grouper par (teacher, facility, course_type, date)",
     *         @OA\Schema(type="string", enum={"teacher", "facility", "course_type", "date"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Résumé des affectations",
     *         @OA\JsonContent(
     *             @OA\Property(property="summary", type="object"),
     *             @OA\Property(property="assignments", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="missing_assignments", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="teacher_stats", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="facility_stats", type="array", @OA\Items(type="object"))
     *         )
     *     )
     * )
     */
    public function getSummary(Request $request, $clubId): JsonResponse
    {
        $club = Club::findOrFail($clubId);
        
        $startDate = $request->get('start_date', Carbon::now()->startOfWeek()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->endOfWeek()->format('Y-m-d'));
        $groupBy = $request->get('group_by', 'date');

        // Récupérer toutes les plages de cours pour la période
        $courseSlots = $club->activeCourseSlots()
            ->with(['facility', 'courseType', 'assignments' => function($query) use ($startDate, $endDate) {
                $query->whereBetween('assignment_date', [$startDate, $endDate])
                      ->with(['teacher.user', 'contract']);
            }])
            ->where(function($query) use ($startDate, $endDate) {
                $query->whereNull('end_date')
                      ->orWhere('end_date', '>=', $startDate);
            })
            ->where('start_date', '<=', $endDate)
            ->get();

        // Générer toutes les dates possibles pour chaque plage
        $allSlots = [];
        foreach ($courseSlots as $slot) {
            $dates = $slot->generateDatesForRange($startDate, $endDate);
            foreach ($dates as $date) {
                $assignment = $slot->assignments->where('assignment_date', $date)->first();
                $allSlots[] = [
                    'slot' => $slot,
                    'date' => $date,
                    'assignment' => $assignment
                ];
            }
        }

        // Séparer les affectations et les manquantes
        $assignments = collect($allSlots)->where('assignment', '!=', null);
        $missingAssignments = collect($allSlots)->where('assignment', null);

        // Calculer les statistiques
        $summary = $this->calculateSummaryStats($assignments, $missingAssignments);
        $teacherStats = $this->calculateTeacherStats($assignments);
        $facilityStats = $this->calculateFacilityStats($assignments, $missingAssignments);

        // Grouper les données selon le paramètre
        $groupedData = $this->groupAssignments($assignments, $groupBy);

        return response()->json([
            'summary' => $summary,
            'assignments' => $groupedData,
            'missing_assignments' => $missingAssignments->map(function($item) {
                return [
                    'slot_name' => $item['slot']->name,
                    'facility' => $item['slot']->facility->name,
                    'course_type' => $item['slot']->courseType->name,
                    'date' => $item['date'],
                    'day_of_week' => $item['slot']->day_of_week,
                    'time_range' => $item['slot']->time_range,
                    'max_students' => $item['slot']->max_students,
                    'price' => $item['slot']->price
                ];
            }),
            'teacher_stats' => $teacherStats,
            'facility_stats' => $facilityStats,
            'date_range' => [
                'start' => $startDate,
                'end' => $endDate
            ]
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/clubs/{clubId}/assignment-alerts",
     *     summary="Alertes d'affectations manquantes",
     *     tags={"Assignment Summary"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="clubId",
     *         in="path",
     *         required=true,
     *         description="ID du club",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="days_ahead",
     *         in="query",
     *         description="Nombre de jours à venir à vérifier",
     *         @OA\Schema(type="integer", default=7)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Liste des alertes",
     *         @OA\JsonContent(
     *             @OA\Property(property="alerts", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="critical_alerts", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="warning_alerts", type="array", @OA\Items(type="object"))
     *         )
     *     )
     * )
     */
    public function getAlerts(Request $request, $clubId): JsonResponse
    {
        $club = Club::findOrFail($clubId);
        $daysAhead = $request->get('days_ahead', 7);
        
        $startDate = Carbon::now()->format('Y-m-d');
        $endDate = Carbon::now()->addDays($daysAhead)->format('Y-m-d');

        // Récupérer les plages non assignées
        $courseSlots = $club->activeCourseSlots()
            ->with(['facility', 'courseType'])
            ->where(function($query) use ($startDate, $endDate) {
                $query->whereNull('end_date')
                      ->orWhere('end_date', '>=', $startDate);
            })
            ->where('start_date', '<=', $endDate)
            ->get();

        $alerts = [];
        $criticalAlerts = [];
        $warningAlerts = [];

        foreach ($courseSlots as $slot) {
            $dates = $slot->generateDatesForRange($startDate, $endDate);
            
            foreach ($dates as $date) {
                $assignment = CourseAssignment::where('course_slot_id', $slot->id)
                    ->where('assignment_date', $date)
                    ->first();

                if (!$assignment) {
                    $daysUntilCourse = Carbon::parse($date)->diffInDays(Carbon::now());
                    
                    $alert = [
                        'slot_id' => $slot->id,
                        'slot_name' => $slot->name,
                        'facility' => $slot->facility->name,
                        'course_type' => $slot->courseType->name,
                        'date' => $date,
                        'day_of_week' => $slot->day_of_week,
                        'time_range' => $slot->time_range,
                        'days_until' => $daysUntilCourse,
                        'priority' => $this->calculateAlertPriority($daysUntilCourse, $slot)
                    ];

                    $alerts[] = $alert;

                    if ($daysUntilCourse <= 1) {
                        $criticalAlerts[] = $alert;
                    } elseif ($daysUntilCourse <= 3) {
                        $warningAlerts[] = $alert;
                    }
                }
            }
        }

        // Trier par priorité et date
        usort($alerts, function($a, $b) {
            if ($a['priority'] === $b['priority']) {
                return strcmp($a['date'], $b['date']);
            }
            return $a['priority'] - $b['priority'];
        });

        return response()->json([
            'alerts' => $alerts,
            'critical_alerts' => $criticalAlerts,
            'warning_alerts' => $warningAlerts,
            'total_alerts' => count($alerts),
            'critical_count' => count($criticalAlerts),
            'warning_count' => count($warningAlerts)
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/clubs/{clubId}/teacher-workload",
     *     summary="Charge de travail des enseignants",
     *     tags={"Assignment Summary"},
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
     *         description="Charge de travail par enseignant",
     *         @OA\JsonContent(
     *             @OA\Property(property="workload", type="array", @OA\Items(type="object"))
     *         )
     *     )
     * )
     */
    public function getTeacherWorkload(Request $request, $clubId): JsonResponse
    {
        $club = Club::findOrFail($clubId);
        
        $startDate = $request->get('start_date', Carbon::now()->startOfWeek()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->endOfWeek()->format('Y-m-d'));

        $assignments = $club->courseAssignments()
            ->with(['teacher.user', 'contract', 'courseSlot'])
            ->whereBetween('assignment_date', [$startDate, $endDate])
            ->whereIn('status', ['assigned', 'confirmed', 'completed'])
            ->get();

        $workload = $assignments->groupBy('teacher_id')->map(function($teacherAssignments) {
            $teacher = $teacherAssignments->first()->teacher;
            $contract = $teacherAssignments->first()->contract;
            
            $totalHours = $teacherAssignments->sum(function($assignment) {
                return ($assignment->actual_duration ?? $assignment->courseSlot->duration) / 60;
            });
            
            $totalCost = $teacherAssignments->sum('total_cost');
            $assignmentCount = $teacherAssignments->count();
            
            $maxHours = $contract ? $contract->max_hours_per_week : null;
            $minHours = $contract ? $contract->min_hours_per_week : null;
            
            $workloadPercentage = $maxHours ? ($totalHours / $maxHours) * 100 : null;
            
            return [
                'teacher_id' => $teacher->id,
                'teacher_name' => $teacher->user->name,
                'contract_type' => $contract ? $contract->contract_type : null,
                'total_hours' => round($totalHours, 2),
                'total_cost' => $totalCost,
                'assignment_count' => $assignmentCount,
                'max_hours' => $maxHours,
                'min_hours' => $minHours,
                'workload_percentage' => $workloadPercentage ? round($workloadPercentage, 2) : null,
                'is_overloaded' => $maxHours && $totalHours > $maxHours,
                'is_underloaded' => $minHours && $totalHours < $minHours,
                'assignments' => $teacherAssignments->map(function($assignment) {
                    return [
                        'date' => $assignment->assignment_date->format('Y-m-d'),
                        'slot_name' => $assignment->courseSlot->name,
                        'facility' => $assignment->courseSlot->facility->name,
                        'time_range' => $assignment->courseSlot->time_range,
                        'duration' => $assignment->actual_duration ?? $assignment->courseSlot->duration,
                        'status' => $assignment->status,
                        'hourly_rate' => $assignment->hourly_rate
                    ];
                })
            ];
        });

        return response()->json([
            'workload' => $workload->values(),
            'date_range' => [
                'start' => $startDate,
                'end' => $endDate
            ]
        ]);
    }

    /**
     * Calculer les statistiques récapitulatives
     */
    private function calculateSummaryStats($assignments, $missingAssignments): array
    {
        $totalSlots = $assignments->count() + $missingAssignments->count();
        $assignedSlots = $assignments->count();
        $missingSlots = $missingAssignments->count();
        
        $assignmentRate = $totalSlots > 0 ? round(($assignedSlots / $totalSlots) * 100, 2) : 0;
        
        $totalHours = $assignments->sum(function($item) {
            return ($item['assignment']->actual_duration ?? $item['slot']->duration) / 60;
        });
        
        $totalCost = $assignments->sum(function($item) {
            return $item['assignment']->total_cost;
        });

        return [
            'total_slots' => $totalSlots,
            'assigned_slots' => $assignedSlots,
            'missing_slots' => $missingSlots,
            'assignment_rate' => $assignmentRate,
            'total_hours' => round($totalHours, 2),
            'total_cost' => $totalCost,
            'average_hourly_rate' => $totalHours > 0 ? round($totalCost / $totalHours, 2) : 0
        ];
    }

    /**
     * Calculer les statistiques par enseignant
     */
    private function calculateTeacherStats($assignments): array
    {
        return $assignments->groupBy(function($item) {
            return $item['assignment']->teacher_id;
        })->map(function($teacherAssignments) {
            $teacher = $teacherAssignments->first()['assignment']->teacher;
            $totalHours = $teacherAssignments->sum(function($item) {
                return ($item['assignment']->actual_duration ?? $item['slot']->duration) / 60;
            });
            
            return [
                'teacher_id' => $teacher->id,
                'teacher_name' => $teacher->user->name,
                'assignment_count' => $teacherAssignments->count(),
                'total_hours' => round($totalHours, 2),
                'total_cost' => $teacherAssignments->sum(function($item) {
                    return $item['assignment']->total_cost;
                })
            ];
        })->values()->toArray();
    }

    /**
     * Calculer les statistiques par installation
     */
    private function calculateFacilityStats($assignments, $missingAssignments): array
    {
        $allSlots = $assignments->concat($missingAssignments);
        
        return $allSlots->groupBy(function($item) {
            return $item['slot']->facility_id;
        })->map(function($facilitySlots) {
            $facility = $facilitySlots->first()['slot']->facility;
            $assigned = $facilitySlots->where('assignment', '!=', null)->count();
            $missing = $facilitySlots->where('assignment', null)->count();
            
            return [
                'facility_id' => $facility->id,
                'facility_name' => $facility->name,
                'total_slots' => $facilitySlots->count(),
                'assigned_slots' => $assigned,
                'missing_slots' => $missing,
                'assignment_rate' => $facilitySlots->count() > 0 ? round(($assigned / $facilitySlots->count()) * 100, 2) : 0
            ];
        })->values()->toArray();
    }

    /**
     * Grouper les affectations selon le critère demandé
     */
    private function groupAssignments($assignments, $groupBy): array
    {
        switch ($groupBy) {
            case 'teacher':
                return $assignments->groupBy(function($item) {
                    return $item['assignment']->teacher_id;
                })->map(function($teacherAssignments) {
                    $teacher = $teacherAssignments->first()['assignment']->teacher;
                    return [
                        'teacher_id' => $teacher->id,
                        'teacher_name' => $teacher->user->name,
                        'assignments' => $teacherAssignments->map(function($item) {
                            return $this->formatAssignmentData($item);
                        })
                    ];
                })->values()->toArray();
                
            case 'facility':
                return $assignments->groupBy(function($item) {
                    return $item['slot']->facility_id;
                })->map(function($facilityAssignments) {
                    $facility = $facilityAssignments->first()['slot']->facility;
                    return [
                        'facility_id' => $facility->id,
                        'facility_name' => $facility->name,
                        'assignments' => $facilityAssignments->map(function($item) {
                            return $this->formatAssignmentData($item);
                        })
                    ];
                })->values()->toArray();
                
            case 'course_type':
                return $assignments->groupBy(function($item) {
                    return $item['slot']->course_type_id;
                })->map(function($typeAssignments) {
                    $courseType = $typeAssignments->first()['slot']->courseType;
                    return [
                        'course_type_id' => $courseType->id,
                        'course_type_name' => $courseType->name,
                        'assignments' => $typeAssignments->map(function($item) {
                            return $this->formatAssignmentData($item);
                        })
                    ];
                })->values()->toArray();
                
            case 'date':
            default:
                return $assignments->groupBy('date')->map(function($dateAssignments) {
                    return [
                        'date' => $dateAssignments->first()['date'],
                        'assignments' => $dateAssignments->map(function($item) {
                            return $this->formatAssignmentData($item);
                        })
                    ];
                })->values()->toArray();
        }
    }

    /**
     * Formater les données d'affectation
     */
    private function formatAssignmentData($item): array
    {
        $assignment = $item['assignment'];
        $slot = $item['slot'];
        
        return [
            'assignment_id' => $assignment->id,
            'slot_name' => $slot->name,
            'facility' => $slot->facility->name,
            'course_type' => $slot->courseType->name,
            'teacher_name' => $assignment->teacher->user->name,
            'date' => $assignment->assignment_date->format('Y-m-d'),
            'day_of_week' => $slot->day_of_week,
            'time_range' => $slot->time_range,
            'duration' => $assignment->actual_duration ?? $slot->duration,
            'status' => $assignment->status,
            'hourly_rate' => $assignment->hourly_rate,
            'total_cost' => $assignment->total_cost
        ];
    }

    /**
     * Calculer la priorité d'une alerte
     */
    private function calculateAlertPriority($daysUntilCourse, $slot): int
    {
        $priority = $daysUntilCourse;
        
        // Priorité plus élevée pour les cours du weekend
        if (in_array($slot->day_of_week, ['saturday', 'sunday'])) {
            $priority -= 2;
        }
        
        // Priorité plus élevée pour les cours avec plus d'étudiants
        if ($slot->max_students > 6) {
            $priority -= 1;
        }
        
        return max(1, $priority);
    }
}
