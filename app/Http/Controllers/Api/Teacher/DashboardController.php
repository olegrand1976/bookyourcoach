<?php

namespace App\Http\Controllers\Api\Teacher;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Lesson;
use App\Models\Teacher;
use Carbon\Carbon;

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
        $user = $request->user();
        $teacher = $user->teacher;

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
        $today_lessons = (clone $lessons)->where('status', 'confirmed')->whereDate('scheduled_at', $now->toDateString())->count();
        $active_students = (clone $lessons)->whereIn('status', ['confirmed', 'completed'])->distinct('student_id')->count('student_id');
        $monthly_earnings = (clone $lessons)->where('status', 'completed')->whereBetween('scheduled_at', [$startOfMonth, $endOfMonth])->sum('price');

        // La note moyenne n'est pas encore implémentée
        $average_rating = 0;

        // Stats de la semaine
        $week_lessons = (clone $lessons)->where('status', 'confirmed')->whereBetween('scheduled_at', [$startOfWeek, $endOfWeek])->count();
        $week_hours = (clone $lessons)->where('status', 'completed')->whereBetween('scheduled_at', [$startOfWeek, $endOfWeek])->sum('duration') / 60;
        $week_earnings = (clone $lessons)->where('status', 'completed')->whereBetween('scheduled_at', [$startOfWeek, $endOfWeek])->sum('price');

        // Nouveaux élèves ce mois-ci (simplifié)
        $new_students = (clone $lessons)->where('created_at', '>=', $now->subMonth())->distinct('student_id')->count('student_id');

        // --- Prochains cours ---
        $upcomingLessons = (clone $lessons)->with('student.user') // Charger l'élève et son utilisateur associé
            ->where('status', 'confirmed')
            ->where('scheduled_at', '>=', $now)
            ->orderBy('scheduled_at', 'asc')
            ->limit(5)
            ->get()
            ->map(function ($lesson) {
                return [
                    'id' => $lesson->id,
                    'student_name' => $lesson->student->user->name ?? 'Élève inconnu',
                    'type' => $lesson->courseType->name ?? 'Cours',
                    'duration' => $lesson->duration,
                    'scheduled_at' => $lesson->scheduled_at,
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
}
