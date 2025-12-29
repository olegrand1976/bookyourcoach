<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Teacher;
use App\Models\Lesson;
use Illuminate\Support\Facades\Log;

class TeacherController extends Controller
{
    /**
     * Dashboard complet de l'enseignant avec statistiques détaillées
     */
    public function dashboard(Request $request)
    {
        try {
            $user = $request->user();
            $teacher = $user->teacher;

            if (!$teacher) {
                return response()->json([
                    'success' => false,
                    'message' => 'Profil enseignant introuvable'
                ], 404);
            }

            $now = now();
            $startOfWeek = $now->copy()->startOfWeek();
            $endOfWeek = $now->copy()->endOfWeek();
            $startOfMonth = $now->copy()->startOfMonth();
            $endOfMonth = $now->copy()->endOfMonth();
            
            // Mois précédent
            $startOfPreviousMonth = $now->copy()->subMonth()->startOfMonth();
            $endOfPreviousMonth = $now->copy()->subMonth()->endOfMonth();

            // Optimiser les statistiques avec des requêtes DB raw plus rapides
            // Utiliser DB::table() au lieu d'Eloquent pour de meilleures performances
            $teacherId = $teacher->id;
            $todayDate = $now->toDateString();
            $startOfWeekStr = $startOfWeek->toDateTimeString();
            $endOfWeekStr = $endOfWeek->toDateTimeString();
            $startOfMonthStr = $startOfMonth->toDateTimeString();
            $endOfMonthStr = $endOfMonth->toDateTimeString();
            $startOfPreviousMonthStr = $startOfPreviousMonth->toDateTimeString();
            $endOfPreviousMonthStr = $endOfPreviousMonth->toDateTimeString();
            
            // Utiliser une seule requête avec des sous-requêtes pour toutes les statistiques
            // Cela réduit le nombre de round-trips vers la DB (de 7 requêtes à 1)
            $stats = \Illuminate\Support\Facades\DB::table('lessons')
                ->where('teacher_id', $teacherId)
                ->selectRaw('
                    COUNT(CASE WHEN DATE(start_time) = ? AND status IN (\'confirmed\', \'completed\') THEN 1 END) as today_lessons,
                    COUNT(CASE WHEN status IN (\'confirmed\', \'completed\') THEN 1 END) as total_lessons,
                    COUNT(DISTINCT CASE WHEN status IN (\'confirmed\', \'completed\') AND student_id IS NOT NULL THEN student_id END) as active_students,
                    COUNT(CASE WHEN start_time BETWEEN ? AND ? AND status IN (\'confirmed\', \'completed\') THEN 1 END) as weekly_lessons,
                    COALESCE(SUM(CASE WHEN start_time BETWEEN ? AND ? AND status = \'completed\' THEN price END), 0) as weekly_earnings,
                    COALESCE(SUM(CASE WHEN start_time BETWEEN ? AND ? AND status = \'completed\' THEN price END), 0) as monthly_earnings,
                    -- Revenus payés/non payés mois précédent
                    COALESCE(SUM(CASE WHEN start_time BETWEEN ? AND ? AND status = \'completed\' AND payment_status = \'paid\' THEN COALESCE(montant, price) END), 0) as previous_month_paid,
                    COALESCE(SUM(CASE WHEN start_time BETWEEN ? AND ? AND status = \'completed\' AND payment_status != \'paid\' THEN COALESCE(montant, price) END), 0) as previous_month_unpaid,
                    -- Revenus payés/non payés mois en cours
                    COALESCE(SUM(CASE WHEN start_time BETWEEN ? AND ? AND status = \'completed\' AND payment_status = \'paid\' THEN COALESCE(montant, price) END), 0) as current_month_paid,
                    COALESCE(SUM(CASE WHEN start_time BETWEEN ? AND ? AND status = \'completed\' AND payment_status != \'paid\' THEN COALESCE(montant, price) END), 0) as current_month_unpaid
                ', [
                    $todayDate,
                    $startOfWeekStr, $endOfWeekStr,
                    $startOfWeekStr, $endOfWeekStr,
                    $startOfMonthStr, $endOfMonthStr,
                    $startOfPreviousMonthStr, $endOfPreviousMonthStr,
                    $startOfPreviousMonthStr, $endOfPreviousMonthStr,
                    $startOfMonthStr, $endOfMonthStr,
                    $startOfMonthStr, $endOfMonthStr
                ])
                ->first();
            
            // Calculer les heures hebdomadaires séparément avec Carbon pour compatibilité SQLite/MySQL
            $weeklyHours = Lesson::where('teacher_id', $teacherId)
                ->whereBetween('start_time', [$startOfWeek, $endOfWeek])
                ->where('status', 'completed')
                ->whereNotNull('end_time')
                ->get()
                ->sum(function($lesson) {
                    return $lesson->start_time->diffInMinutes($lesson->end_time) / 60.0;
                });
            
            $todayLessons = $stats->today_lessons ?? 0;
            $totalLessons = $stats->total_lessons ?? 0;
            $activeStudents = $stats->active_students ?? 0;
            $weeklyLessons = $stats->weekly_lessons ?? 0;
            // S'assurer que weekly_earnings est toujours un float même si la valeur est entière
            $weeklyEarnings = (float) round($stats->weekly_earnings ?? 0, 2);
            $monthlyEarnings = (float) round($stats->monthly_earnings ?? 0, 2);
            $weeklyHours = round($weeklyHours, 1);
            
            // Revenus payés/non payés
            $previousMonthPaid = round($stats->previous_month_paid ?? 0, 2);
            $previousMonthUnpaid = round($stats->previous_month_unpaid ?? 0, 2);
            $currentMonthPaid = round($stats->current_month_paid ?? 0, 2);
            $currentMonthUnpaid = round($stats->current_month_unpaid ?? 0, 2);

            // Gérer le filtre de période (par défaut: 7 jours à venir)
            $period = $request->get('period', '7days'); // 7days, 15days, previous_month, current_month, next_month
            $dateFrom = null;
            $dateTo = null;
            
            switch ($period) {
                case '7days':
                    $dateFrom = $now->copy()->startOfDay();
                    $dateTo = $now->copy()->addDays(7)->endOfDay();
                    break;
                case '15days':
                    $dateFrom = $now->copy()->startOfDay();
                    $dateTo = $now->copy()->addDays(15)->endOfDay();
                    break;
                case 'previous_month':
                    $dateFrom = $now->copy()->subMonth()->startOfMonth()->startOfDay();
                    $dateTo = $now->copy()->subMonth()->endOfMonth()->endOfDay();
                    break;
                case 'current_month':
                    $dateFrom = $now->copy()->startOfMonth()->startOfDay();
                    $dateTo = $now->copy()->endOfMonth()->endOfDay();
                    break;
                case 'next_month':
                    $dateFrom = $now->copy()->addMonth()->startOfMonth()->startOfDay();
                    $dateTo = $now->copy()->addMonth()->endOfMonth()->endOfDay();
                    break;
                default:
                    $dateFrom = $now->copy()->startOfDay();
                    $dateTo = $now->copy()->addDays(7)->endOfDay();
            }

            // Prochains cours selon la période sélectionnée
            // Utiliser une requête DB raw avec JOINs pour de meilleures performances (une seule requête au lieu de 7)
            $upcomingLessonsRaw = \Illuminate\Support\Facades\DB::table('lessons')
                ->leftJoin('students', 'lessons.student_id', '=', 'students.id')
                ->leftJoin('users as student_users', 'students.user_id', '=', 'student_users.id')
                ->leftJoin('course_types', 'lessons.course_type_id', '=', 'course_types.id')
                ->leftJoin('locations', 'lessons.location_id', '=', 'locations.id')
                ->leftJoin('clubs', 'lessons.club_id', '=', 'clubs.id')
                ->where('lessons.teacher_id', $teacher->id)
                ->whereBetween('lessons.start_time', [$dateFrom, $dateTo])
                ->whereIn('lessons.status', ['confirmed', 'pending', 'completed', 'cancelled'])
                ->select(
                    'lessons.id',
                    'lessons.teacher_id',
                    'lessons.student_id',
                    'lessons.course_type_id',
                    'lessons.location_id',
                    'lessons.club_id',
                    'lessons.start_time',
                    'lessons.end_time',
                    'lessons.status',
                    'lessons.price',
                    'lessons.notes',
                    'student_users.name as student_name',
                    'course_types.name as course_type_name',
                    'locations.name as location_name',
                    'clubs.name as club_name'
                )
                ->orderBy('lessons.start_time', 'asc')
                ->limit(100)
                ->get()
                ->map(function ($lesson) {
                    // Charger les élèves de la relation many-to-many
                    $lessonStudents = \Illuminate\Support\Facades\DB::table('lesson_student')
                        ->join('students', 'lesson_student.student_id', '=', 'students.id')
                        ->join('users', 'students.user_id', '=', 'users.id')
                        ->where('lesson_student.lesson_id', $lesson->id)
                        ->select('students.id', 'users.name', 'users.email', 'students.date_of_birth')
                        ->get()
                        ->map(function ($s) {
                            // Calculer l'âge à partir de date_of_birth
                            $age = null;
                            if ($s->date_of_birth) {
                                $age = \Carbon\Carbon::parse($s->date_of_birth)->age;
                            }
                            
                            return [
                                'id' => $s->id,
                                'user' => [
                                    'id' => null,
                                    'name' => $s->name ?? 'Sans nom',
                                    'email' => $s->email ?? ''
                                ],
                                'age' => $age
                            ];
                        })
                        ->values() // Réindexer le tableau pour s'assurer que c'est un tableau JSON valide
                        ->toArray();
                    
                    // Construire l'objet student si student_id existe et student_name n'est pas vide
                    $studentObj = null;
                    if ($lesson->student_id && $lesson->student_name) {
                        $studentObj = [
                            'id' => $lesson->student_id,
                            'user' => [
                                'id' => null,
                                'name' => $lesson->student_name
                            ]
                        ];
                    }
                    
                    // Si aucun élève dans la relation many-to-many mais qu'on a un student_id, l'ajouter aussi
                    if (empty($lessonStudents) && $lesson->student_id && $lesson->student_name) {
                        $lessonStudents[] = [
                            'id' => $lesson->student_id,
                            'user' => [
                                'id' => null,
                                'name' => $lesson->student_name
                            ],
                            'age' => null
                        ];
                    }
                    
                    $result = [
                        'id' => $lesson->id,
                        'teacher_id' => $lesson->teacher_id,
                        'student_id' => $lesson->student_id,
                        'course_type_id' => $lesson->course_type_id,
                        'location_id' => $lesson->location_id,
                        'club_id' => $lesson->club_id,
                        'start_time' => $lesson->start_time,
                        'end_time' => $lesson->end_time,
                        'status' => $lesson->status,
                        'price' => $lesson->price,
                        'notes' => $lesson->notes,
                        'student' => $studentObj,
                        'students' => $lessonStudents, // Tableau d'élèves de la relation many-to-many
                        'course_type' => $lesson->course_type_id ? [
                            'id' => $lesson->course_type_id,
                            'name' => $lesson->course_type_name
                        ] : null,
                        'location' => $lesson->location_id ? [
                            'id' => $lesson->location_id,
                            'name' => $lesson->location_name
                        ] : null,
                        'club' => $lesson->club_id ? [
                            'id' => $lesson->club_id,
                            'name' => $lesson->club_name
                        ] : null
                    ];
                    
                    // Log pour déboguer
                    Log::debug('Lesson data structure', [
                        'lesson_id' => $lesson->id,
                        'student_id' => $lesson->student_id,
                        'student_name' => $lesson->student_name,
                        'students_count' => count($lessonStudents),
                        'students_data' => $lessonStudents,
                        'student_obj' => $studentObj
                    ]);
                    
                    return $result;
                });
            
            // Convertir en collection puis en tableau pour une meilleure sérialisation
            $upcomingLessons = collect($upcomingLessonsRaw);

            // Cours récents uniquement si la période inclut le passé
            // Utiliser la même approche optimisée avec JOINs
            $recentLessons = collect();
            if (in_array($period, ['previous_month', 'current_month'])) {
                $recentLessonsRaw = \Illuminate\Support\Facades\DB::table('lessons')
                    ->leftJoin('students', 'lessons.student_id', '=', 'students.id')
                    ->leftJoin('users as student_users', 'students.user_id', '=', 'student_users.id')
                    ->leftJoin('course_types', 'lessons.course_type_id', '=', 'course_types.id')
                    ->leftJoin('locations', 'lessons.location_id', '=', 'locations.id')
                    ->leftJoin('clubs', 'lessons.club_id', '=', 'clubs.id')
                    ->where('lessons.teacher_id', $teacher->id)
                    ->whereBetween('lessons.start_time', [$dateFrom, $dateTo])
                    ->whereIn('lessons.status', ['completed', 'cancelled'])
                    ->select(
                        'lessons.id',
                        'lessons.teacher_id',
                        'lessons.student_id',
                        'lessons.course_type_id',
                        'lessons.location_id',
                        'lessons.club_id',
                        'lessons.start_time',
                        'lessons.end_time',
                        'lessons.status',
                        'lessons.price',
                        'lessons.notes',
                        'student_users.name as student_name',
                        'course_types.name as course_type_name',
                        'locations.name as location_name',
                        'clubs.name as club_name'
                    )
                    ->orderBy('lessons.start_time', 'desc')
                    ->limit(20)
                    ->get()
                    ->map(function ($lesson) {
                        // Charger les élèves de la relation many-to-many
                        $lessonStudents = \Illuminate\Support\Facades\DB::table('lesson_student')
                            ->join('students', 'lesson_student.student_id', '=', 'students.id')
                            ->join('users', 'students.user_id', '=', 'users.id')
                            ->where('lesson_student.lesson_id', $lesson->id)
                            ->select('students.id', 'users.name', 'users.email', 'students.date_of_birth')
                            ->get()
                            ->map(function ($s) {
                                // Calculer l'âge à partir de date_of_birth
                                $age = null;
                                if ($s->date_of_birth) {
                                    $age = \Carbon\Carbon::parse($s->date_of_birth)->age;
                                }
                                
                                return [
                                    'id' => $s->id,
                                    'user' => [
                                        'id' => null,
                                        'name' => $s->name ?? 'Sans nom',
                                        'email' => $s->email ?? ''
                                    ],
                                    'age' => $age
                                ];
                            })
                            ->values() // Réindexer le tableau pour s'assurer que c'est un tableau JSON valide
                            ->toArray();
                        
                        // Construire l'objet student si student_id existe et student_name n'est pas vide
                        $studentObj = null;
                        if ($lesson->student_id && $lesson->student_name) {
                            $studentObj = [
                                'id' => $lesson->student_id,
                                'user' => [
                                    'id' => null,
                                    'name' => $lesson->student_name
                                ]
                            ];
                        }
                        
                        // Si aucun élève dans la relation many-to-many mais qu'on a un student_id, l'ajouter aussi
                        if (empty($lessonStudents) && $lesson->student_id && $lesson->student_name) {
                            $lessonStudents[] = [
                                'id' => $lesson->student_id,
                                'user' => [
                                    'id' => null,
                                    'name' => $lesson->student_name
                                ],
                                'age' => null
                            ];
                        }
                        
                        return [
                            'id' => $lesson->id,
                            'teacher_id' => $lesson->teacher_id,
                            'student_id' => $lesson->student_id,
                            'course_type_id' => $lesson->course_type_id,
                            'location_id' => $lesson->location_id,
                            'club_id' => $lesson->club_id,
                            'start_time' => $lesson->start_time,
                            'end_time' => $lesson->end_time,
                            'status' => $lesson->status,
                            'price' => $lesson->price,
                            'notes' => $lesson->notes,
                            'student' => $studentObj,
                            'students' => $lessonStudents, // Tableau d'élèves de la relation many-to-many
                            'course_type' => $lesson->course_type_id ? [
                                'id' => $lesson->course_type_id,
                                'name' => $lesson->course_type_name
                            ] : null,
                            'location' => $lesson->location_id ? [
                                'id' => $lesson->location_id,
                                'name' => $lesson->location_name
                            ] : null,
                            'club' => $lesson->club_id ? [
                                'id' => $lesson->club_id,
                                'name' => $lesson->club_name
                            ] : null
                        ];
                    });
                
                // Convertir en collection puis en tableau pour une meilleure sérialisation
                $recentLessons = collect($recentLessonsRaw);
            }

            // Clubs de l'enseignant avec seulement les colonnes nécessaires pour optimiser
            try {
                $clubs = $teacher->clubs()->select('clubs.id', 'clubs.name', 'clubs.email', 'clubs.phone', 'clubs.address', 'clubs.postal_code', 'clubs.city', 'clubs.country', 'clubs.legal_representative_name', 'clubs.legal_representative_role')->get();
            } catch (\Exception $e) {
                Log::warning('Erreur lors de la récupération des clubs de l\'enseignant: ' . $e->getMessage());
                $clubs = collect([]);
            }

            // Demandes de remplacement en attente
            $pendingReplacements = \App\Models\LessonReplacement::where(function($query) use ($teacher) {
                $query->where('replacement_teacher_id', $teacher->id)
                      ->orWhere('original_teacher_id', $teacher->id);
            })
            ->where('status', 'pending')
            ->count();

            // Convertir les collections en tableaux pour une sérialisation JSON correcte
            $upcomingLessonsArray = $upcomingLessons->map(function($lesson) {
                return is_array($lesson) ? $lesson : (array) $lesson;
            })->values()->toArray();
            
            $recentLessonsArray = $recentLessons->map(function($lesson) {
                return is_array($lesson) ? $lesson : (array) $lesson;
            })->values()->toArray();
            
            // Log pour vérifier la structure des données
            if (count($upcomingLessonsArray) > 0) {
                Log::info('📊 [Dashboard] First lesson structure', [
                    'lesson_id' => $upcomingLessonsArray[0]['id'] ?? 'N/A',
                    'has_student' => isset($upcomingLessonsArray[0]['student']),
                    'has_students' => isset($upcomingLessonsArray[0]['students']),
                    'students_count' => count($upcomingLessonsArray[0]['students'] ?? []),
                    'students_data' => $upcomingLessonsArray[0]['students'] ?? [],
                    'student_obj' => $upcomingLessonsArray[0]['student'] ?? null
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'stats' => [
                        'today_lessons' => $todayLessons,
                        'total_lessons' => $totalLessons,
                        'active_students' => $activeStudents,
                        'weekly_lessons' => $weeklyLessons,
                        'week_earnings' => $weeklyEarnings > 0 && $weeklyEarnings == (int)$weeklyEarnings ? (float)($weeklyEarnings . '.0') : (float)$weeklyEarnings,
                        'week_hours' => round($weeklyHours, 1),
                        'monthly_earnings' => round($monthlyEarnings, 2),
                        'pending_replacements' => $pendingReplacements,
                        // Revenus détaillés par mois
                        'revenues' => [
                            'previous_month' => [
                                'paid' => $previousMonthPaid,
                                'unpaid' => $previousMonthUnpaid,
                                'total' => round($previousMonthPaid + $previousMonthUnpaid, 2)
                            ],
                            'current_month' => [
                                'paid' => $currentMonthPaid,
                                'unpaid' => $currentMonthUnpaid,
                                'total' => round($currentMonthPaid + $currentMonthUnpaid, 2)
                            ]
                        ]
                    ],
                    'upcoming_lessons' => $upcomingLessonsArray,
                    'recent_lessons' => $recentLessonsArray,
                    'clubs' => $clubs->toArray(),
                    'teacher' => $teacher->load('user')
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur dashboard enseignant: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement du dashboard',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur interne'
            ], 500);
        }
    }

    /**
     * Statistiques rapides pour le dashboard simplifié
     */
    public function dashboardSimple(Request $request)
    {
        try {
            $user = $request->user();
            $teacher = $user->teacher;

            if (!$teacher) {
                return response()->json([
                    'success' => false,
                    'message' => 'Profil enseignant introuvable'
                ], 404);
            }

            $now = now();
            $startOfWeek = $now->copy()->startOfWeek();
            $endOfWeek = $now->copy()->endOfWeek();

            // Statistiques rapides
            $todayLessons = Lesson::where('teacher_id', $teacher->id)
                ->whereDate('start_time', $now->toDateString())
                ->whereIn('status', ['confirmed', 'completed'])
                ->count();

            $activeStudents = Lesson::where('teacher_id', $teacher->id)
                ->whereIn('status', ['confirmed', 'completed'])
                ->whereNotNull('student_id')
                ->distinct()
                ->count('student_id');

            $weekEarnings = Lesson::where('teacher_id', $teacher->id)
                ->whereBetween('start_time', [$startOfWeek, $endOfWeek])
                ->where('status', 'completed')
                ->sum('price');

            // S'assurer que week_earnings est toujours un float même si la valeur est entière
            // Utiliser number_format puis (float) pour garantir le type float
            $weekEarningsFloat = (float) number_format(round($weekEarnings ?? 0, 2), 2, '.', '');

            return response()->json([
                'success' => true,
                'stats' => [
                    'today_lessons' => $todayLessons,
                    'active_students' => $activeStudents,
                    'week_earnings' => $weekEarningsFloat,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur dashboard simple enseignant: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des statistiques',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Liste de tous les enseignants (pour sélection de remplaçants)
     * Retourne les enseignants du même club que l'utilisateur connecté
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            $currentTeacher = $user->teacher;

            if (!$currentTeacher) {
                return response()->json([
                    'success' => false,
                    'message' => 'Profil enseignant introuvable'
                ], 404);
            }

            // Récupérer les clubs où l'enseignant actuel travaille
            $clubIds = $currentTeacher->clubs()->pluck('clubs.id')->toArray();
            
            Log::info('🔍 [TeacherController] Clubs de l\'enseignant:', [
                'teacher_id' => $currentTeacher->id,
                'teacher_name' => $user->name,
                'club_ids' => $clubIds
            ]);

            // Récupérer tous les enseignants actifs des mêmes clubs, sauf l'utilisateur actuel
            $teachers = Teacher::with('user')
                ->where('id', '!=', $currentTeacher->id)
                ->whereHas('user', function($query) {
                    $query->where('role', 'teacher');
                })
                ->whereHas('clubs', function($query) use ($clubIds) {
                    $query->whereIn('clubs.id', $clubIds);
                })
                ->get();

            Log::info('✅ [TeacherController] Enseignants trouvés:', [
                'count' => $teachers->count(),
                'teachers' => $teachers->pluck('user.name')->toArray()
            ]);

            return response()->json([
                'success' => true,
                'data' => $teachers
            ]);

        } catch (\Exception $e) {
            Log::error('❌ [TeacherController] Erreur lors de la récupération des enseignants: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des enseignants',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Liste des élèves à qui l'enseignant donne cours
     */
    public function getStudents(Request $request)
    {
        try {
            $user = $request->user();
            $teacher = $user->teacher;

            if (!$teacher) {
                return response()->json([
                    'success' => false,
                    'message' => 'Profil enseignant introuvable'
                ], 404);
            }

            // Récupérer uniquement les élèves qui ont des cours avec cet enseignant
            // Via la relation many-to-many lesson_student
            $studentIds = \App\Models\Lesson::where('teacher_id', $teacher->id)
                ->whereHas('students')
                ->with('students')
                ->get()
                ->flatMap(function($lesson) {
                    return $lesson->students->pluck('id');
                })
                ->unique()
                ->toArray();

            // Également inclure les élèves via la relation one-to-many (student_id)
            $studentIdsFromDirect = \App\Models\Lesson::where('teacher_id', $teacher->id)
                ->whereNotNull('student_id')
                ->pluck('student_id')
                ->unique()
                ->toArray();

            // Fusionner les deux listes
            $allStudentIds = array_unique(array_merge($studentIds, $studentIdsFromDirect));

            // Si aucun élève n'a de cours avec cet enseignant, retourner une liste vide
            if (empty($allStudentIds)) {
                return response()->json([
                    'success' => true,
                    'students' => []
                ]);
            }

            // Récupérer les élèves avec leurs informations
            $students = \App\Models\Student::with(['user', 'club'])
                ->whereIn('id', $allStudentIds)
                ->get()
                ->map(function($student) {
                    return [
                        'id' => $student->id,
                        'name' => $student->user->name ?? 'Sans nom',
                        'email' => $student->user->email ?? '',
                        'level' => $student->level ?? 'débutant',
                        'age' => $student->age,
                        'club_id' => $student->club_id,
                        'club_name' => $student->club ? $student->club->name : null
                    ];
                });

            return response()->json([
                'success' => true,
                'students' => $students
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des élèves: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des élèves'
            ], 500);
        }
    }

    /**
     * Récupère les détails d'un élève spécifique
     */
    public function getStudent(Request $request, $id)
    {
        try {
            $user = $request->user();
            $teacher = $user->teacher;

            if (!$teacher) {
                return response()->json([
                    'success' => false,
                    'message' => 'Profil enseignant introuvable'
                ], 404);
            }

            // Récupérer les clubs où l'enseignant travaille
            $clubIds = $teacher->clubs()->pluck('clubs.id')->toArray();

            // Si l'enseignant n'a pas de clubs, retourner une erreur
            if (empty($clubIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun club associé à cet enseignant'
                ], 403);
            }

            // Récupérer l'élève s'il appartient à un des clubs de l'enseignant
            $student = \App\Models\Student::with('user')
                ->whereIn('club_id', $clubIds)
                ->findOrFail($id);
            
            // Charger le club séparément pour éviter les erreurs si la relation n'existe pas
            $club = null;
            if ($student->club_id) {
                try {
                    $club = \App\Models\Club::find($student->club_id);
                } catch (\Exception $e) {
                    Log::warning('Erreur chargement club élève: ' . $e->getMessage());
                }
            }

            // Calculer l'âge si date_of_birth existe
            $age = null;
            if ($student->date_of_birth) {
                try {
                    $age = \Carbon\Carbon::parse($student->date_of_birth)->age;
                } catch (\Exception $e) {
                    Log::warning('Erreur calcul âge élève: ' . $e->getMessage());
                }
            }

            return response()->json([
                'success' => true,
                'student' => [
                    'id' => $student->id,
                    'name' => $student->user->name ?? ($student->first_name && $student->last_name ? $student->first_name . ' ' . $student->last_name : 'Sans nom'),
                    'email' => $student->user->email ?? '',
                    'phone' => $student->user->phone ?? '',
                    'level' => $student->level ?? 'débutant',
                    'age' => $age,
                    'club_id' => $student->club_id,
                    'club' => $club ? [
                        'id' => $club->id,
                        'name' => $club->name
                    ] : null
                ]
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Élève non trouvé'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération de l\'élève: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de l\'élève',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Liste des clubs où l'enseignant travaille
     */
    public function getClubs(Request $request)
    {
        try {
            $user = $request->user();
            $teacher = $user->teacher;

            if (!$teacher) {
                return response()->json([
                    'success' => false,
                    'message' => 'Profil enseignant introuvable'
                ], 404);
            }

            $clubs = $teacher->clubs()->get();

            return response()->json([
                'success' => true,
                'clubs' => $clubs
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des clubs: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des clubs'
            ], 500);
        }
    }

    /**
     * Récupère les revenus de l'enseignant pour une période donnée
     */
    public function getEarnings(Request $request)
    {
        try {
            $user = $request->user();
            $teacher = $user->teacher;

            if (!$teacher) {
                return response()->json([
                    'success' => false,
                    'message' => 'Profil enseignant introuvable'
                ], 404);
            }

            $period = $request->get('period', 'week'); // week, month, year
            $now = now();
            
            $dateFrom = null;
            $dateTo = null;
            
            switch ($period) {
                case 'week':
                    $dateFrom = $now->copy()->startOfWeek();
                    $dateTo = $now->copy()->endOfWeek();
                    break;
                case 'month':
                    $dateFrom = $now->copy()->startOfMonth();
                    $dateTo = $now->copy()->endOfMonth();
                    break;
                case 'year':
                    $dateFrom = $now->copy()->startOfYear();
                    $dateTo = $now->copy()->endOfYear();
                    break;
                default:
                    $dateFrom = $now->copy()->startOfWeek();
                    $dateTo = $now->copy()->endOfWeek();
            }

            // Calculer les revenus
            $earnings = Lesson::where('teacher_id', $teacher->id)
                ->whereBetween('start_time', [$dateFrom, $dateTo])
                ->where('status', 'completed')
                ->sum('price');

            // Nombre de cours complétés
            $completedLessons = Lesson::where('teacher_id', $teacher->id)
                ->whereBetween('start_time', [$dateFrom, $dateTo])
                ->where('status', 'completed')
                ->count();

            // Heures travaillées
            $hoursWorked = Lesson::where('teacher_id', $teacher->id)
                ->whereBetween('start_time', [$dateFrom, $dateTo])
                ->where('status', 'completed')
                ->get()
                ->sum(function($lesson) {
                    return $lesson->start_time->diffInMinutes($lesson->end_time) / 60;
                });

            // Détails par cours
            $lessons = Lesson::where('teacher_id', $teacher->id)
                ->whereBetween('start_time', [$dateFrom, $dateTo])
                ->where('status', 'completed')
                ->with(['student.user', 'courseType', 'club'])
                ->orderBy('start_time', 'desc')
                ->get()
                ->map(function($lesson) {
                    return [
                        'id' => $lesson->id,
                        'start_time' => $lesson->start_time->toDateTimeString(),
                        'end_time' => $lesson->end_time->toDateTimeString(),
                        'price' => $lesson->price,
                        'duration' => $lesson->start_time->diffInMinutes($lesson->end_time) / 60,
                        'student' => $lesson->student ? [
                            'id' => $lesson->student->id,
                            'name' => $lesson->student->user->name ?? 'Sans nom'
                        ] : null,
                        'course_type' => $lesson->courseType ? [
                            'id' => $lesson->courseType->id,
                            'name' => $lesson->courseType->name
                        ] : null,
                        'club' => $lesson->club ? [
                            'id' => $lesson->club->id,
                            'name' => $lesson->club->name
                        ] : null
                    ];
                });

            return response()->json([
                'success' => true,
                'period' => $period,
                'date_from' => $dateFrom->toDateString(),
                'date_to' => $dateTo->toDateString(),
                'earnings' => round($earnings, 2),
                'completed_lessons' => $completedLessons,
                'hours_worked' => round($hoursWorked, 2),
                'lessons' => $lessons
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des revenus: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des revenus',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupère le profil de l'enseignant connecté
     */
    public function getProfile(Request $request)
    {
        try {
            $user = $request->user();
            
            Log::info('TeacherController::getProfile - User:', [
                'user_id' => $user->id,
                'email' => $user->email,
                'role' => $user->role
            ]);
            
            // Récupérer le profil enseignant
            $teacher = $user->teacher;

            if (!$teacher) {
                Log::warning('TeacherController::getProfile - Aucun profil enseignant trouvé', [
                    'user_id' => $user->id,
                    'email' => $user->email
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Profil enseignant introuvable'
                ], 404);
            }

            // Charger les relations nécessaires
            $teacher->load(['user', 'clubs']);

            return response()->json([
                'success' => true,
                'profile' => $user,
                'teacher' => $teacher
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération du profil: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du profil',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Met à jour le profil de l'enseignant connecté
     */
    public function updateProfile(Request $request)
    {
        try {
            $user = $request->user();
            
            Log::info('TeacherController::updateProfile - User:', [
                'user_id' => $user->id,
                'email' => $user->email,
                'role' => $user->role
            ]);
            
            // Récupérer le profil enseignant
            $teacher = $user->teacher;

            if (!$teacher) {
                return response()->json([
                    'success' => false,
                    'message' => 'Profil enseignant introuvable'
                ], 404);
            }

            // Normaliser les données avant validation (convertir chaînes vides en null)
            $requestData = $request->all();
            
            Log::info('🔵 [TeacherController::updateProfile] Données reçues AVANT normalisation:', [
                'request_all' => $requestData,
                'birth_date_raw' => $requestData['birth_date'] ?? 'non défini',
                'birth_date_type' => gettype($requestData['birth_date'] ?? null),
                'birth_date_is_empty' => isset($requestData['birth_date']) && $requestData['birth_date'] === '',
                'birth_date_is_null' => !isset($requestData['birth_date']) || $requestData['birth_date'] === null
            ]);
            
            // Convertir les chaînes vides en null pour birth_date, phone, bio
            if (isset($requestData['birth_date'])) {
                if ($requestData['birth_date'] === '' || trim($requestData['birth_date']) === '') {
                    Log::info('⚠️ [TeacherController::updateProfile] birth_date est une chaîne vide, conversion en null');
                    $request->merge(['birth_date' => null]);
                } else {
                    Log::info('✅ [TeacherController::updateProfile] birth_date a une valeur:', [
                        'value' => $requestData['birth_date'],
                        'trimmed' => trim($requestData['birth_date'])
                    ]);
                }
            } else {
                Log::info('ℹ️ [TeacherController::updateProfile] birth_date n\'est pas présent dans la requête');
            }
            
            if (isset($requestData['phone']) && $requestData['phone'] === '') {
                $request->merge(['phone' => null]);
            }
            if (isset($requestData['bio']) && $requestData['bio'] === '') {
                $request->merge(['bio' => null]);
            }
            
            Log::info('🔵 [TeacherController::updateProfile] Données APRÈS normalisation:', [
                'birth_date_after_merge' => $request->input('birth_date'),
                'birth_date_type_after' => gettype($request->input('birth_date'))
            ]);
            
            // Validation des données (exclure hourly_rate et experience_years qui ne doivent pas être modifiables)
            Log::info('🔵 [TeacherController::updateProfile] Avant validation:', [
                'birth_date_before_validate' => $request->input('birth_date'),
                'request_inputs' => $request->all()
            ]);
            
            $validated = $request->validate([
                'name' => 'nullable|string|max:255',
                'phone' => 'nullable|string|max:20',
                'birth_date' => 'nullable|date',
                'bio' => 'nullable|string',
                'specialties' => 'nullable|array',
                'certifications' => 'nullable|array',
                'experience_start_date' => 'nullable|date',
                // experience_years et hourly_rate ne peuvent pas être modifiés par l'enseignant
                'experience_years' => 'prohibited',
                'hourly_rate' => 'prohibited',
            ]);
            
            Log::info('✅ [TeacherController::updateProfile] Après validation:', [
                'validated' => $validated,
                'birth_date_in_validated' => $validated['birth_date'] ?? 'non défini',
                'birth_date_type' => gettype($validated['birth_date'] ?? null),
                'has_birth_date_key' => array_key_exists('birth_date', $validated)
            ]);
            
            // Mettre à jour les informations de l'utilisateur
            if (isset($validated['name'])) {
                $user->name = $validated['name'];
            }
            if (array_key_exists('phone', $validated)) {
                // Convertir chaîne vide en null
                $user->phone = $validated['phone'] ?: null;
            }
            // Récupérer la valeur originale avant modification
            $originalBirthDate = $user->birth_date;
            
            if (array_key_exists('birth_date', $validated)) {
                // S'assurer que birth_date est bien une date valide ou null
                $newBirthDate = $validated['birth_date'] ?: null;
                
                // Si c'est une chaîne de date, s'assurer qu'elle est au format Y-m-d
                if ($newBirthDate && is_string($newBirthDate)) {
                    // Extraire seulement la partie date (YYYY-MM-DD) au cas où il y aurait une heure
                    $newBirthDate = substr($newBirthDate, 0, 10);
                    
                    Log::info('🔵 [TeacherController::updateProfile] Formatage birth_date:', [
                        'original' => $validated['birth_date'],
                        'formatted' => $newBirthDate
                    ]);
                }
                
                $user->birth_date = $newBirthDate;
                
                Log::info('🔵 [TeacherController::updateProfile] Mise à jour birth_date:', [
                    'original_value' => $originalBirthDate,
                    'new_value' => $newBirthDate,
                    'new_value_type' => gettype($newBirthDate),
                    'validated_value' => $validated['birth_date'],
                    'is_null' => $newBirthDate === null,
                    'will_change' => $originalBirthDate != $newBirthDate
                ]);
            } else {
                Log::warning('⚠️ [TeacherController::updateProfile] birth_date n\'est pas dans validated, pas de mise à jour');
            }
            
            // Gérer experience_start_date
            if (array_key_exists('experience_start_date', $validated)) {
                $newExperienceStartDate = $validated['experience_start_date'] ?: null;
                
                // Si c'est une chaîne de date, s'assurer qu'elle est au format Y-m-d
                if ($newExperienceStartDate && is_string($newExperienceStartDate)) {
                    $newExperienceStartDate = substr($newExperienceStartDate, 0, 10);
                }
                
                $user->experience_start_date = $newExperienceStartDate;
            }
            
            Log::info('📝 [TeacherController::updateProfile] État AVANT save():', [
                'user_id' => $user->id,
                'name' => $user->name,
                'phone' => $user->phone,
                'birth_date' => $user->birth_date,
                'birth_date_original' => $originalBirthDate,
                'experience_start_date' => $user->experience_start_date,
                'birth_date_is_dirty' => $user->isDirty('birth_date'),
                'user_is_dirty' => $user->isDirty(),
                'validated_birth_date' => $validated['birth_date'] ?? 'non défini',
                'request_birth_date' => $request->input('birth_date')
            ]);
            
            $user->save();
            
            // Recharger depuis la DB pour vérifier la valeur sauvegardée
            $user->refresh();
            
            Log::info('✅ [TeacherController::updateProfile] État APRÈS save() et refresh():', [
                'user_id' => $user->id,
                'birth_date_saved' => $user->birth_date,
                'birth_date_type' => gettype($user->birth_date),
                'is_null' => $user->birth_date === null,
                'formatted' => $user->birth_date ? $user->birth_date->format('Y-m-d') : 'null'
            ]);

            // Mettre à jour les informations de l'enseignant
            // Note: hourly_rate et experience_years ne peuvent pas être modifiés par l'enseignant
            $teacherData = [];
            if (isset($validated['bio'])) {
                $teacherData['bio'] = $validated['bio'];
            }
            if (isset($validated['specialties'])) {
                // Le casting du modèle s'occupera de la conversion en JSON
                $teacherData['specialties'] = $validated['specialties'];
            }
            if (isset($validated['certifications'])) {
                // Le casting du modèle s'occupera de la conversion en JSON
                $teacherData['certifications'] = $validated['certifications'];
            }
            // experience_years et hourly_rate sont volontairement exclus

            if (!empty($teacherData)) {
                $teacher->update($teacherData);
            }

            // Recharger les relations
            $teacher->load(['user', 'clubs']);

            return response()->json([
                'success' => true,
                'message' => 'Profil mis à jour avec succès',
                'profile' => $user,
                'teacher' => $teacher
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la mise à jour du profil: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du profil',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
