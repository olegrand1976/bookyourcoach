<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class StudentController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        // Le middleware 'auth:sanctum' est appliqué dans les routes
    }

    /**
     * Dashboard étudiant
     */
    public function dashboard()
    {
        $user = auth()->user();
        
        // Récupérer l'ID étudiant depuis la table students
        $student = \DB::table('students')->where('user_id', $user->id)->first();
        
        if (!$student) {
            return response()->json([
                'success' => true,
                'stats' => [
                    'total_lessons' => 0,
                    'completed_lessons' => 0,
                    'upcoming_lessons' => 0,
                    'total_spent' => 0,
                ],
                'upcomingLessons' => []
            ]);
        }

        // Statistiques de base
        $totalLessons = \DB::table('lessons')
            ->where('student_id', $student->id)
            ->count();

        $completedLessons = \DB::table('lessons')
            ->where('student_id', $student->id)
            ->where('status', 'completed')
            ->count();

        $upcomingLessons = \DB::table('lessons')
            ->where('student_id', $student->id)
            ->where('start_time', '>=', now())
            ->count();

        $totalSpent = \DB::table('lessons')
            ->where('student_id', $student->id)
            ->where('status', 'completed')
            ->sum('price');

        return response()->json([
            'success' => true,
            'stats' => [
                'total_lessons' => $totalLessons,
                'completed_lessons' => $completedLessons,
                'upcoming_lessons' => $upcomingLessons,
                'total_spent' => $totalSpent,
            ],
            'upcomingLessons' => []
        ]);
    }

    /**
     * Calendrier étudiant
     */
    public function getCalendar(Request $request)
    {
        $user = auth()->user();
        $student = \DB::table('students')->where('user_id', $user->id)->first();
        
        if (!$student) {
            return response()->json([
                'success' => true,
                'events' => []
            ]);
        }

        $events = \DB::table('lessons')
            ->leftJoin('teachers', 'lessons.teacher_id', '=', 'teachers.id')
            ->leftJoin('users', 'teachers.user_id', '=', 'users.id')
            ->where('lessons.student_id', $student->id)
            ->select(
                'lessons.*',
                'users.name as teacher_name',
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
     * Liste des clubs
     */
    public function getClubs(Request $request)
    {
        $user = auth()->user();
        $student = \DB::table('students')->where('user_id', $user->id)->first();
        
        if (!$student) {
            return response()->json([
                'success' => true,
                'clubs' => []
            ]);
        }

        $clubs = \DB::table('clubs')
            ->leftJoin('lessons', 'clubs.id', '=', 'lessons.club_id')
            ->where('lessons.student_id', $student->id)
            ->select(
                'clubs.*',
                \DB::raw('COUNT(DISTINCT lessons.id) as lessons_count')
            )
            ->groupBy('clubs.id')
            ->get();

        return response()->json([
            'success' => true,
            'clubs' => $clubs
        ]);
    }

    /**
     * Réserver une leçon
     */
    public function bookLesson(Request $request)
    {
        $user = auth()->user();
        $student = \DB::table('students')->where('user_id', $user->id)->first();
        
        if (!$student) {
            return response()->json(['error' => 'Profil étudiant non trouvé'], 404);
        }

        $validator = Validator::make($request->all(), [
            'teacher_id' => 'required|integer',
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
            'teacher_id' => $request->teacher_id,
            'student_id' => $student->id,
            'course_type_id' => $typeMapping[$request->type],
            'location_id' => 1,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'status' => 'scheduled',
            'notes' => $request->notes ?? 'Cours réservé',
            'price' => 45.00,
            'payment_status' => 'pending',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return response()->json([
            'success' => true,
            'lesson_id' => $lessonId,
            'message' => 'Cours réservé avec succès'
        ], 201);
    }

    /**
     * Liste des leçons
     */
    public function getLessons(Request $request)
    {
        $user = auth()->user();
        $student = \DB::table('students')->where('user_id', $user->id)->first();
        
        if (!$student) {
            return response()->json([
                'success' => true,
                'lessons' => []
            ]);
        }

        $status = $request->query('status', 'all');
        $query = \DB::table('lessons')
            ->leftJoin('teachers', 'lessons.teacher_id', '=', 'teachers.id')
            ->leftJoin('users', 'teachers.user_id', '=', 'users.id')
            ->where('lessons.student_id', $student->id);

        if ($status !== 'all') {
            $query->where('lessons.status', $status);
        }

        $lessons = $query->select(
            'lessons.*',
            'users.name as teacher_name'
        )
        ->orderBy('lessons.start_time', 'desc')
        ->get();

        return response()->json([
            'success' => true,
            'lessons' => $lessons
        ]);
    }

    /**
     * Profil étudiant
     */
    public function getProfile()
    {
        $user = auth()->user();
        $student = \DB::table('students')->where('user_id', $user->id)->first();
        
        if (!$student) {
            return response()->json(['error' => 'Profil étudiant non trouvé'], 404);
        }

        $profile = \DB::table('profiles')->where('user_id', $user->id)->first();

        return response()->json([
            'success' => true,
            'profile' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $profile ? $profile->phone : $user->phone,
                'birth_date' => $profile ? $profile->date_of_birth : null,
                'address' => $profile ? $profile->address : null,
                'city' => $profile ? $profile->city : null,
                'postal_code' => $profile ? $profile->postal_code : null,
                'country' => $profile ? $profile->country : null,
                'level' => $student->level,
                'course_preferences' => $student->course_preferences,
                'emergency_contact' => $student->emergency_contact,
                'medical_notes' => $student->medical_notes,
                'status' => $user->status ?? 'active',
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ]
        ]);
    }

    /**
     * Synchronisation Google Calendar
     */
    public function syncGoogleCalendar(Request $request)
    {
        $user = auth()->user();
        
        // Logique de synchronisation Google Calendar
        // Cette méthode sera implémentée selon les besoins
        
        return response()->json([
            'success' => true,
            'message' => 'Synchronisation Google Calendar initiée'
        ]);
    }
}
