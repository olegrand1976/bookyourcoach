<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Teacher;
use App\Models\Lesson;
use Illuminate\Support\Facades\Log;

class TeacherController extends Controller
{
    public function dashboard()
    {
        return response()->json(['message' => 'Welcome to the teacher dashboard']);
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
     * Liste des élèves des clubs où l'enseignant travaille
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

            // Récupérer les clubs où l'enseignant travaille
            $clubIds = $teacher->clubs()->pluck('clubs.id');

            // Récupérer les élèves de ces clubs
            $students = \App\Models\Student::with('user')
                ->whereIn('club_id', $clubIds)
                ->get()
                ->map(function($student) {
                    return [
                        'id' => $student->id,
                        'name' => $student->user->name ?? 'Sans nom',
                        'email' => $student->user->email ?? '',
                        'level' => $student->level ?? 'débutant',
                        'age' => $student->age,
                        'club_id' => $student->club_id
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
}
