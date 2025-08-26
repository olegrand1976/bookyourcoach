<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Lesson;
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
            ->where('scheduled_at', '>=', Carbon::now())
            ->count();

        $completed_lessons = Lesson::where('student_id', $studentId)
            ->where('status', 'completed')
            ->count();

        $total_hours = Lesson::where('student_id', $studentId)
            ->where('status', 'completed')
            ->sum('duration') / 60; // Convertir les minutes en heures

        return response()->json([
            'upcoming_lessons' => $upcoming_lessons,
            'completed_lessons' => $completed_lessons,
            'total_hours' => round($total_hours, 1), // Arrondir à une décimale
        ]);
    }
}
