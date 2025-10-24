<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class StudentController extends Controller
{
    public function dashboard()
    {
        return response()->json(['message' => 'Welcome to the student dashboard']);
    }

    /**
     * Créer un nouvel élève (utilisateur + profil étudiant)
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Vérifier que l'utilisateur est un club
            if ($user->role !== 'club') {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès réservé aux clubs'
                ], 403);
            }

            $club = $user->getFirstClub();
            if (!$club) {
                return response()->json([
                    'success' => false,
                    'message' => 'Club non trouvé'
                ], 404);
            }

            // Validation
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'nullable|string|min:8',
                'phone' => 'nullable|string|max:20',
                'date_of_birth' => 'nullable|date|before:today',
                'level' => 'nullable|in:debutant,intermediaire,avance,expert',
                'goals' => 'nullable|string',
                'medical_info' => 'nullable|string',
            ]);

            DB::beginTransaction();

            // Créer l'utilisateur
            $newUser = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => isset($validated['password']) ? Hash::make($validated['password']) : Hash::make(bin2hex(random_bytes(16))),
                'phone' => $validated['phone'] ?? null,
                'role' => 'student'
            ]);

            // Créer le profil étudiant
            $student = Student::create([
                'user_id' => $newUser->id,
                'club_id' => $club->id,
                'date_of_birth' => $validated['date_of_birth'] ?? null,
                'level' => $validated['level'] ?? null,
                'goals' => $validated['goals'] ?? null,
                'medical_info' => $validated['medical_info'] ?? null,
            ]);

            // Lier l'élève au club via la table pivot
            DB::table('club_students')->insert([
                'club_id' => $club->id,
                'student_id' => $student->id,
                'level' => $validated['level'] ?? null,
                'goals' => $validated['goals'] ?? null,
                'medical_info' => $validated['medical_info'] ?? null,
                'is_active' => true,
                'joined_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();

            // Charger les relations
            $student->load('user');

            return response()->json([
                'success' => true,
                'data' => $student,
                'message' => 'Élève créé avec succès'
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de l\'élève',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mettre à jour un élève
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $user = Auth::user();
            
            $student = Student::with('user')->findOrFail($id);

            // Vérifier les permissions
            if ($user->role === 'club') {
                $club = $user->getFirstClub();
                if (!$club || !DB::table('club_students')
                    ->where('club_id', $club->id)
                    ->where('student_id', $student->id)
                    ->exists()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cet élève n\'appartient pas à votre club'
                    ], 403);
                }
            }

            // Validation
            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'date_of_birth' => 'nullable|date|before:today',
                'level' => 'nullable|in:debutant,intermediaire,avance,expert',
                'goals' => 'nullable|string',
                'medical_info' => 'nullable|string',
            ]);

            DB::beginTransaction();

            // Mettre à jour l'utilisateur si nécessaire
            if (isset($validated['name'])) {
                $student->user->update(['name' => $validated['name']]);
            }

            // Mettre à jour le profil étudiant
            $studentData = [];
            if (isset($validated['date_of_birth'])) $studentData['date_of_birth'] = $validated['date_of_birth'];
            if (isset($validated['level'])) $studentData['level'] = $validated['level'];
            if (isset($validated['goals'])) $studentData['goals'] = $validated['goals'];
            if (isset($validated['medical_info'])) $studentData['medical_info'] = $validated['medical_info'];

            if (!empty($studentData)) {
                $student->update($studentData);
            }

            DB::commit();

            $student->refresh();
            $student->load('user');

            return response()->json([
                'success' => true,
                'data' => $student,
                'message' => 'Élève mis à jour avec succès'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de l\'élève',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
