<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClubOpenSlot;
use App\Models\CourseType;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Contrôleur de debug pour vérifier les données de l'API
 */
class DebugController extends Controller
{
    /**
     * Vérifier les types de cours et le filtrage par club
     */
    public function checkCourseTypesFiltering(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'error' => 'Non authentifié'
                ], 401);
            }

            $result = [
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'role' => $user->role
                ],
                'club' => null,
                'club_disciplines' => [],
                'all_course_types' => [],
                'filtered_course_types' => [],
                'open_slots' => [],
                'filtering_applied' => false
            ];

            // Récupérer le club si l'utilisateur est un club manager
            if ($user->role === 'club') {
                $club = $user->getFirstClub();
                
                if ($club) {
                    $clubData = DB::table('clubs')->where('id', $club->id)->first();
                    
                    $result['club'] = [
                        'id' => $club->id,
                        'name' => $club->name,
                        'disciplines_raw' => $clubData->disciplines,
                        'disciplines_type' => gettype($clubData->disciplines),
                        'disciplines_parsed' => $club->disciplines,
                        'disciplines_is_array' => is_array($club->disciplines),
                        'disciplines_count' => is_array($club->disciplines) ? count($club->disciplines) : 0
                    ];
                    
                    $clubDisciplineIds = $club->disciplines ?? [];
                    $result['club_disciplines'] = $clubDisciplineIds;
                    $result['filtering_applied'] = !empty($clubDisciplineIds);

                    // Récupérer TOUS les types de cours
                    $allCourseTypes = CourseType::orderBy('name')->get();
                    $result['all_course_types'] = $allCourseTypes->map(function($ct) {
                        return [
                            'id' => $ct->id,
                            'name' => $ct->name,
                            'discipline_id' => $ct->discipline_id,
                            'duration_minutes' => $ct->duration_minutes,
                            'is_active' => $ct->is_active
                        ];
                    })->toArray();

                    // Appliquer le filtrage comme dans CourseTypeController
                    if (!empty($clubDisciplineIds)) {
                        $filteredCourseTypes = $allCourseTypes->filter(function($type) use ($clubDisciplineIds) {
                            // Garder les types génériques OU ceux du club
                            return (!$type->discipline_id || in_array($type->discipline_id, $clubDisciplineIds)) 
                                   && $type->is_active;
                        });
                        
                        $result['filtered_course_types'] = $filteredCourseTypes->map(function($ct) {
                            return [
                                'id' => $ct->id,
                                'name' => $ct->name,
                                'discipline_id' => $ct->discipline_id,
                                'duration_minutes' => $ct->duration_minutes,
                                'matches_club' => true
                            ];
                        })->values()->toArray();
                    }

                    // Récupérer les créneaux et leurs types de cours
                    $slots = ClubOpenSlot::with(['courseTypes', 'discipline'])
                        ->where('club_id', $club->id)
                        ->orderBy('day_of_week')
                        ->orderBy('start_time')
                        ->get();

                    $result['open_slots'] = $slots->map(function($slot) use ($clubDisciplineIds) {
                        $courseTypes = $slot->courseTypes;
                        
                        // Filtrer comme dans ClubOpenSlotController
                        $filteredCourseTypes = $courseTypes;
                        if (!empty($clubDisciplineIds)) {
                            $filteredCourseTypes = $courseTypes->filter(function($ct) use ($clubDisciplineIds) {
                                return !$ct->discipline_id || in_array($ct->discipline_id, $clubDisciplineIds);
                            })->values();
                        }

                        return [
                            'id' => $slot->id,
                            'day_of_week' => $slot->day_of_week,
                            'start_time' => $slot->start_time,
                            'end_time' => $slot->end_time,
                            'discipline_id' => $slot->discipline_id,
                            'discipline_name' => $slot->discipline ? $slot->discipline->name : null,
                            'course_types_count_before_filter' => $courseTypes->count(),
                            'course_types_count_after_filter' => $filteredCourseTypes->count(),
                            'course_types_before_filter' => $courseTypes->map(fn($ct) => [
                                'id' => $ct->id,
                                'name' => $ct->name,
                                'discipline_id' => $ct->discipline_id,
                                'should_be_filtered' => $ct->discipline_id && !in_array($ct->discipline_id, $clubDisciplineIds)
                            ])->toArray(),
                            'course_types_after_filter' => $filteredCourseTypes->map(fn($ct) => [
                                'id' => $ct->id,
                                'name' => $ct->name,
                                'discipline_id' => $ct->discipline_id
                            ])->toArray()
                        ];
                    })->toArray();
                }
            }

            // Résumé des problèmes détectés
            $result['issues'] = [];
            
            if ($user->role === 'club' && empty($result['club_disciplines'])) {
                $result['issues'][] = '⚠️ Aucune discipline configurée pour le club';
            }
            
            if ($user->role === 'club' && $result['club'] && !is_array($result['club']['disciplines_parsed'])) {
                $result['issues'][] = '❌ Les disciplines ne sont pas un tableau (problème de parsing)';
            }

            foreach ($result['open_slots'] as $slot) {
                if ($slot['course_types_count_before_filter'] != $slot['course_types_count_after_filter']) {
                    $filtered = $slot['course_types_count_before_filter'] - $slot['course_types_count_after_filter'];
                    $result['issues'][] = "✅ Créneau {$slot['id']}: {$filtered} type(s) filtré(s) correctement";
                }
                
                foreach ($slot['course_types_before_filter'] as $ct) {
                    if ($ct['should_be_filtered']) {
                        $result['issues'][] = "❌ Créneau {$slot['id']}: Type '{$ct['name']}' (disc:{$ct['discipline_id']}) devrait être filtré mais ne l'est pas";
                    }
                }
            }

            if (empty($result['issues'])) {
                $result['issues'][] = '✅ Aucun problème détecté';
            }

            return response()->json([
                'success' => true,
                'data' => $result,
                'summary' => [
                    'user_role' => $user->role,
                    'club_has_disciplines' => !empty($result['club_disciplines']),
                    'disciplines_count' => count($result['club_disciplines']),
                    'all_course_types_count' => count($result['all_course_types']),
                    'filtered_course_types_count' => count($result['filtered_course_types']),
                    'open_slots_count' => count($result['open_slots']),
                    'filtering_working' => $result['filtering_applied'] && !empty($result['filtered_course_types'])
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    /**
     * Tester un créneau spécifique
     */
    public function checkSlot(Request $request, string $slotId): JsonResponse
    {
        try {
            $user = Auth::user();
            $slot = ClubOpenSlot::with(['courseTypes', 'discipline'])->find($slotId);

            if (!$slot) {
                return response()->json([
                    'success' => false,
                    'error' => 'Créneau non trouvé'
                ], 404);
            }

            $result = [
                'slot' => [
                    'id' => $slot->id,
                    'day_of_week' => $slot->day_of_week,
                    'start_time' => $slot->start_time,
                    'end_time' => $slot->end_time,
                    'discipline_id' => $slot->discipline_id,
                    'club_id' => $slot->club_id
                ],
                'course_types' => $slot->courseTypes->map(function($ct) {
                    return [
                        'id' => $ct->id,
                        'name' => $ct->name,
                        'discipline_id' => $ct->discipline_id,
                        'duration_minutes' => $ct->duration_minutes,
                        'is_active' => $ct->is_active
                    ];
                })->toArray()
            ];

            if ($user->role === 'club') {
                $club = $user->getFirstClub();
                if ($club) {
                    $clubDisciplineIds = $club->disciplines ?? [];
                    
                    $result['club_disciplines'] = $clubDisciplineIds;
                    $result['filtering_analysis'] = $slot->courseTypes->map(function($ct) use ($clubDisciplineIds) {
                        $shouldKeep = !$ct->discipline_id || in_array($ct->discipline_id, $clubDisciplineIds);
                        return [
                            'id' => $ct->id,
                            'name' => $ct->name,
                            'discipline_id' => $ct->discipline_id,
                            'is_generic' => !$ct->discipline_id,
                            'matches_club' => $ct->discipline_id ? in_array($ct->discipline_id, $clubDisciplineIds) : null,
                            'should_keep' => $shouldKeep,
                            'reason' => !$ct->discipline_id ? 'Générique' : ($shouldKeep ? 'Match club' : 'Hors club')
                        ];
                    })->toArray();
                }
            }

            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

