<?php

namespace App\Services;

use App\Models\Club;
use App\Models\CourseSlot;
use App\Models\CourseAssignment;
use App\Models\TeacherContract;
use App\Models\Teacher;
use Carbon\Carbon;

class TeacherAssignmentService
{
    /**
     * Affectation automatique des enseignants pour une période donnée
     */
    public function autoAssignTeachers(Club $club, string $startDate, string $endDate, bool $forceReassign = false): array
    {
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
        $conflicts = [];

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
                $assignmentResult = $this->findBestTeacherForSlot($club, $slot, $date);

                if ($assignmentResult['teacher']) {
                    $teacher = $assignmentResult['teacher'];
                    $contract = $assignmentResult['contract'];
                    
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
                        'reason' => $assignmentResult['reason'] ?? 'Aucun enseignant disponible'
                    ];
                }
            }
        }

        return [
            'assignments' => $assignments,
            'unassigned_slots' => $unassignedSlots,
            'conflicts' => $conflicts
        ];
    }

    /**
     * Trouver le meilleur enseignant pour une plage donnée
     */
    public function findBestTeacherForSlot(Club $club, CourseSlot $slot, string $date): array
    {
        $teachers = $club->activeTeachers()
            ->with(['contracts' => function($query) use ($club) {
                $query->where('club_id', $club->id)->where('is_active', true);
            }])
            ->get();

        $availableTeachers = $teachers->filter(function($teacher) use ($club, $slot, $date) {
            return $this->canTeacherTeachSlot($teacher, $club, $slot, $date);
        });

        if ($availableTeachers->isEmpty()) {
            return [
                'teacher' => null,
                'contract' => null,
                'reason' => 'Aucun enseignant disponible'
            ];
        }

        // Calculer le score pour chaque enseignant
        $scoredTeachers = $availableTeachers->map(function($teacher) use ($club, $slot, $date) {
            $contract = $teacher->getContractForClub($club->id);
            $score = $this->calculateTeacherScore($teacher, $contract, $slot, $date);
            
            return [
                'teacher' => $teacher,
                'contract' => $contract,
                'score' => $score
            ];
        });

        // Trier par score (plus bas = meilleur)
        $bestTeacher = $scoredTeachers->sortBy('score')->first();

        return [
            'teacher' => $bestTeacher['teacher'],
            'contract' => $bestTeacher['contract'],
            'score' => $bestTeacher['score']
        ];
    }

    /**
     * Vérifier si un enseignant peut enseigner une plage donnée
     */
    public function canTeacherTeachSlot(Teacher $teacher, Club $club, CourseSlot $slot, string $date): bool
    {
        $contract = $teacher->getContractForClub($club->id);
        
        if (!$contract) {
            return false;
        }

        // Vérifier les contraintes de base
        if (!$contract->isActiveForDate($date)) {
            return false;
        }

        if (!$contract->canTeachOnDay($slot->day_of_week)) {
            return false;
        }

        if (!$contract->canTeachAtTime($slot->start_time->format('H:i'), $slot->end_time->format('H:i'))) {
            return false;
        }

        // Vérifier les disciplines
        if (!$contract->canTeachDiscipline($slot->course_type_id)) {
            return false;
        }

        // Vérifier les heures max par semaine
        if ($contract->hasReachedMaxHoursForWeek($date)) {
            return false;
        }

        // Vérifier les conflits d'horaires existants
        if ($this->hasTimeConflict($teacher, $slot, $date)) {
            return false;
        }

        return true;
    }

    /**
     * Calculer le score d'un enseignant pour une plage (plus bas = meilleur)
     */
    private function calculateTeacherScore(Teacher $teacher, TeacherContract $contract, CourseSlot $slot, string $date): int
    {
        $score = 0;

        // Priorité par type de contrat
        $contractPriority = [
            'permanent' => 0,
            'temporary' => 1,
            'seasonal' => 2,
            'freelance' => 3
        ];
        $score += $contractPriority[$contract->contract_type] ?? 5;

        // Priorité par heures déjà travaillées cette semaine
        $hoursThisWeek = $contract->getHoursWorkedInWeek($date);
        $score += $hoursThisWeek * 2;

        // Priorité par note de l'enseignant
        $score += (5 - $teacher->rating) * 3;

        // Priorité par nombre total de cours
        $score += $teacher->total_lessons / 100;

        // Priorité par installation préférée
        if (in_array($slot->facility_id, $contract->preferred_facilities ?? [])) {
            $score -= 2;
        }

        // Priorité par spécialités
        if (in_array($slot->courseType->discipline_id, $teacher->specialties ?? [])) {
            $score -= 1;
        }

        return max(0, $score);
    }

    /**
     * Vérifier les conflits d'horaires
     */
    private function hasTimeConflict(Teacher $teacher, CourseSlot $slot, string $date): bool
    {
        $existingAssignments = CourseAssignment::where('teacher_id', $teacher->id)
            ->where('assignment_date', $date)
            ->whereIn('status', ['assigned', 'confirmed'])
            ->with('courseSlot')
            ->get();

        foreach ($existingAssignments as $assignment) {
            $existingSlot = $assignment->courseSlot;
            
            if ($this->timesOverlap(
                $slot->start_time->format('H:i'),
                $slot->end_time->format('H:i'),
                $existingSlot->start_time->format('H:i'),
                $existingSlot->end_time->format('H:i')
            )) {
                return true;
            }
        }

        return false;
    }

    /**
     * Vérifier si deux plages horaires se chevauchent
     */
    private function timesOverlap(string $start1, string $end1, string $start2, string $end2): bool
    {
        $start1 = Carbon::parse($start1);
        $end1 = Carbon::parse($end1);
        $start2 = Carbon::parse($start2);
        $end2 = Carbon::parse($end2);

        return $start1->lt($end2) && $start2->lt($end1);
    }

    /**
     * Optimiser les affectations existantes
     */
    public function optimizeAssignments(Club $club, string $startDate, string $endDate): array
    {
        $assignments = $club->courseAssignments()
            ->with(['courseSlot', 'teacher', 'contract'])
            ->whereBetween('assignment_date', [$startDate, $endDate])
            ->whereIn('status', ['assigned', 'confirmed'])
            ->get();

        $optimizations = [];
        $improvements = 0;

        foreach ($assignments as $assignment) {
            $slot = $assignment->courseSlot;
            $currentTeacher = $assignment->teacher;
            $currentContract = $assignment->contract;

            // Chercher un meilleur enseignant
            $betterTeacher = $this->findBetterTeacherForAssignment($club, $slot, $assignment->assignment_date, $currentTeacher);

            if ($betterTeacher && $betterTeacher['score'] < $this->calculateTeacherScore($currentTeacher, $currentContract, $slot, $assignment->assignment_date)) {
                $optimizations[] = [
                    'assignment_id' => $assignment->id,
                    'current_teacher' => $currentTeacher->user->name,
                    'suggested_teacher' => $betterTeacher['teacher']->user->name,
                    'improvement_score' => $this->calculateTeacherScore($currentTeacher, $currentContract, $slot, $assignment->assignment_date) - $betterTeacher['score']
                ];
                $improvements++;
            }
        }

        return [
            'optimizations' => $optimizations,
            'total_improvements' => $improvements,
            'optimization_rate' => $assignments->count() > 0 ? round(($improvements / $assignments->count()) * 100, 2) : 0
        ];
    }

    /**
     * Trouver un meilleur enseignant pour une affectation existante
     */
    private function findBetterTeacherForAssignment(Club $club, CourseSlot $slot, string $date, Teacher $excludeTeacher): ?array
    {
        $teachers = $club->activeTeachers()
            ->where('id', '!=', $excludeTeacher->id)
            ->with(['contracts' => function($query) use ($club) {
                $query->where('club_id', $club->id)->where('is_active', true);
            }])
            ->get();

        $availableTeachers = $teachers->filter(function($teacher) use ($club, $slot, $date) {
            return $this->canTeacherTeachSlot($teacher, $club, $slot, $date);
        });

        if ($availableTeachers->isEmpty()) {
            return null;
        }

        $scoredTeachers = $availableTeachers->map(function($teacher) use ($club, $slot, $date) {
            $contract = $teacher->getContractForClub($club->id);
            $score = $this->calculateTeacherScore($teacher, $contract, $slot, $date);
            
            return [
                'teacher' => $teacher,
                'contract' => $contract,
                'score' => $score
            ];
        });

        return $scoredTeachers->sortBy('score')->first();
    }

    /**
     * Analyser les contraintes non respectées
     */
    public function analyzeConstraintViolations(Club $club, string $startDate, string $endDate): array
    {
        $assignments = $club->courseAssignments()
            ->with(['courseSlot', 'teacher', 'contract'])
            ->whereBetween('assignment_date', [$startDate, $endDate])
            ->whereIn('status', ['assigned', 'confirmed'])
            ->get();

        $violations = [];

        foreach ($assignments as $assignment) {
            $slot = $assignment->courseSlot;
            $teacher = $assignment->teacher;
            $contract = $assignment->contract;

            $violation = [
                'assignment_id' => $assignment->id,
                'teacher_name' => $teacher->user->name,
                'slot_name' => $slot->name,
                'date' => $assignment->assignment_date,
                'violations' => []
            ];

            // Vérifier les heures max par semaine
            if ($contract->max_hours_per_week) {
                $hoursThisWeek = $contract->getHoursWorkedInWeek($assignment->assignment_date);
                if ($hoursThisWeek > $contract->max_hours_per_week) {
                    $violation['violations'][] = [
                        'type' => 'max_hours_exceeded',
                        'message' => "Heures max dépassées: {$hoursThisWeek}h / {$contract->max_hours_per_week}h"
                    ];
                }
            }

            // Vérifier les heures min par semaine
            if ($contract->min_hours_per_week) {
                $hoursThisWeek = $contract->getHoursWorkedInWeek($assignment->assignment_date);
                if ($hoursThisWeek < $contract->min_hours_per_week) {
                    $violation['violations'][] = [
                        'type' => 'min_hours_not_met',
                        'message' => "Heures min non atteintes: {$hoursThisWeek}h / {$contract->min_hours_per_week}h"
                    ];
                }
            }

            // Vérifier les jours indisponibles
            if (in_array($slot->day_of_week, $contract->unavailable_days ?? [])) {
                $violation['violations'][] = [
                    'type' => 'unavailable_day',
                    'message' => "Jour indisponible selon le contrat: {$slot->day_of_week}"
                ];
            }

            // Vérifier les horaires
            if ($contract->earliest_start_time && $slot->start_time->lt($contract->earliest_start_time)) {
                $violation['violations'][] = [
                    'type' => 'too_early',
                    'message' => "Heure trop précoce: {$slot->start_time->format('H:i')} < {$contract->earliest_start_time->format('H:i')}"
                ];
            }

            if ($contract->latest_end_time && $slot->end_time->gt($contract->latest_end_time)) {
                $violation['violations'][] = [
                    'type' => 'too_late',
                    'message' => "Heure trop tardive: {$slot->end_time->format('H:i')} > {$contract->latest_end_time->format('H:i')}"
                ];
            }

            if (!empty($violation['violations'])) {
                $violations[] = $violation;
            }
        }

        return [
            'violations' => $violations,
            'total_violations' => count($violations),
            'violation_count' => collect($violations)->sum(function($v) {
                return count($v['violations']);
            })
        ];
    }
}
