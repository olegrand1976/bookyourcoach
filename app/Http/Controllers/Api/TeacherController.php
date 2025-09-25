<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TeacherController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        // Le middleware 'auth:sanctum' est appliqué dans les routes
    }

    /**
     * Dashboard enseignant
     */
    public function dashboard()
    {
        $user = auth()->user();
        
        // Récupérer l'ID enseignant depuis la table teachers
        $teacher = \DB::table('teachers')->where('user_id', $user->id)->first();
        
        if (!$teacher) {
            return response()->json([
                'success' => true,
                'stats' => [
                    'today_lessons' => 0,
                    'active_students' => 0,
                    'monthly_earnings' => 0,
                    'average_rating' => 0,
                ],
                'upcomingLessons' => []
            ]);
        }

        // Statistiques de base
        $todayLessons = \DB::table('lessons')
            ->where('teacher_id', $teacher->id)
            ->whereDate('start_time', today())
            ->count();

        $activeStudents = \DB::table('lessons')
            ->join('students', 'lessons.student_id', '=', 'students.id')
            ->where('lessons.teacher_id', $teacher->id)
            ->where('lessons.start_time', '>=', now()->subDays(30))
            ->distinct('students.id')
            ->count();

        $monthlyEarnings = \DB::table('lessons')
            ->where('teacher_id', $teacher->id)
            ->whereMonth('start_time', now()->month)
            ->sum('price');

        return response()->json([
            'success' => true,
            'stats' => [
                'today_lessons' => $todayLessons,
                'active_students' => $activeStudents,
                'monthly_earnings' => $monthlyEarnings,
                'average_rating' => 4.8,
            ],
            'upcomingLessons' => []
        ]);
    }

    /**
     * Calendrier enseignant
     */
    public function getCalendar(Request $request)
    {
        $user = auth()->user();
        $teacher = \DB::table('teachers')->where('user_id', $user->id)->first();
        
        if (!$teacher) {
            return response()->json([
                'success' => true,
                'events' => []
            ]);
        }

        $events = \DB::table('lessons')
            ->leftJoin('users', 'lessons.student_id', '=', 'users.id')
            ->where('lessons.teacher_id', $teacher->id)
            ->select(
                'lessons.*',
                'users.name as student_name',
                'lessons.notes as title'
            )
            ->orderBy('lessons.start_time')
            ->get();

        return response()->json([
            'success' => true,
            'events' => $events
        ]);
    }

    /**
     * Liste des élèves
     */
    public function getStudents(Request $request)
    {
        $user = auth()->user();
        $teacher = \DB::table('teachers')->where('user_id', $user->id)->first();
        
        if (!$teacher) {
            return response()->json([
                'success' => true,
                'students' => []
            ]);
        }

        $students = \DB::table('students')
            ->join('users', 'students.user_id', '=', 'users.id')
            ->join('lessons', 'students.id', '=', 'lessons.student_id')
            ->leftJoin('clubs', 'students.club_id', '=', 'clubs.id')
            ->where('lessons.teacher_id', $teacher->id)
            ->select(
                'users.id',
                'users.name',
                'users.email',
                'students.level',
                'students.club_id',
                'clubs.name as club_name',
                \DB::raw('COUNT(DISTINCT lessons.id) as lessons_count'),
                \DB::raw('MAX(lessons.start_time) as last_lesson')
            )
            ->groupBy('users.id', 'users.name', 'users.email', 'students.level', 'students.club_id', 'clubs.name')
            ->get();

        return response()->json([
            'success' => true,
            'students' => $students
        ]);
    }

    /**
     * Créer une leçon
     */
    public function createLesson(Request $request)
    {
        $user = auth()->user();
        $teacher = \DB::table('teachers')->where('user_id', $user->id)->first();
        
        if (!$teacher) {
            return response()->json(['error' => 'Profil enseignant non trouvé'], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'student_id' => 'required|integer',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'duration' => 'required|integer|min:15|max:180',
            'type' => 'required|in:lesson,group,training,competition',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Mappage des types vers les course_type_id
        $typeMapping = [
            'lesson' => 1,
            'group' => 2,
            'training' => 3,
            'competition' => 4
        ];

        // Créer le cours
        $lessonId = \DB::table('lessons')->insertGetId([
            'teacher_id' => $teacher->id,
            'student_id' => $request->student_id,
            'course_type_id' => $typeMapping[$request->type],
            'location_id' => 1,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'status' => 'scheduled',
            'notes' => $request->title,
            'price' => 45.00,
            'payment_status' => 'pending',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return response()->json([
            'success' => true,
            'lesson_id' => $lessonId,
            'message' => 'Cours créé avec succès'
        ], 201);
    }

    /**
     * Revenus de l'enseignant
     */
    public function getEarnings(Request $request)
    {
        $user = auth()->user();
        $teacher = \DB::table('teachers')->where('user_id', $user->id)->first();
        
        if (!$teacher) {
            return response()->json(['error' => 'Profil enseignant non trouvé'], 404);
        }

        $period = $request->query('period', 'current_month');
        $startDate = null;
        $endDate = null;
        
        switch ($period) {
            case 'current_month':
                $startDate = now()->startOfMonth();
                $endDate = now()->endOfMonth();
                break;
            case 'last_month':
                $startDate = now()->subMonth()->startOfMonth();
                $endDate = now()->subMonth()->endOfMonth();
                break;
            case 'current_year':
                $startDate = now()->startOfYear();
                $endDate = now()->endOfYear();
                break;
        }

        $query = \DB::table('lessons')
            ->where('teacher_id', $teacher->id);

        if ($startDate) {
            $query->where('start_time', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('start_time', '<=', $endDate);
        }

        $totalEarnings = $query->sum('price');
        $totalLessons = $query->count();

        return response()->json([
            'success' => true,
            'total_earnings' => $totalEarnings,
            'total_lessons' => $totalLessons,
            'average_per_lesson' => $totalLessons > 0 ? $totalEarnings / $totalLessons : 0,
            'period' => $period
        ]);
    }

    /**
     * Disponibilités
     */
    public function getAvailabilities()
    {
        $user = auth()->user();
        $teacher = \DB::table('teachers')->where('user_id', $user->id)->first();
        
        if (!$teacher) {
            return response()->json([
                'success' => true,
                'availabilities' => []
            ]);
        }

        // Récupérer les disponibilités depuis la base de données
        $availabilities = \DB::table('teacher_availabilities')
            ->where('teacher_id', $teacher->id)
            ->get();

        return response()->json([
            'success' => true,
            'availabilities' => $availabilities
        ]);
    }

    /**
     * Créer une disponibilité
     */
    public function createAvailability(Request $request)
    {
        $user = auth()->user();
        $teacher = \DB::table('teachers')->where('user_id', $user->id)->first();
        
        if (!$teacher) {
            return response()->json(['error' => 'Profil enseignant non trouvé'], 404);
        }

        $validator = Validator::make($request->all(), [
            'day_of_week' => 'required|integer|between:0,6',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $availabilityId = \DB::table('teacher_availabilities')->insertGetId([
            'teacher_id' => $teacher->id,
            'day_of_week' => $request->day_of_week,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return response()->json([
            'success' => true,
            'availability_id' => $availabilityId,
            'message' => 'Disponibilité créée avec succès'
        ], 201);
    }
}
