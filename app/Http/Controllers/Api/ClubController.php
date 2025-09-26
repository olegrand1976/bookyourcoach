<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ClubController extends Controller
{
    public function dashboard()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'stats' => [
                    'total_teachers' => 0,
                    'total_students' => 0,
                    'total_lessons' => 0,
                    'completed_lessons' => 0,
                    'total_revenue' => 0,
                    'monthly_revenue' => 0,
                ],
                'recentTeachers' => [],
                'recentStudents' => [],
                'recentLessons' => [],
            ],
            'message' => 'Données du dashboard récupérées avec succès'
        ]);
    }

    public function getProfile(Request $request)
    {
        try {
            $user = $request->user();
            
            // Récupérer le club associé à cet utilisateur
            $clubManager = DB::table('club_managers')
                ->where('user_id', $user->id)
                ->first();
            
            if (!$clubManager) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun club associé à cet utilisateur'
                ], 404);
            }
            
            $club = DB::table('clubs')
                ->where('id', $clubManager->club_id)
                ->first();
            
            if (!$club) {
                return response()->json([
                    'success' => false,
                    'message' => 'Club non trouvé'
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'data' => $club
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du profil'
            ], 500);
        }
    }

    public function updateProfile(Request $request)
    {
        try {
            $user = $request->user();
            
            // Récupérer le club associé à cet utilisateur
            $clubManager = DB::table('club_managers')
                ->where('user_id', $user->id)
                ->first();
            
            if (!$clubManager) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun club associé à cet utilisateur'
                ], 404);
            }
            
            // Mettre à jour le club
            $updateData = $request->only([
                'name', 'description', 'email', 'phone', 'street', 'street_number',
                'city', 'postal_code', 'country', 'website', 'facilities', 
                'disciplines', 'max_students', 'subscription_price'
            ]);
            
            // Encoder les arrays en JSON si nécessaire
            if (isset($updateData['facilities']) && is_array($updateData['facilities'])) {
                $updateData['facilities'] = json_encode($updateData['facilities']);
            }
            if (isset($updateData['disciplines']) && is_array($updateData['disciplines'])) {
                $updateData['disciplines'] = json_encode($updateData['disciplines']);
            }
            
            DB::table('clubs')
                ->where('id', $clubManager->club_id)
                ->update($updateData);
            
            return response()->json([
                'success' => true,
                'message' => 'Profil mis à jour avec succès'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du profil'
            ], 500);
        }
    }

    public function getCustomSpecialties(Request $request)
    {
        try {
            $user = $request->user();
            
            // Récupérer le club associé à cet utilisateur
            $clubManager = DB::table('club_managers')
                ->where('user_id', $user->id)
                ->first();
            
            if (!$clubManager) {
                return response()->json([
                    'success' => false,
                    'data' => []
                ]);
            }
            
            $specialties = DB::table('club_custom_specialties')
                ->where('club_id', $clubManager->club_id)
                ->where('is_active', true)
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $specialties
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'data' => []
            ]);
        }
    }

    public function getTeachers(Request $request)
    {
        try {
            $user = $request->user();
            
            // Récupérer le club associé à cet utilisateur
            $clubManager = DB::table('club_managers')
                ->where('user_id', $user->id)
                ->first();
            
            if (!$clubManager) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun club associé à cet utilisateur'
                ], 404);
            }
            
            $teachers = DB::table('club_teachers')
                ->join('teachers', 'club_teachers.teacher_id', '=', 'teachers.id')
                ->join('users', 'teachers.user_id', '=', 'users.id')
                ->where('club_teachers.club_id', $clubManager->club_id)
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
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $teachers
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des enseignants'
            ], 500);
        }
    }

    public function getStudents(Request $request)
    {
        try {
            $user = $request->user();
            
            // Récupérer le club associé à cet utilisateur
            $clubManager = DB::table('club_managers')
                ->where('user_id', $user->id)
                ->first();
            
            if (!$clubManager) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun club associé à cet utilisateur'
                ], 404);
            }
            
            $students = DB::table('club_students')
                ->join('students', 'club_students.student_id', '=', 'students.id')
                ->join('users', 'students.user_id', '=', 'users.id')
                ->where('club_students.club_id', $clubManager->club_id)
                ->where('club_students.is_active', true)
                ->select(
                    'users.id',
                    'users.name',
                    'users.email',
                    'students.level',
                    'students.total_lessons',
                    'students.total_spent',
                    'club_students.joined_at'
                )
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $students
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des étudiants'
            ], 500);
        }
    }
}