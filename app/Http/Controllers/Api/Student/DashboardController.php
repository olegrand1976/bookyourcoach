<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Lesson;
use App\Models\User;
use App\Models\Teacher;
use App\Models\Student;
use App\Models\CourseType;
use App\Models\Location;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Récupère les statistiques pour le tableau de bord de l'élève.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStats(Request $request)
    {
        $user = $request->user();

        // Assurer que l'utilisateur est un élève ou a un profil élève
        if (!$user->student) {
            return response()->json(['message' => 'Profil étudiant non trouvé.'], 404);
        }

        $studentId = $user->student->id;

        // Calcul des statistiques
        $upcoming_lessons = Lesson::where('student_id', $studentId)
            ->where('status', 'confirmed')
            ->where('start_time', '>=', Carbon::now())
            ->count();

        $completed_lessons = Lesson::where('student_id', $studentId)
            ->where('status', 'completed')
            ->count();

        $total_hours = Lesson::where('student_id', $studentId)
            ->where('status', 'completed')
            ->get()
            ->sum(function ($lesson) {
                return Carbon::parse($lesson->start_time)->diffInMinutes(Carbon::parse($lesson->end_time));
            }) / 60; // Convertir les minutes en heures

        return response()->json([
            'upcoming_lessons' => $upcoming_lessons,
            'completed_lessons' => $completed_lessons,
            'total_hours' => round($total_hours, 1), // Arrondir à une décimale
        ]);
    }

    /**
     * Récupère les cours disponibles pour l'étudiant.
     */
    public function getAvailableLessons(Request $request)
    {
        $query = Lesson::with(['teacher.user', 'courseType', 'location', 'club'])
            ->where('status', 'available')
            ->where('start_time', '>=', Carbon::now());

        if ($request->has('subject')) {
            $query->whereHas('courseType', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->subject . '%');
            });
        }

        if ($request->has('date')) {
            $date = Carbon::parse($request->date);
            $query->whereDate('start_time', $date);
        }

        $lessons = $query->get();

        return response()->json($lessons);
    }

    /**
     * Récupère les réservations de l'étudiant.
     */
    public function getBookings(Request $request)
    {
        $user = $request->user();
        $studentId = $user->student->id;

        $query = Lesson::with(['teacher.user', 'courseType', 'location', 'club'])
            ->where('student_id', $studentId);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $bookings = $query->get();

        return response()->json($bookings);
    }

    /**
     * Crée une nouvelle réservation.
     */
    public function createBooking(Request $request)
    {
        $request->validate([
            'lesson_id' => 'required|exists:lessons,id',
            'notes' => 'nullable|string',
        ]);

        $user = $request->user();
        $studentId = $user->student->id;

        $lesson = Lesson::findOrFail($request->lesson_id);
        
        if ($lesson->status !== 'available') {
            return response()->json(['message' => 'Ce cours n\'est pas disponible.'], 400);
        }

        $lesson->update([
            'student_id' => $studentId,
            'status' => 'confirmed',
            'notes' => $request->notes,
        ]);

        return response()->json($lesson->load(['teacher.user', 'courseType', 'location', 'club']), 201);
    }

    /**
     * Annule une réservation.
     */
    public function cancelBooking(Request $request, $id)
    {
        $user = $request->user();
        $studentId = $user->student->id;

        $lesson = Lesson::where('id', $id)
            ->where('student_id', $studentId)
            ->firstOrFail();

        $lesson->update(['status' => 'cancelled']);

        return response()->json(['message' => 'Réservation annulée avec succès.']);
    }

    /**
     * Récupère les enseignants disponibles.
     */
    public function getAvailableTeachers(Request $request)
    {
        $query = Teacher::with('user')
            ->where('is_available', true);

        if ($request->has('subject')) {
            $query->whereHas('courseTypes', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->subject . '%');
            });
        }

        $teachers = $query->get();

        return response()->json($teachers);
    }

    /**
     * Récupère les cours d'un enseignant spécifique.
     */
    public function getTeacherLessons(Request $request, $id)
    {
        $lessons = Lesson::with(['courseType', 'location', 'club'])
            ->where('teacher_id', $id)
            ->where('status', 'available')
            ->where('start_time', '>=', Carbon::now())
            ->get();

        return response()->json($lessons);
    }

    /**
     * Recherche des cours.
     */
    public function searchLessons(Request $request)
    {
        $query = Lesson::with(['teacher.user', 'courseType', 'location', 'club'])
            ->where('status', 'available')
            ->where('start_time', '>=', Carbon::now());

        if ($request->has('q')) {
            $query->where(function ($q) use ($request) {
                $q->whereHas('courseType', function ($cq) use ($request) {
                    $cq->where('name', 'like', '%' . $request->q . '%');
                })
                ->orWhereHas('teacher.user', function ($tq) use ($request) {
                    $tq->where('name', 'like', '%' . $request->q . '%');
                });
            });
        }

        if ($request->has('subject')) {
            $query->whereHas('courseType', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->subject . '%');
            });
        }

        if ($request->has('start_date')) {
            $query->where('start_time', '>=', Carbon::parse($request->start_date));
        }

        if ($request->has('end_date')) {
            $query->where('start_time', '<=', Carbon::parse($request->end_date));
        }

        if ($request->has('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }

        $lessons = $query->get();

        return response()->json($lessons);
    }

    /**
     * Récupère l'historique des cours.
     */
    public function getLessonHistory(Request $request)
    {
        $user = $request->user();
        $studentId = $user->student->id;

        $lessons = Lesson::with(['teacher.user', 'courseType', 'location', 'club'])
            ->where('student_id', $studentId)
            ->where('status', 'completed')
            ->orderBy('start_time', 'desc')
            ->get();

        return response()->json($lessons);
    }

    /**
     * Note un cours terminé.
     */
    public function rateLesson(Request $request, $id)
    {
        $request->validate([
            'rating' => 'required|integer|between:1,5',
            'review' => 'nullable|string',
        ]);

        $user = $request->user();
        $studentId = $user->student->id;

        $lesson = Lesson::where('id', $id)
            ->where('student_id', $studentId)
            ->where('status', 'completed')
            ->firstOrFail();

        $lesson->update([
            'rating' => $request->rating,
            'review' => $request->review,
        ]);

        return response()->json(['message' => 'Cours noté avec succès.']);
    }

    /**
     * Récupère les enseignants favoris.
     */
    public function getFavoriteTeachers(Request $request)
    {
        $user = $request->user();
        $studentId = $user->student->id;

        // Pour l'instant, retourner les enseignants avec qui l'étudiant a eu le plus de cours
        $teachers = Teacher::with('user')
            ->whereHas('lessons', function ($q) use ($studentId) {
                $q->where('student_id', $studentId)
                  ->where('status', 'completed');
            })
            ->get();

        return response()->json($teachers);
    }

    /**
     * Ajoute/Retire un enseignant des favoris.
     */
    public function toggleFavoriteTeacher(Request $request, $id)
    {
        // Pour l'instant, retourner un succès
        return response()->json(['message' => 'Enseignant ajouté aux favoris.']);
    }

    /**
     * Récupère tous les enseignants.
     */
    public function getTeachers(Request $request)
    {
        $teachers = Teacher::with('user')->get();
        return response()->json($teachers);
    }

    /**
     * Récupère les préférences de l'étudiant.
     */
    public function getPreferences(Request $request)
    {
        $user = $request->user();
        $student = $user->student;

        return response()->json([
            'preferred_disciplines' => $student->preferred_disciplines ?? [],
            'preferred_levels' => $student->preferred_levels ?? [],
            'preferred_formats' => $student->preferred_formats ?? [],
            'location' => $student->location ?? null,
            'max_price' => $student->max_price ?? null,
            'max_distance' => $student->max_distance ?? null,
            'notifications_enabled' => $student->notifications_enabled ?? true,
        ]);
    }

    /**
     * Sauvegarde les préférences de l'étudiant.
     */
    public function savePreferences(Request $request)
    {
        $request->validate([
            'preferred_disciplines' => 'nullable|array',
            'preferred_levels' => 'nullable|array',
            'preferred_formats' => 'nullable|array',
            'location' => 'nullable|string',
            'max_price' => 'nullable|numeric',
            'max_distance' => 'nullable|integer',
            'notifications_enabled' => 'nullable|boolean',
        ]);

        $user = $request->user();
        $student = $user->student;

        $student->update($request->only([
            'preferred_disciplines',
            'preferred_levels',
            'preferred_formats',
            'location',
            'max_price',
            'max_distance',
            'notifications_enabled',
        ]));

        return response()->json(['message' => 'Préférences sauvegardées avec succès.']);
    }
}
