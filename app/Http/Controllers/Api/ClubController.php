<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Club;
use App\Models\User;
use App\Models\Teacher;
use App\Models\Student;
use App\Models\Lesson;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class ClubController extends Controller
{
    /**
     * Get club dashboard data
     */
    public function dashboard()
    {
        // Pour le test, utiliser un club par défaut
        $club = \App\Models\Club::first();
        
        if (!$club) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun club trouvé dans la base de données'
            ], 404);
        }

        // Données de test pour le dashboard
        $stats = [
            'total_teachers' => 0,
            'total_students' => 0,
            'total_lessons' => 0,
            'completed_lessons' => 0,
            'total_revenue' => 0,
            'monthly_revenue' => 0,
            'occupancy_rate' => 0,
            'average_lesson_price' => 0
        ];

        // Données vides pour les listes récentes
        $recentTeachers = collect([]);
        $recentStudents = collect([]);
        $recentLessons = collect([]);

        return response()->json([
            'success' => true,
            'data' => [
                'club' => [
                    'id' => $club->id,
                    'name' => $club->name,
                    'email' => $club->email,
                    'phone' => $club->phone,
                    'address' => $club->address,
                    'city' => $club->city,
                    'postal_code' => $club->postal_code,
                    'country' => $club->country,
                    'website' => $club->website,
                    'facilities' => $club->facilities,
                    'disciplines' => $club->disciplines,
                    'max_students' => $club->max_students,
                    'subscription_price' => $club->subscription_price,
                    'is_active' => $club->is_active
                ],
                'stats' => $stats,
                'recentTeachers' => $recentTeachers,
                'recentStudents' => $recentStudents,
                'recentLessons' => $recentLessons
            ],
            'message' => 'Données du dashboard récupérées avec succès'
        ]);
    }

    /**
     * Get all teachers of the club
     */
    public function teachers()
    {
        $user = auth()->user();
        $club = $user->clubs()->first();
        
        if (!$club) {
            return response()->json(['message' => 'Aucun club associé'], 404);
        }

        $teachers = $club->users()
            ->wherePivot('role', 'teacher')
            ->orderBy('club_user.created_at', 'desc')
            ->paginate(15);

        return response()->json($teachers);
    }

    /**
     * Get all students of the club
     */
    public function students()
    {
        $user = auth()->user();
        $club = $user->clubs()->first();
        
        if (!$club) {
            return response()->json(['message' => 'Aucun club associé'], 404);
        }

        $students = $club->users()
            ->wherePivot('role', 'student')
            ->orderBy('club_user.created_at', 'desc')
            ->paginate(15);

        return response()->json($students);
    }

    /**
     * Add a teacher to the club
     */
    public function addTeacher(Request $request)
    {
        $user = auth()->user();
        $club = $user->clubs()->first();
        
        if (!$club) {
            return response()->json(['message' => 'Aucun club associé'], 404);
        }

        $validator = Validator::make($request->all(), [
            'email' => 'required|email'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Trouver l'utilisateur par email
            $teacherUser = User::where('email', $request->email)->first();
            
            if (!$teacherUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur non trouvé'
                ], 404);
            }

            // Vérifier si l'utilisateur est déjà membre du club
            if ($club->users()->where('user_id', $teacherUser->id)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cet utilisateur est déjà membre du club'
                ], 400);
            }

            // Vérifier que l'utilisateur a le rôle teacher
            if ($teacherUser->role !== 'teacher') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cet utilisateur n\'est pas un enseignant'
                ], 400);
            }

            // Associer l'enseignant au club
            $club->users()->attach($teacherUser->id, [
                'role' => 'teacher',
                'is_admin' => false,
                'joined_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Enseignant ajouté au club avec succès'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'ajout de l\'enseignant',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add a student to the club
     */
    public function addStudent(Request $request)
    {
        $user = auth()->user();
        $club = $user->clubs()->first();
        
        if (!$club) {
            return response()->json(['message' => 'Aucun club associé'], 404);
        }

        $validator = Validator::make($request->all(), [
            'email' => 'required|email'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Trouver l'utilisateur par email
            $studentUser = User::where('email', $request->email)->first();
            
            if (!$studentUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur non trouvé'
                ], 404);
            }

            // Vérifier si l'utilisateur est déjà membre du club
            if ($club->users()->where('user_id', $studentUser->id)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cet utilisateur est déjà membre du club'
                ], 400);
            }

            // Vérifier que l'utilisateur a le rôle student
            if ($studentUser->role !== 'student') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cet utilisateur n\'est pas un élève'
                ], 400);
            }

            // Associer l'étudiant au club
            $club->users()->attach($studentUser->id, [
                'role' => 'student',
                'is_admin' => false,
                'joined_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Élève ajouté au club avec succès'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'ajout de l\'élève',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update club information
     */
    public function updateClub(Request $request)
    {
        $user = auth()->user();
        $club = $user->clubs()->first();
        
        if (!$club) {
            return response()->json(['message' => 'Aucun club associé'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'email' => 'nullable|email|unique:clubs,email,' . $club->id,
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:10',
            'website' => 'nullable|url',
            'facilities' => 'nullable|array',
            'disciplines' => 'nullable|array',
            'max_students' => 'nullable|integer|min:1',
            'subscription_price' => 'nullable|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $club->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Profil du club mis à jour avec succès',
                'club' => $club
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la mise à jour',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get club profile data
     */
    public function getProfile()
    {
        // Pour le test, utiliser un club par défaut
        $club = \App\Models\Club::first();
        
        if (!$club) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun club trouvé dans la base de données'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'club' => [
                    'id' => $club->id,
                    'name' => $club->name,
                    'email' => $club->email,
                    'phone' => $club->phone,
                    'address' => $club->address,
                    'city' => $club->city,
                    'postal_code' => $club->postal_code,
                    'country' => $club->country,
                    'website' => $club->website,
                    'description' => $club->description,
                    'facilities' => $club->facilities,
                    'disciplines' => $club->disciplines,
                    'max_students' => $club->max_students,
                    'subscription_price' => $club->subscription_price,
                    'is_active' => $club->is_active
                ]
            ]
        ]);
    }

    /**
     * Get custom specialties
     */
    public function getCustomSpecialties()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'specialties' => [
                    'Équitation classique',
                    'Dressage',
                    'Saut d\'obstacles',
                    'Cross',
                    'Équitation western',
                    'Équitation de loisir',
                    'Équitation thérapeutique',
                    'Attelage',
                    'Voltige'
                ]
            ]
        ]);
    }

    /**
     * Get disciplines
     */
    public function getDisciplines()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'disciplines' => [
                    'Équitation',
                    'Dressage',
                    'Saut d\'obstacles',
                    'Cross',
                    'Équitation western',
                    'Attelage',
                    'Voltige'
                ]
            ]
        ]);
    }

    /**
     * Create a new lesson
     */
    public function createLesson(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'teacher_id' => 'required|integer',
            'student_id' => 'required|integer',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'duration' => 'required|integer|min:15|max:60',
            'type' => 'required|in:lesson,group,training,competition',
            'price' => 'required|numeric|min:0',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Pour l'instant, simuler la création du cours
            $lessonData = [
                'id' => rand(1000, 9999),
                'title' => $request->title,
                'teacher_id' => $request->teacher_id,
                'student_id' => $request->student_id,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'duration' => $request->duration,
                'type' => $request->type,
                'price' => $request->price,
                'notes' => $request->notes,
                'status' => 'scheduled',
                'created_at' => now()
            ];

            return response()->json([
                'success' => true,
                'message' => 'Cours créé avec succès',
                'data' => $lessonData
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du cours',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get test statistics for all data
     */
    public function getTestStats()
    {
        try {
            // Compter les données réelles dans la base
            $clubsCount = \App\Models\Club::count();
            $usersCount = \App\Models\User::count();
            $lessonsCount = \App\Models\Lesson::count();
            $paymentsCount = \App\Models\Payment::count();

            return response()->json([
                'success' => true,
                'data' => [
                    'clubs' => $clubsCount,
                    'users' => $usersCount,
                    'lessons' => $lessonsCount,
                    'payments' => $paymentsCount
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des statistiques',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all clubs for testing
     */
    public function getTestClubs()
    {
        try {
            $clubs = \App\Models\Club::select([
                'id', 'name', 'email', 'phone', 'address', 'city', 
                'postal_code', 'country', 'website', 'description', 
                'facilities', 'disciplines', 'is_active', 'created_at'
            ])->get();

            return response()->json([
                'success' => true,
                'data' => $clubs
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des clubs',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get detailed club information for testing
     */
    public function getTestClubDetails($id)
    {
        try {
            $club = \App\Models\Club::find($id);
            
            if (!$club) {
                return response()->json([
                    'success' => false,
                    'message' => 'Club non trouvé'
                ], 404);
            }

            // Statistiques du club
            $teachersCount = \App\Models\User::where('club_id', $id)
                ->where('role', 'teacher')
                ->count();
                
            $studentsCount = \App\Models\User::where('club_id', $id)
                ->where('role', 'student')
                ->count();
                
            $lessonsCount = \App\Models\Lesson::whereHas('teacher', function($query) use ($id) {
                $query->where('club_id', $id);
            })->count();
            
            $completedLessonsCount = \App\Models\Lesson::whereHas('teacher', function($query) use ($id) {
                $query->where('club_id', $id);
            })->where('status', 'completed')->count();
            
            $totalRevenue = \App\Models\Payment::whereHas('lesson.teacher', function($query) use ($id) {
                $query->where('club_id', $id);
            })->where('status', 'completed')->sum('amount');
            
            $monthlyRevenue = \App\Models\Payment::whereHas('lesson.teacher', function($query) use ($id) {
                $query->where('club_id', $id);
            })->where('status', 'completed')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');

            return response()->json([
                'success' => true,
                'data' => [
                    'club' => $club,
                    'stats' => [
                        'teachers' => $teachersCount,
                        'students' => $studentsCount,
                        'lessons' => $lessonsCount,
                        'completed_lessons' => $completedLessonsCount,
                        'total_revenue' => number_format($totalRevenue, 2),
                        'monthly_revenue' => number_format($monthlyRevenue, 2)
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des détails du club',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}