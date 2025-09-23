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
}