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
use Illuminate\Support\Facades\Password;
use App\Notifications\TeacherWelcomeNotification;
use App\Notifications\StudentWelcomeNotification;

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
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'nullable|string|min:8',
                'phone' => 'nullable|string|max:20',
                'date_of_birth' => 'nullable|date|before:today',
                'level' => 'nullable|in:debutant,intermediaire,avance,expert',
                'goals' => 'nullable|string',
                'medical_info' => 'nullable|string',
                'disciplines' => 'nullable|array',
                'disciplines.*' => 'integer|exists:disciplines,id',
            ]);

            DB::beginTransaction();

            // Créer l'utilisateur
            $fullName = trim($validated['first_name'] . ' ' . $validated['last_name']);
            $newUser = User::create([
                'name' => $fullName,
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
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

            // Lier les disciplines à l'étudiant si fournies
            if (!empty($validated['disciplines'])) {
                foreach ($validated['disciplines'] as $disciplineId) {
                    DB::table('student_disciplines')->insert([
                        'student_id' => $student->id,
                        'discipline_id' => $disciplineId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
                
                \Log::info('Disciplines liées à l\'élève', [
                    'student_id' => $student->id,
                    'disciplines' => $validated['disciplines']
                ]);
            }

            // Générer un token de réinitialisation de mot de passe
            $resetToken = Password::broker()->createToken($newUser);
            
            // Envoyer l'email de bienvenue avec le lien de réinitialisation
            $newUser->notify(new StudentWelcomeNotification($club->name, $resetToken));

            \Log::info('Email de bienvenue envoyé à l\'élève', [
                'student_id' => $student->id,
                'user_id' => $newUser->id,
                'club_id' => $club->id,
                'email' => $newUser->email
            ]);

            DB::commit();

            // Charger les relations
            $student->load('user');

            return response()->json([
                'success' => true,
                'data' => $student,
                'student' => $student,
                'message' => 'Élève créé avec succès ! Un email a été envoyé à ' . $newUser->email . ' pour définir son mot de passe.'
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
                'first_name' => 'sometimes|string|max:255',
                'last_name' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|unique:users,email,' . $student->user_id,
                'phone' => 'nullable|string|max:20',
                'date_of_birth' => 'nullable|date|before:today',
                'level' => 'nullable|in:debutant,intermediaire,avance,expert',
                'goals' => 'nullable|string',
                'medical_info' => 'nullable|string',
            ]);

            DB::beginTransaction();

            // Mettre à jour l'utilisateur si nécessaire
            if (isset($validated['first_name']) || isset($validated['last_name'])) {
                $firstName = $validated['first_name'] ?? $student->user->first_name;
                $lastName = $validated['last_name'] ?? $student->user->last_name;
                $student->user->update([
                    'name' => trim($firstName . ' ' . $lastName),
                    'first_name' => $firstName,
                    'last_name' => $lastName
                ]);
            }
            
            if (isset($validated['email'])) {
                $student->user->update(['email' => $validated['email']]);
            }
            
            if (isset($validated['phone'])) {
                $student->user->update(['phone' => $validated['phone']]);
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

    /**
     * Renvoyer l'email d'invitation à un élève
     */
    public function resendInvitation(Request $request, $id): JsonResponse
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

            $student = Student::with('user')->findOrFail($id);

            // Vérifier que l'élève appartient au club
            if (!DB::table('club_students')
                ->where('club_id', $club->id)
                ->where('student_id', $student->id)
                ->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cet élève n\'appartient pas à votre club'
                ], 403);
            }

            $studentUser = $student->user;
            if (!$studentUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur élève non trouvé'
                ], 404);
            }

            // Vérifier que l'email est valide
            if (!$studentUser->email || !filter_var($studentUser->email, FILTER_VALIDATE_EMAIL)) {
                return response()->json([
                    'success' => false,
                    'message' => 'L\'adresse email de cet élève est invalide (' . $studentUser->email . '). Veuillez la corriger avant d\'envoyer l\'invitation.'
                ], 400);
            }

            // Générer un token de réinitialisation de mot de passe
            $resetToken = Password::broker()->createToken($studentUser);
            
            // Envoyer la notification
            $studentUser->notify(new StudentWelcomeNotification($club->name, $resetToken));

            \Log::info('Email d\'invitation renvoyé à l\'élève', [
                'student_id' => $id,
                'user_id' => $studentUser->id,
                'club_id' => $club->id,
                'email' => $studentUser->email
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Email d\'invitation renvoyé avec succès à ' . $studentUser->email
            ]);

        } catch (\Exception $e) {
            \Log::error('Erreur lors du renvoi de l\'invitation', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du renvoi de l\'invitation: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer un élève (désactivation)
     */
    public function destroy(Request $request, $id): JsonResponse
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

            $student = Student::findOrFail($id);

            // Vérifier que l'élève appartient au club
            if (!DB::table('club_students')
                ->where('club_id', $club->id)
                ->where('student_id', $student->id)
                ->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cet élève n\'appartient pas à votre club'
                ], 403);
            }

            DB::beginTransaction();

            // Désactiver la relation club-élève
            DB::table('club_students')
                ->where('club_id', $club->id)
                ->where('student_id', $student->id)
                ->update([
                    'is_active' => false,
                    'updated_at' => now()
                ]);

            DB::commit();

            \Log::info('Élève désactivé du club', [
                'student_id' => $id,
                'club_id' => $club->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Élève retiré du club avec succès'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            \Log::error('Erreur lors de la suppression de l\'élève', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de l\'élève: ' . $e->getMessage()
            ], 500);
        }
    }
}
