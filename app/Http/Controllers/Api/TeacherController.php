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

            // Statistiques générales
            $todayLessons = Lesson::where('teacher_id', $teacher->id)
                ->whereDate('start_time', $now->toDateString())
                ->whereIn('status', ['confirmed', 'completed'])
                ->count();

            $totalLessons = Lesson::where('teacher_id', $teacher->id)
                ->whereIn('status', ['confirmed', 'completed'])
                ->count();

            $activeStudents = Lesson::where('teacher_id', $teacher->id)
                ->whereIn('status', ['confirmed', 'completed'])
                ->distinct('student_id')
                ->whereNotNull('student_id')
                ->count('student_id');

            $weeklyLessons = Lesson::where('teacher_id', $teacher->id)
                ->whereBetween('start_time', [$startOfWeek, $endOfWeek])
                ->whereIn('status', ['confirmed', 'completed'])
                ->count();

            $weeklyEarnings = Lesson::where('teacher_id', $teacher->id)
                ->whereBetween('start_time', [$startOfWeek, $endOfWeek])
                ->where('status', 'completed')
                ->sum('price');

            $monthlyEarnings = Lesson::where('teacher_id', $teacher->id)
                ->whereBetween('start_time', [$startOfMonth, $endOfMonth])
                ->where('status', 'completed')
                ->sum('price');

            // Heures totales cette semaine
            $weeklyHours = Lesson::where('teacher_id', $teacher->id)
                ->whereBetween('start_time', [$startOfWeek, $endOfWeek])
                ->where('status', 'completed')
                ->get()
                ->sum(function ($lesson) {
                    if (!$lesson->start_time || !$lesson->end_time) {
                        return 0;
                    }
                    return $lesson->start_time->diffInMinutes($lesson->end_time) / 60;
                });

            // Prochains cours (5 prochains)
            $upcomingLessons = Lesson::where('teacher_id', $teacher->id)
                ->with(['student.user', 'courseType', 'location', 'club'])
                ->where('start_time', '>=', $now)
                ->whereIn('status', ['confirmed', 'pending'])
                ->orderBy('start_time', 'asc')
                ->limit(5)
                ->get();

            // Cours récents (5 derniers)
            $recentLessons = Lesson::where('teacher_id', $teacher->id)
                ->with(['student.user', 'courseType', 'location', 'club'])
                ->whereIn('status', ['completed', 'cancelled'])
                ->orderBy('start_time', 'desc')
                ->limit(5)
                ->get();

            // Clubs de l'enseignant
            $clubs = $teacher->clubs()->get();

            // Demandes de remplacement en attente
            $pendingReplacements = \App\Models\LessonReplacement::where(function($query) use ($teacher) {
                $query->where('replacement_teacher_id', $teacher->id)
                      ->orWhere('original_teacher_id', $teacher->id);
            })
            ->where('status', 'pending')
            ->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'stats' => [
                        'today_lessons' => $todayLessons,
                        'total_lessons' => $totalLessons,
                        'active_students' => $activeStudents,
                        'weekly_lessons' => $weeklyLessons,
                        'week_earnings' => round($weeklyEarnings, 2),
                        'week_hours' => round($weeklyHours, 1),
                        'monthly_earnings' => round($monthlyEarnings, 2),
                        'pending_replacements' => $pendingReplacements,
                    ],
                    'upcoming_lessons' => $upcomingLessons,
                    'recent_lessons' => $recentLessons,
                    'clubs' => $clubs,
                    'teacher' => $teacher->load('user')
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur dashboard enseignant: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement du dashboard',
                'error' => $e->getMessage()
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
                ->distinct('student_id')
                ->whereNotNull('student_id')
                ->count('student_id');

            $weekEarnings = Lesson::where('teacher_id', $teacher->id)
                ->whereBetween('start_time', [$startOfWeek, $endOfWeek])
                ->where('status', 'completed')
                ->sum('price');

            return response()->json([
                'success' => true,
                'stats' => [
                    'today_lessons' => $todayLessons,
                    'active_students' => $activeStudents,
                    'week_earnings' => round($weekEarnings, 2),
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

            // Validation des données
            $validated = $request->validate([
                'name' => 'nullable|string|max:255',
                'phone' => 'nullable|string|max:20',
                'birth_date' => 'nullable|date',
                'bio' => 'nullable|string',
                'specialties' => 'nullable|array',
                'certifications' => 'nullable|array',
                'experience_years' => 'nullable|integer|min:0',
                'hourly_rate' => 'nullable|numeric|min:0',
            ]);

            // Mettre à jour les informations de l'utilisateur
            if (isset($validated['name'])) {
                $user->name = $validated['name'];
            }
            if (isset($validated['phone'])) {
                $user->phone = $validated['phone'];
            }
            if (isset($validated['birth_date'])) {
                $user->birth_date = $validated['birth_date'];
            }
            $user->save();

            // Mettre à jour les informations de l'enseignant
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
            if (isset($validated['experience_years'])) {
                $teacherData['experience_years'] = $validated['experience_years'];
            }
            if (isset($validated['hourly_rate'])) {
                $teacherData['hourly_rate'] = $validated['hourly_rate'];
            }

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
