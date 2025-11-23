<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ClubDashboardController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/club/dashboard",
     *     summary="Get club dashboard data",
     *     tags={"Club"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Club dashboard data retrieved successfully")
     * )
     */
    public function dashboard(Request $request)
    {
        try {
            // Utiliser Auth::user() au lieu de vérifier manuellement le token
            // Le middleware 'auth:sanctum' s'occupe déjà de l'authentification
            $user = Auth::user();
            
            if (!$user) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }
            
            // Vérifier que l'utilisateur a le rôle 'club'
            if ($user->role !== 'club') {
                return response()->json(['message' => 'Access denied. Club role required.'], 403);
            }
            
            // Récupérer le club associé à cet utilisateur via la table club_user
            $clubUser = DB::table('club_user')
                ->where('user_id', $user->id)
                ->where('is_admin', true)
                ->first();
            
            if (!$clubUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun club associé à cet utilisateur'
                ], 404);
            }
            
            $club = DB::table('clubs')
                ->where('id', $clubUser->club_id)
                ->first();
            
            if (!$club) {
                return response()->json([
                    'success' => false,
                    'message' => 'Club non trouvé'
                ], 404);
            }
            
            // Récupérer les statistiques du club
            $stats = $this->getClubStats($club->id);
            
            // Récupérer les enseignants récents
            $recentTeachers = $this->getRecentTeachers($club->id);
            
            // Récupérer les étudiants récents
            $recentStudents = $this->getRecentStudents($club->id);
            
            // Récupérer les cours récents
            $recentLessons = $this->getRecentLessons($club->id);
            
            // Récupérer les élèves avec données incomplètes (pas de nom ou pas d'email)
            $incompleteStudents = $this->getIncompleteStudents($club->id);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'club' => $club,
                    'stats' => $stats,
                    'recentTeachers' => $recentTeachers,
                    'recentStudents' => $recentStudents,
                    'recentLessons' => $recentLessons,
                    'incompleteStudents' => $incompleteStudents
                ]
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Erreur dans ClubDashboardController::dashboard: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur interne du serveur'
            ], 500);
        }
    }
    
    private function getClubStats($clubId)
    {
        // Nombre total d'enseignants
        $totalTeachers = DB::table('club_teachers')
            ->where('club_id', $clubId)
            ->where('is_active', true)
            ->count();
        
        // Nombre total d'étudiants
        $totalStudents = DB::table('club_students')
            ->where('club_id', $clubId)
            ->where('is_active', true)
            ->count();
        
        // Nombre total de cours
        $totalLessons = DB::table('lessons')
            ->join('teachers', 'lessons.teacher_id', '=', 'teachers.id')
            ->join('club_teachers', 'teachers.id', '=', 'club_teachers.teacher_id')
            ->where('club_teachers.club_id', $clubId)
            ->count();
        
        // Cours terminés
        $completedLessons = DB::table('lessons')
            ->join('teachers', 'lessons.teacher_id', '=', 'teachers.id')
            ->join('club_teachers', 'teachers.id', '=', 'club_teachers.teacher_id')
            ->where('club_teachers.club_id', $clubId)
            ->where('lessons.status', 'completed')
            ->count();
        
        // Revenus totaux
        $totalRevenue = DB::table('lessons')
            ->join('teachers', 'lessons.teacher_id', '=', 'teachers.id')
            ->join('club_teachers', 'teachers.id', '=', 'club_teachers.teacher_id')
            ->where('club_teachers.club_id', $clubId)
            ->where('lessons.status', 'completed')
            ->sum('lessons.price');
        
        // Revenus du mois en cours
        $monthlyRevenue = DB::table('lessons')
            ->join('teachers', 'lessons.teacher_id', '=', 'teachers.id')
            ->join('club_teachers', 'teachers.id', '=', 'club_teachers.teacher_id')
            ->where('club_teachers.club_id', $clubId)
            ->where('lessons.status', 'completed')
            ->whereMonth('lessons.start_time', now()->month)
            ->whereYear('lessons.start_time', now()->year)
            ->sum('lessons.price');
        
        // Prix moyen des cours
        $averageLessonPrice = $totalLessons > 0 
            ? DB::table('lessons')
                ->join('teachers', 'lessons.teacher_id', '=', 'teachers.id')
                ->join('club_teachers', 'teachers.id', '=', 'club_teachers.teacher_id')
                ->where('club_teachers.club_id', $clubId)
                ->avg('lessons.price')
            : 0;
        
        // Taux d'occupation (pourcentage de cours confirmés/complétés)
        $occupancyRate = $totalLessons > 0 
            ? round(($completedLessons / $totalLessons) * 100, 1)
            : 0;
        
        return [
            'total_teachers' => $totalTeachers,
            'total_students' => $totalStudents,
            'total_lessons' => $totalLessons,
            'completed_lessons' => $completedLessons,
            'total_revenue' => round($totalRevenue ?? 0, 2),
            'monthly_revenue' => round($monthlyRevenue ?? 0, 2),
            'average_lesson_price' => round($averageLessonPrice ?? 0, 2),
            'occupancy_rate' => $occupancyRate
        ];
    }
    
    private function getRecentTeachers($clubId)
    {
        return DB::table('club_teachers')
            ->join('teachers', 'club_teachers.teacher_id', '=', 'teachers.id')
            ->join('users', 'teachers.user_id', '=', 'users.id')
            ->where('club_teachers.club_id', $clubId)
            ->where('club_teachers.is_active', true)
            ->select(
                'users.id',
                'users.name',
                'users.email',
                'teachers.hourly_rate',
                'teachers.experience_years',
                'teachers.specialties',
                'club_teachers.joined_at'
            )
            ->orderBy('club_teachers.joined_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($teacher) {
                return [
                    'id' => $teacher->id,
                    'name' => $teacher->name,
                    'email' => $teacher->email,
                    'hourly_rate' => $teacher->hourly_rate,
                    'experience_years' => $teacher->experience_years,
                    'specialties' => json_decode($teacher->specialties, true),
                    'joined_at' => $teacher->joined_at
                ];
            });
    }
    
    private function getRecentStudents($clubId)
    {
        return DB::table('club_students')
            ->join('students', 'club_students.student_id', '=', 'students.id')
            ->leftJoin('users', 'students.user_id', '=', 'users.id')
            ->where('club_students.club_id', $clubId)
            ->where('club_students.is_active', true)
            ->select(
                'students.id',
                'students.first_name as student_first_name',
                'students.last_name as student_last_name',
                'users.id as user_id',
                'users.name',
                'users.first_name',
                'users.last_name',
                'users.email',
                'students.level',
                'students.total_lessons',
                'students.total_spent',
                'club_students.joined_at'
            )
            ->orderBy('club_students.joined_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($student) {
                // Construire le nom : utiliser users.name si disponible, sinon construire depuis users.first_name/last_name, sinon depuis students.first_name/last_name
                $name = null;
                if ($student->name) {
                    $name = $student->name;
                } elseif ($student->first_name || $student->last_name) {
                    $name = trim(($student->first_name ?? '') . ' ' . ($student->last_name ?? ''));
                    if (empty($name)) {
                        $name = null;
                    }
                } elseif ($student->student_first_name || $student->student_last_name) {
                    $name = trim(($student->student_first_name ?? '') . ' ' . ($student->student_last_name ?? ''));
                    if (empty($name)) {
                        $name = null;
                    }
                }
                
                return [
                    'id' => $student->id,
                    'user_id' => $student->user_id,
                    'name' => $name ?? 'Élève sans nom',
                    'first_name' => $student->first_name ?? $student->student_first_name,
                    'last_name' => $student->last_name ?? $student->student_last_name,
                    'email' => $student->email,
                    'level' => $student->level,
                    'total_lessons' => $student->total_lessons,
                    'total_spent' => $student->total_spent,
                    'joined_at' => $student->joined_at
                ];
            });
    }
    
    private function getRecentLessons($clubId)
    {
        return DB::table('lessons')
            ->join('teachers', 'lessons.teacher_id', '=', 'teachers.id')
            ->join('club_teachers', 'teachers.id', '=', 'club_teachers.teacher_id')
            ->join('students', 'lessons.student_id', '=', 'students.id')
            ->join('users as student_users', 'students.user_id', '=', 'student_users.id')
            ->join('users as teacher_users', 'teachers.user_id', '=', 'teacher_users.id')
            ->where('club_teachers.club_id', $clubId)
            ->select(
                'lessons.id',
                'lessons.start_time',
                'lessons.end_time',
                'lessons.status',
                'lessons.price',
                'lessons.notes as title',
                'student_users.name as student_name',
                'teacher_users.name as teacher_name'
            )
            ->orderBy('lessons.start_time', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($lesson) {
                return [
                    'id' => $lesson->id,
                    'start_time' => $lesson->start_time,
                    'end_time' => $lesson->end_time,
                    'status' => $lesson->status,
                    'price' => $lesson->price,
                    'title' => $lesson->title ?: 'Cours avec ' . $lesson->student_name,
                    'student_name' => $lesson->student_name,
                    'teacher_name' => $lesson->teacher_name
                ];
            });
    }
    
    private function getIncompleteStudents($clubId)
    {
        // Récupérer les élèves sans user_id qui n'ont pas de nom dans students
        $studentsWithoutUser = DB::table('club_students')
            ->join('students', 'club_students.student_id', '=', 'students.id')
            ->where('club_students.club_id', $clubId)
            ->where('club_students.is_active', true)
            ->whereNull('students.user_id')
            ->where(function($query) {
                $query->whereNull('students.first_name')
                      ->orWhereNull('students.last_name')
                      ->orWhere('students.first_name', '')
                      ->orWhere('students.last_name', '');
            })
            ->select(
                'students.id',
                'students.club_id',
                'students.date_of_birth',
                'students.first_name',
                'students.last_name',
                DB::raw('NULL as name'),
                DB::raw('NULL as email'),
                DB::raw('NULL as phone'),
                DB::raw('"no_name" as missing_field')
            )
            ->get();
        
        // Récupérer les élèves avec user_id mais sans nom
        // Un élève est considéré sans nom UNIQUEMENT s'il n'a de nom ni dans students ni dans users
        $studentsWithoutName = DB::table('club_students')
            ->join('students', 'club_students.student_id', '=', 'students.id')
            ->leftJoin('users', 'students.user_id', '=', 'users.id')
            ->where('club_students.club_id', $clubId)
            ->where('club_students.is_active', true)
            ->whereNotNull('students.user_id')
            ->where(function($query) {
                // Pas de nom dans students
                $query->where(function($q) {
                    $q->where(function($nameQ) {
                        $nameQ->whereNull('students.first_name')
                              ->orWhere('students.first_name', '');
                    })
                    ->where(function($nameQ) {
                        $nameQ->whereNull('students.last_name')
                              ->orWhere('students.last_name', '');
                    });
                })
                // ET pas de nom dans users non plus
                ->where(function($q) {
                    $q->where(function($subQ) {
                        // Pas de users.name ET pas de users.first_name/last_name
                        $subQ->where(function($nameQ) {
                            $nameQ->whereNull('users.name')
                                  ->orWhere('users.name', '');
                        })
                        ->where(function($nameQ) {
                            $nameQ->where(function($fn) {
                                $fn->whereNull('users.first_name')
                                   ->orWhere('users.first_name', '');
                            })
                            ->where(function($ln) {
                                $ln->whereNull('users.last_name')
                                   ->orWhere('users.last_name', '');
                            });
                        });
                    });
                });
            })
            ->select(
                'students.id',
                'students.club_id',
                'students.date_of_birth',
                'students.first_name as student_first_name',
                'students.last_name as student_last_name',
                'users.name',
                'users.first_name',
                'users.last_name',
                'users.email',
                'users.phone',
                DB::raw('"no_name" as missing_field')
            )
            ->get()
            ->map(function($student) {
                // Utiliser les noms de students si disponibles, sinon ceux de users
                return (object) [
                    'id' => $student->id,
                    'club_id' => $student->club_id,
                    'date_of_birth' => $student->date_of_birth,
                    'name' => $student->name,
                    'first_name' => $student->student_first_name ?: $student->first_name,
                    'last_name' => $student->student_last_name ?: $student->last_name,
                    'email' => $student->email,
                    'phone' => $student->phone,
                    'missing_field' => 'no_name'
                ];
            });
        
        // Récupérer les élèves avec user_id mais sans email
        // Un élève sans email est considéré comme incomplet même s'il a un nom
        $studentsWithoutEmail = DB::table('club_students')
            ->join('students', 'club_students.student_id', '=', 'students.id')
            ->join('users', 'students.user_id', '=', 'users.id')
            ->where('club_students.club_id', $clubId)
            ->where('club_students.is_active', true)
            ->where(function($query) {
                $query->whereNull('users.email')
                      ->orWhere('users.email', '');
            })
            ->select(
                'students.id',
                'students.club_id',
                'students.date_of_birth',
                'students.first_name as student_first_name',
                'students.last_name as student_last_name',
                'users.name',
                'users.first_name',
                'users.last_name',
                'users.email',
                'users.phone',
                DB::raw('"no_email" as missing_field')
            )
            ->get()
            ->map(function($student) {
                // Utiliser les noms de students si disponibles, sinon ceux de users
                return (object) [
                    'id' => $student->id,
                    'club_id' => $student->club_id,
                    'date_of_birth' => $student->date_of_birth,
                    'name' => $student->name,
                    'first_name' => $student->student_first_name ?: $student->first_name,
                    'last_name' => $student->student_last_name ?: $student->last_name,
                    'email' => $student->email,
                    'phone' => $student->phone,
                    'missing_field' => 'no_email'
                ];
            });
        
        // Fusionner les résultats et filtrer les doublons
        $allIncomplete = $studentsWithoutUser
            ->concat($studentsWithoutName)
            ->concat($studentsWithoutEmail)
            ->unique('id')
            ->map(function ($student) {
                $missingFields = $this->getMissingFields($student);
                
                // Un élève est incomplet s'il manque une des quatre infos : prénom, nom, email ou téléphone
                // Garder tous les élèves qui ont au moins un champ manquant
                if (empty($missingFields)) {
                    return null; // Tous les champs sont remplis
                }
                
                return [
                    'student_id' => $student->id,
                    'club_id' => $student->club_id,
                    'date_of_birth' => $student->date_of_birth,
                    'name' => $student->name,
                    'first_name' => $student->first_name,
                    'last_name' => $student->last_name,
                    'email' => $student->email,
                    'phone' => $student->phone ?? null,
                    'missing_fields' => $missingFields
                ];
            })
            ->filter() // Retirer les null
            ->values();
        
        return $allIncomplete;
    }
    
    private function getMissingFields($student)
    {
        $missing = [];
        
        // Vérifier l'email (requis pour la connexion)
        if (!$student->email || empty($student->email)) {
            $missing[] = 'email';
        }
        
        // Vérifier le nom : il faut au moins un first_name ou last_name non vide (requis pour la connexion)
        $hasFirstName = !empty($student->first_name);
        $hasLastName = !empty($student->last_name);
        
        // Si on a un name complet dans users, on le considère comme OK
        if (!empty($student->name)) {
            // Le nom existe dans users, pas besoin de vérifier first_name/last_name
        } elseif (!$hasFirstName && !$hasLastName) {
            // Ni first_name ni last_name n'est rempli
            $missing[] = 'name';
        } elseif (!$hasFirstName) {
            // Pas de prénom
            $missing[] = 'first_name';
        } elseif (!$hasLastName) {
            // Pas de nom
            $missing[] = 'last_name';
        }
        
        // Vérifier le téléphone (optionnel mais mentionné si manquant)
        if (!$student->phone || empty($student->phone)) {
            $missing[] = 'phone';
        }
        
        return $missing;
    }
}
