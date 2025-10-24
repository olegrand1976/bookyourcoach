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

            // Récupérer tous les enseignants actifs sauf l'utilisateur actuel
            $teachers = Teacher::with('user')
                ->where('id', '!=', $currentTeacher->id)
                ->whereHas('user', function($query) {
                    $query->where('role', 'teacher');
                })
                ->get();

            return response()->json([
                'success' => true,
                'data' => $teachers
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des enseignants: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des enseignants'
            ], 500);
        }
    }
}
