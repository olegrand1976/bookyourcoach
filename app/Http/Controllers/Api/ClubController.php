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
            
            // Log pour debugging
            \Log::info('ClubController::getProfile - User:', [
                'user_id' => $user->id,
                'email' => $user->email,
                'role' => $user->role
            ]);
            
            // Récupérer le club associé à cet utilisateur
            $clubUser = DB::table('club_user')
                ->where('user_id', $user->id)
                ->where('is_admin', true)
                ->first();
            
            if (!$clubUser) {
                \Log::warning('ClubController::getProfile - Aucun club_manager trouvé', [
                    'user_id' => $user->id,
                    'email' => $user->email
                ]);
                
                // Si l'utilisateur a le rôle 'club' mais n'est pas dans club_managers,
                // retourner un profil par défaut plutôt qu'une erreur 404
                if ($user->role === 'club') {
                    return response()->json([
                        'success' => true,
                        'data' => [
                            'id' => null,
                            'name' => $user->name ?? 'Mon Club',
                            'email' => $user->email,
                            'phone' => null,
                            'website' => null,
                            'description' => null,
                            'address' => null,
                            'city' => null,
                            'postal_code' => null,
                            'country' => null,
                            'is_active' => true,
                            'activity_types' => [],
                            'disciplines' => [],
                            'discipline_settings' => [],
                            'needs_setup' => true // Indicateur pour le frontend
                        ]
                    ]);
                }
                
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun club associé à cet utilisateur'
                ], 404);
            }
            
            $club = DB::table('clubs')
                ->where('id', $clubUser->club_id)
                ->first();
            
            if (!$club) {
                \Log::error('ClubController::getProfile - Club non trouvé', [
                    'club_id' => $clubUser->club_id,
                    'user_id' => $user->id
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Club non trouvé'
                ], 404);
            }
            
            \Log::info('ClubController::getProfile - Club trouvé', [
                'club_id' => $club->id,
                'club_name' => $club->name
            ]);
            
            return response()->json([
                'success' => true,
                'data' => $club
            ]);
            
        } catch (\Exception $e) {
            \Log::error('ClubController::getProfile - Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
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
            
            \Log::info('ClubController::updateProfile - User:', [
                'user_id' => $user->id,
                'email' => $user->email,
                'role' => $user->role
            ]);
            
            // Récupérer le club associé à cet utilisateur
            $clubUser = DB::table('club_user')
                ->where('user_id', $user->id)
                ->where('is_admin', true)
                ->first();
            
            if (!$clubUser) {
                \Log::warning('ClubController::updateProfile - Aucun club_manager trouvé', [
                    'user_id' => $user->id,
                    'email' => $user->email
                ]);
                
                // Si l'utilisateur a le rôle 'club' mais n'est pas dans club_user,
                // créer un nouveau club et l'association
                if ($user->role === 'club') {
                    // Préparer les données du club
                    $updateData = $request->only([
                        'name', 'description', 'email', 'phone', 'address',
                        'city', 'postal_code', 'country', 'website', 'is_active',
                        'activity_types', 'disciplines', 'discipline_settings', 'schedule_config'
                    ]);
                    
                    // Encoder les arrays en JSON si nécessaire
                    if (isset($updateData['activity_types']) && is_array($updateData['activity_types'])) {
                        $updateData['activity_types'] = json_encode($updateData['activity_types']);
                    }
                    if (isset($updateData['disciplines']) && is_array($updateData['disciplines'])) {
                        $updateData['disciplines'] = json_encode($updateData['disciplines']);
                    }
                    if (isset($updateData['discipline_settings']) && is_array($updateData['discipline_settings'])) {
                        $updateData['discipline_settings'] = json_encode($updateData['discipline_settings']);
                    }
                    if (isset($updateData['schedule_config']) && is_array($updateData['schedule_config'])) {
                        $updateData['schedule_config'] = json_encode($updateData['schedule_config']);
                    }
                    
                    // Valeurs par défaut
                    $updateData['created_at'] = now();
                    $updateData['updated_at'] = now();
                    
                    // Créer le nouveau club
                    $clubId = DB::table('clubs')->insertGetId($updateData);
                    
                    // Créer l'association club_user
                    DB::table('club_user')->insert([
                        'club_id' => $clubId,
                        'user_id' => $user->id,
                        'role' => 'owner',
                        'is_admin' => true,
                        'joined_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                    
                    \Log::info('ClubController::updateProfile - Nouveau club créé', [
                        'club_id' => $clubId,
                        'user_id' => $user->id
                    ]);
                    
                    return response()->json([
                        'success' => true,
                        'message' => 'Profil créé avec succès'
                    ]);
                }
                
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun club associé à cet utilisateur'
                ], 404);
            }
            
            // Mettre à jour le club existant
            $updateData = $request->only([
                'name', 'description', 'email', 'phone', 'address',
                'city', 'postal_code', 'country', 'website', 'is_active',
                'activity_types', 'disciplines', 'discipline_settings', 'schedule_config'
            ]);
            
            // Encoder les arrays en JSON si nécessaire
            if (isset($updateData['activity_types']) && is_array($updateData['activity_types'])) {
                $updateData['activity_types'] = json_encode($updateData['activity_types']);
            }
            if (isset($updateData['disciplines']) && is_array($updateData['disciplines'])) {
                $updateData['disciplines'] = json_encode($updateData['disciplines']);
            }
            if (isset($updateData['discipline_settings']) && is_array($updateData['discipline_settings'])) {
                $updateData['discipline_settings'] = json_encode($updateData['discipline_settings']);
            }
            if (isset($updateData['schedule_config']) && is_array($updateData['schedule_config'])) {
                $updateData['schedule_config'] = json_encode($updateData['schedule_config']);
            }
            
            $updateData['updated_at'] = now();
            
            DB::table('clubs')
                ->where('id', $clubUser->club_id)
                ->update($updateData);
            
            \Log::info('ClubController::updateProfile - Club mis à jour', [
                'club_id' => $clubUser->club_id,
                'user_id' => $user->id
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Profil mis à jour avec succès'
            ]);
            
        } catch (\Exception $e) {
            \Log::error('ClubController::updateProfile - Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
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
            $clubUser = DB::table('club_user')
                ->where('user_id', $user->id)
                ->where('is_admin', true)
                ->first();
            
            if (!$clubUser) {
                return response()->json([
                    'success' => false,
                    'data' => []
                ]);
            }
            
            $specialties = DB::table('club_custom_specialties')
                ->where('club_id', $clubUser->club_id)
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
            
            $teachers = DB::table('club_teachers')
                ->join('teachers', 'club_teachers.teacher_id', '=', 'teachers.id')
                ->join('users', 'teachers.user_id', '=', 'users.id')
                ->where('club_teachers.club_id', $clubUser->club_id)
                ->where('club_teachers.is_active', true)
                ->select(
                    'teachers.id',  // Corrigé : retourner teachers.id au lieu de users.id
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
            
            $students = DB::table('club_students')
                ->join('students', 'club_students.student_id', '=', 'students.id')
                ->join('users', 'students.user_id', '=', 'users.id')
                ->where('club_students.club_id', $clubUser->club_id)
                ->where('club_students.is_active', true)
                ->select(
                    'students.id',  // Corrigé : retourner students.id au lieu de users.id
                    'users.name',
                    'users.email',
                    'students.date_of_birth',
                    'students.level',
                    'students.total_lessons',
                    'students.total_spent',
                    'club_students.joined_at'
                )
                ->get()
                ->map(function($student) {
                    // Calculer l'âge si date_of_birth existe
                    if ($student->date_of_birth) {
                        $student->age = \Carbon\Carbon::parse($student->date_of_birth)->age;
                    } else {
                        $student->age = null;
                    }
                    return $student;
                });
            
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