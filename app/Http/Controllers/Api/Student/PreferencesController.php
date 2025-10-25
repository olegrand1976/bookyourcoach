<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Models\Discipline;
use App\Models\CourseType;
use App\Models\StudentPreference;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class PreferencesController extends Controller
{
    /**
     * Get all available disciplines with their course types
     */
    public function getDisciplines(): JsonResponse
    {
        $disciplines = Discipline::with(['courseTypes' => function($query) {
            $query->where('is_active', true);
        }])
        ->where('is_active', true)
        ->get();

        return response()->json([
            'success' => true,
            'data' => $disciplines
        ]);
    }

    /**
     * Get student's current preferences
     */
    public function getPreferences(): JsonResponse
    {
        $student = Auth::user()->student;
        
        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Profil étudiant non trouvé'
            ], 404);
        }

        $preferences = StudentPreference::with(['discipline', 'courseType'])
            ->where('student_id', $student->id)
            ->get()
            ->groupBy('discipline_id');

        return response()->json([
            'success' => true,
            'data' => $preferences
        ]);
    }

    /**
     * Update student preferences
     */
    public function updatePreferences(Request $request): JsonResponse
    {
        $student = Auth::user()->student;
        
        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Profil étudiant non trouvé'
            ], 404);
        }

        $request->validate([
            'preferences' => 'required|array',
            'preferences.*.discipline_id' => 'required|exists:disciplines,id',
            'preferences.*.course_type_id' => 'nullable|exists:course_types,id',
            'preferences.*.is_preferred' => 'boolean',
            'preferences.*.priority_level' => 'integer|min:1|max:5',
        ]);

        // Supprimer les anciennes préférences
        StudentPreference::where('student_id', $student->id)->delete();

        // Créer les nouvelles préférences
        foreach ($request->preferences as $preference) {
            StudentPreference::create([
                'student_id' => $student->id,
                'discipline_id' => $preference['discipline_id'],
                'course_type_id' => $preference['course_type_id'] ?? null,
                'is_preferred' => $preference['is_preferred'] ?? true,
                'priority_level' => $preference['priority_level'] ?? 1,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Préférences mises à jour avec succès'
        ]);
    }

    /**
     * Add a single preference
     */
    public function addPreference(Request $request): JsonResponse
    {
        $student = Auth::user()->student;
        
        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Profil étudiant non trouvé'
            ], 404);
        }

        $request->validate([
            'discipline_id' => 'required|exists:disciplines,id',
            'course_type_id' => 'nullable|exists:course_types,id',
            'is_preferred' => 'boolean',
            'priority_level' => 'integer|min:1|max:5',
        ]);

        // Vérifier si la préférence existe déjà
        $existingPreference = StudentPreference::where('student_id', $student->id)
            ->where('discipline_id', $request->discipline_id)
            ->where('course_type_id', $request->course_type_id)
            ->first();

        if ($existingPreference) {
            return response()->json([
                'success' => false,
                'message' => 'Cette préférence existe déjà'
            ], 400);
        }

        $preference = StudentPreference::create([
            'student_id' => $student->id,
            'discipline_id' => $request->discipline_id,
            'course_type_id' => $request->course_type_id,
            'is_preferred' => $request->is_preferred ?? true,
            'priority_level' => $request->priority_level ?? 1,
        ]);

        return response()->json([
            'success' => true,
            'data' => $preference->load(['discipline', 'courseType']),
            'message' => 'Préférence ajoutée avec succès'
        ]);
    }

    /**
     * Remove a preference
     */
    public function removePreference(Request $request): JsonResponse
    {
        $student = Auth::user()->student;
        
        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Profil étudiant non trouvé'
            ], 404);
        }

        $request->validate([
            'discipline_id' => 'required|exists:disciplines,id',
            'course_type_id' => 'nullable|exists:course_types,id',
        ]);

        $deleted = StudentPreference::where('student_id', $student->id)
            ->where('discipline_id', $request->discipline_id)
            ->where('course_type_id', $request->course_type_id)
            ->delete();

        if ($deleted) {
            return response()->json([
                'success' => true,
                'message' => 'Préférence supprimée avec succès'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Préférence non trouvée'
        ], 404);
    }

    /**
     * Get course types for a specific discipline
     */
    public function getCourseTypesByDiscipline($disciplineId): JsonResponse
    {
        $courseTypes = CourseType::where('discipline_id', $disciplineId)
            ->where('is_active', true)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $courseTypes
        ]);
    }
}