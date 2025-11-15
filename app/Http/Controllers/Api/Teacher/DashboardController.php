<?php

namespace App\Http\Controllers\Api\Teacher;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Lesson;
use App\Models\Teacher;
use App\Models\Availability;
use App\Models\Student;
use App\Models\CourseType;
use App\Models\Location;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    /**
     * Récupère les données complètes pour le tableau de bord de l'enseignant.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDashboardData(Request $request)
    {
        // Temporairement, utiliser l'utilisateur enseignant par défaut pour debug
        $user = \App\Models\User::where('email', 'sophie.martin@activibe.com')->first();
        $teacher = $user ? $user->teacher : null;

        if (!$teacher) {
            return response()->json(['message' => 'Profil enseignant non trouvé.'], 404);
        }

        // --- Calcul des statistiques ---
        $now = Carbon::now();
        $startOfWeek = $now->startOfWeek()->format('Y-m-d H:i:s');
        $endOfWeek = $now->endOfWeek()->format('Y-m-d H:i:s');
        $startOfMonth = $now->startOfMonth()->format('Y-m-d H:i:s');
        $endOfMonth = $now->endOfMonth()->format('Y-m-d H:i:s');

        // Requête de base pour les leçons du prof
        $lessons = Lesson::where('teacher_id', $teacher->id);

        // Stats générales
        $today_lessons = (clone $lessons)->where('status', 'confirmed')->whereDate('start_time', $now->toDateString())->count();
        $active_students = (clone $lessons)->whereIn('status', ['confirmed', 'completed'])->distinct('student_id')->count('student_id');
        $monthly_earnings = (clone $lessons)->where('status', 'completed')->whereBetween('start_time', [$startOfMonth, $endOfMonth])->sum('price');

        // La note moyenne n'est pas encore implémentée
        $average_rating = 0;

        // Stats de la semaine
        $week_lessons = (clone $lessons)->where('status', 'confirmed')->whereBetween('start_time', [$startOfWeek, $endOfWeek])->count();
        $week_hours = (clone $lessons)->where('status', 'completed')->whereBetween('start_time', [$startOfWeek, $endOfWeek])->get()->sum(function ($lesson) {
            return Carbon::parse($lesson->start_time)->diffInMinutes(Carbon::parse($lesson->end_time));
        }) / 60;
        $week_earnings = (clone $lessons)->where('status', 'completed')->whereBetween('start_time', [$startOfWeek, $endOfWeek])->sum('price');

        // Nouveaux élèves ce mois-ci (simplifié)
        $new_students = (clone $lessons)->where('created_at', '>=', $now->subMonth())->distinct('student_id')->count('student_id');

        // --- Prochains cours ---
        $upcomingLessons = (clone $lessons)->with('student.user') // Charger l'élève et son utilisateur associé
            ->where('status', 'confirmed')
            ->where('start_time', '>=', $now)
            ->orderBy('start_time', 'asc')
            ->limit(5)
            ->get()
            ->map(function ($lesson) {
                return [
                    'id' => $lesson->id,
                    'student_name' => $lesson->student->user->name ?? 'Élève inconnu',
                    'type' => $lesson->courseType->name ?? 'Cours',
                    'start_time' => $lesson->start_time,
                    'end_time' => $lesson->end_time,
                    'status' => $lesson->status,
                ];
            });

        return response()->json([
            'stats' => [
                'today_lessons' => $today_lessons,
                'active_students' => $active_students,
                'monthly_earnings' => round($monthly_earnings, 2),
                'average_rating' => $average_rating,
                'week_lessons' => $week_lessons,
                'week_hours' => round($week_hours, 1),
                'week_earnings' => round($week_earnings, 2),
                'new_students' => $new_students,
            ],
            'upcomingLessons' => $upcomingLessons,
        ]);
    }

    /**
     * Récupère les cours de l'enseignant.
     */
    public function getLessons(Request $request)
    {
        $user = $request->user();
        $teacherId = $user->teacher->id;

        $query = Lesson::with(['student.user', 'students.user', 'courseType', 'location', 'club'])
            ->where('teacher_id', $teacherId);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('date')) {
            $date = Carbon::parse($request->date);
            $query->whereDate('start_time', $date);
        }

        $lessons = $query->orderBy('start_time', 'desc')->get();

        return response()->json($lessons);
    }

    /**
     * Crée un nouveau cours.
     */
    public function createLesson(Request $request)
    {
        try {
            $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'required|string',
                'start_time' => 'required|date|after:now',
                'end_time' => 'required|date|after:start_time',
                'student_id' => 'nullable|exists:students,id', // Étudiant principal optionnel (pour compatibilité)
                'student_ids' => 'nullable|array', // Nouveaux étudiants multiples
                'student_ids.*' => 'exists:students,id', // Validation de chaque ID d'étudiant
                'course_type_id' => 'nullable|exists:course_types,id',
                'location_id' => 'nullable|exists:locations,id',
                'location' => 'nullable|string',
                'price' => 'nullable|numeric|min:0',
                'notes' => 'nullable|string',
            ]);

            $user = $request->user();
            $teacherId = $user->teacher->id;

            // Vérifier qu'un élève n'est pas déjà inscrit à la même heure
            if ($request->student_id) {
                $this->checkStudentTimeConflict($request->student_id, $request->start_time);
            }
            // Vérifier aussi pour les étudiants multiples
            if ($request->student_ids && count($request->student_ids) > 0) {
                foreach ($request->student_ids as $studentId) {
                    $this->checkStudentTimeConflict($studentId, $request->start_time);
                }
            }

            // Déterminer le statut du cours
            $hasStudents = $request->student_id || ($request->student_ids && count($request->student_ids) > 0);
            $status = $hasStudents ? 'pending' : 'available';

            $lesson = Lesson::create([
            'teacher_id' => $teacherId,
            'student_id' => $request->student_id, // Étudiant principal (pour compatibilité)
            'course_type_id' => $request->course_type_id ?? 1, // Type par défaut
            'location_id' => $request->location_id ?? 1, // Location par défaut
            'title' => $request->title,
            'description' => $request->description,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'location' => $request->location,
            'price' => $request->price,
            'notes' => $request->notes,
            'status' => $status,
        ]);

            // Ajouter les étudiants multiples via la table de liaison
            if ($request->student_ids && count($request->student_ids) > 0) {
                $studentData = [];
                foreach ($request->student_ids as $studentId) {
                    $studentData[$studentId] = [
                        'status' => 'pending',
                        'price' => $request->price, // Prix par défaut, peut être personnalisé
                        'notes' => null,
                    ];
                }
                $lesson->students()->attach($studentData);
            }

            // Si un étudiant principal est spécifié, l'ajouter aussi à la table de liaison
            if ($request->student_id && !in_array($request->student_id, $request->student_ids ?? [])) {
                $lesson->students()->attach($request->student_id, [
                    'status' => 'pending',
                    'price' => $request->price,
                    'notes' => null,
                ]);
            }

            return response()->json($lesson->load(['courseType', 'location', 'students.user', 'club']), 201);
        } catch (\Exception $e) {
            // Vérifier si c'est une erreur de conflit horaire pour l'élève
            if (str_contains($e->getMessage(), 'déjà un cours programmé')) {
                Log::warning('Conflit horaire pour l\'élève:', [
                    'message' => $e->getMessage(),
                    'request' => $request->all()
                ]);
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage()
                ], 422);
            }
            // Propager les autres exceptions
            throw $e;
        }
    }

    /**
     * Met à jour un cours.
     */
    public function updateLesson(Request $request, $id)
    {
        $request->validate([
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'start_time' => 'nullable|date',
            'end_time' => 'nullable|date|after:start_time',
            'location' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'status' => 'nullable|string|in:available,confirmed,completed,cancelled',
        ]);

        $user = $request->user();
        $teacherId = $user->teacher->id;

        $lesson = Lesson::where('id', $id)
            ->where('teacher_id', $teacherId)
            ->firstOrFail();

        $lesson->update($request->only([
            'title', 'description', 'start_time', 'end_time',
            'location', 'price', 'notes', 'status'
        ]));

        return response()->json($lesson->load(['student.user', 'courseType', 'location', 'club']));
    }

    /**
     * Supprime un cours.
     */
    public function deleteLesson(Request $request, $id)
    {
        $user = $request->user();
        $teacherId = $user->teacher->id;

        $lesson = Lesson::where('id', $id)
            ->where('teacher_id', $teacherId)
            ->firstOrFail();

        $lesson->delete();

        return response()->json(['message' => 'Cours supprimé avec succès.']);
    }

    /**
     * Récupère les disponibilités de l'enseignant.
     */
    public function getAvailabilities(Request $request)
    {
        $user = $request->user();
        $teacher = $user->teacher;

        if (!$teacher) {
            return response()->json(['message' => 'Profil enseignant non trouvé.'], 404);
        }

        $availabilities = Availability::with('location')
            ->where('teacher_id', $teacher->id)
            ->orderBy('start_time', 'asc')
            ->get();

        return response()->json($availabilities);
    }

    /**
     * Crée une disponibilité.
     */
    public function createAvailability(Request $request)
    {
        $request->validate([
            'start_time' => 'required|date|after:now',
            'end_time' => 'required|date|after:start_time',
            'location_id' => 'required|exists:locations,id',
            'notes' => 'nullable|string',
        ]);

        $user = $request->user();
        $teacherId = $user->teacher->id;

        $availability = Availability::create([
            'teacher_id' => $teacherId,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'location_id' => $request->location_id,
            'notes' => $request->notes,
            'is_available' => true,
        ]);

        return response()->json($availability->load('location'), 201);
    }

    /**
     * Met à jour une disponibilité.
     */
    public function updateAvailability(Request $request, $id)
    {
        $request->validate([
            'start_time' => 'nullable|date',
            'end_time' => 'nullable|date|after:start_time',
            'location_id' => 'nullable|exists:locations,id',
            'is_available' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ]);

        $user = $request->user();
        $teacherId = $user->teacher->id;

        $availability = Availability::where('id', $id)
            ->where('teacher_id', $teacherId)
            ->firstOrFail();

        $availability->update($request->only([
            'start_time', 'end_time', 'location_id', 'is_available', 'notes'
        ]));

        return response()->json($availability->load('location'));
    }

    /**
     * Supprime une disponibilité.
     */
    public function deleteAvailability(Request $request, $id)
    {
        $user = $request->user();
        $teacherId = $user->teacher->id;

        $availability = Availability::where('id', $id)
            ->where('teacher_id', $teacherId)
            ->firstOrFail();

        $availability->delete();

        return response()->json(['message' => 'Disponibilité supprimée avec succès.']);
    }

    /**
     * Récupère les statistiques de l'enseignant.
     */
    public function getStats(Request $request)
    {
        $user = $request->user();
        $teacherId = $user->teacher->id;

        $now = Carbon::now();
        $startOfMonth = $now->startOfMonth();
        $endOfMonth = $now->endOfMonth();

        $lessons = Lesson::where('teacher_id', $teacherId);

        $total_lessons = $lessons->count();
        $completed_lessons = (clone $lessons)->where('status', 'completed')->count();
        $monthly_earnings = (clone $lessons)->where('status', 'completed')
            ->whereBetween('start_time', [$startOfMonth, $endOfMonth])
            ->sum('price');

        $total_hours = (clone $lessons)->where('status', 'completed')
            ->get()
            ->sum(function ($lesson) {
                return Carbon::parse($lesson->start_time)->diffInMinutes(Carbon::parse($lesson->end_time));
            }) / 60;

        return response()->json([
            'total_lessons' => $total_lessons,
            'completed_lessons' => $completed_lessons,
            'monthly_earnings' => round($monthly_earnings, 2),
            'total_hours' => round($total_hours, 1),
        ]);
    }

    /**
     * Récupère les étudiants de l'enseignant.
     */
    public function getStudents(Request $request)
    {
        $user = $request->user();
        $teacherId = $user->teacher->id;

        // Récupérer tous les étudiants avec leurs utilisateurs associés
        $students = Student::with('user')->get();

        return response()->json($students);
    }

    /**
     * Vérifie qu'un élève n'est pas déjà inscrit à la même heure
     * 
     * @param int $studentId L'ID de l'élève
     * @param string $startTime L'heure de début du cours (format Y-m-d H:i:s)
     * @throws \Exception Si l'élève a déjà un cours à cette heure
     */
    private function checkStudentTimeConflict(int $studentId, string $startTime): void
    {
        try {
            $startDateTime = Carbon::parse($startTime);
            $date = $startDateTime->format('Y-m-d');
            $time = $startDateTime->format('H:i');
            
            // Créer une plage de temps très précise (même heure et minute)
            $exactStartTime = Carbon::parse($date . ' ' . $time . ':00');
            $exactEndTime = $exactStartTime->copy()->addMinute(); // +1 minute pour avoir la plage exacte
            
            // Vérifier les cours où l'élève est l'étudiant principal (student_id)
            $conflictingLessonsAsMain = Lesson::where('student_id', $studentId)
                ->where('start_time', '>=', $exactStartTime)
                ->where('start_time', '<', $exactEndTime)
                ->where('status', '!=', 'cancelled') // Ignorer les cours annulés
                ->exists();
            
            if ($conflictingLessonsAsMain) {
                throw new \Exception("Cet élève a déjà un cours programmé à cette heure ({$time}).");
            }
            
            // Vérifier les cours où l'élève est dans la relation many-to-many (students)
            $conflictingLessonsAsSecondary = Lesson::whereHas('students', function ($query) use ($studentId) {
                    $query->where('students.id', $studentId);
                })
                ->where('start_time', '>=', $exactStartTime)
                ->where('start_time', '<', $exactEndTime)
                ->where('status', '!=', 'cancelled') // Ignorer les cours annulés
                ->exists();
            
            if ($conflictingLessonsAsSecondary) {
                throw new \Exception("Cet élève a déjà un cours programmé à cette heure ({$time}).");
            }
            
        } catch (\Exception $e) {
            // Si c'est notre exception de conflit, la propager
            if (str_contains($e->getMessage(), 'déjà un cours programmé')) {
                throw $e;
            }
            // Sinon, logger et continuer (pour ne pas bloquer si erreur technique)
            Log::warning("Erreur lors de la vérification de conflit horaire pour l'élève: " . $e->getMessage());
        }
    }
}
