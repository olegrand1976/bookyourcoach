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

            // Optimiser les statistiques avec une seule requête de base
            $baseQuery = Lesson::where('teacher_id', $teacher->id);
            
            // Statistiques générales (optimisées)
            $todayLessons = (clone $baseQuery)
                ->whereDate('start_time', $now->toDateString())
                ->whereIn('status', ['confirmed', 'completed'])
                ->count();

            $totalLessons = (clone $baseQuery)
                ->whereIn('status', ['confirmed', 'completed'])
                ->count();

            $activeStudents = (clone $baseQuery)
                ->whereIn('status', ['confirmed', 'completed'])
                ->whereNotNull('student_id')
                ->distinct('student_id')
                ->count('student_id');

            $weeklyLessons = (clone $baseQuery)
                ->whereBetween('start_time', [$startOfWeek, $endOfWeek])
                ->whereIn('status', ['confirmed', 'completed'])
                ->count();

            $weeklyEarnings = (clone $baseQuery)
                ->whereBetween('start_time', [$startOfWeek, $endOfWeek])
                ->where('status', 'completed')
                ->sum('price');

            $monthlyEarnings = (clone $baseQuery)
                ->whereBetween('start_time', [$startOfMonth, $endOfMonth])
                ->where('status', 'completed')
                ->sum('price');

            // Heures totales cette semaine (optimisé avec SQL au lieu de PHP)
            $weeklyHours = (clone $baseQuery)
                ->whereBetween('start_time', [$startOfWeek, $endOfWeek])
                ->where('status', 'completed')
                ->selectRaw('SUM(TIMESTAMPDIFF(MINUTE, start_time, end_time)) / 60.0 as total_hours')
                ->value('total_hours') ?? 0;

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
            $upcomingLessons = Lesson::where('teacher_id', $teacher->id)
                ->select('lessons.id', 'lessons.teacher_id', 'lessons.student_id', 'lessons.course_type_id', 'lessons.location_id', 'lessons.club_id', 
                         'lessons.start_time', 'lessons.end_time', 'lessons.status', 'lessons.price', 'lessons.notes')
                ->with([
                    'student:id,user_id',
                    'student.user:id,name',
                    'courseType:id,name',
                    'location:id,name',
                    'club:id,name'
                ])
                ->whereBetween('start_time', [$dateFrom, $dateTo])
                ->whereIn('status', ['confirmed', 'pending', 'completed', 'cancelled'])
                ->orderBy('start_time', 'asc')
                ->limit(100) // Limite augmentée pour permettre de voir tous les cours de la période
                ->get();

            // Cours récents uniquement si la période inclut le passé
            $recentLessons = collect();
            if (in_array($period, ['previous_month', 'current_month'])) {
                $recentLessons = Lesson::where('teacher_id', $teacher->id)
                    ->select('lessons.id', 'lessons.teacher_id', 'lessons.student_id', 'lessons.course_type_id', 'lessons.location_id', 'lessons.club_id',
                             'lessons.start_time', 'lessons.end_time', 'lessons.status', 'lessons.price', 'lessons.notes')
                    ->with([
                        'student:id,user_id',
                        'student.user:id,name',
                        'courseType:id,name',
                        'location:id,name',
                        'club:id,name'
                    ])
                    ->whereBetween('start_time', [$dateFrom, $dateTo])
                    ->whereIn('status', ['completed', 'cancelled'])
                    ->orderBy('start_time', 'desc')
                    ->limit(20)
                    ->get();
            }

            // Clubs de l'enseignant avec seulement les colonnes nécessaires pour optimiser
            $clubs = $teacher->clubs()->select('clubs.id', 'clubs.name', 'clubs.email', 'clubs.phone', 'clubs.address', 'clubs.postal_code', 'clubs.city', 'clubs.country', 'clubs.legal_representative_name', 'clubs.legal_representative_role')->get();

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
                // experience_years et hourly_rate sont exclus - ils ne peuvent pas être modifiés par l'enseignant
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
            
            Log::info('📝 [TeacherController::updateProfile] État AVANT save():', [
                'user_id' => $user->id,
                'name' => $user->name,
                'phone' => $user->phone,
                'birth_date' => $user->birth_date,
                'birth_date_original' => $originalBirthDate,
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
