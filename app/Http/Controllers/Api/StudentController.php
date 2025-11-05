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
     * Récupérer l'historique complet d'un élève (abonnements + cours)
     */
    public function history(Request $request, $studentId): JsonResponse
    {
        try {
            $user = Auth::user();
            
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

            // Vérifier que l'élève appartient au club
            $clubStudent = DB::table('club_students')
                ->where('club_id', $club->id)
                ->where('student_id', $studentId)
                ->first();
            
            if (!$clubStudent) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cet élève n\'appartient pas à votre club'
                ], 403);
            }

            $student = Student::with(['user', 'disciplines'])->findOrFail($studentId);

            // Récupérer les abonnements de l'élève
            $subscriptionInstances = \App\Models\SubscriptionInstance::whereHas('students', function ($query) use ($studentId) {
                    $query->where('students.id', $studentId);
                })
                ->whereHas('subscription', function ($query) use ($club) {
                    if (\App\Models\Subscription::hasClubIdColumn()) {
                        $query->where('club_id', $club->id);
                    } else {
                        $query->whereHas('template', function ($q) use ($club) {
                            $q->where('club_id', $club->id);
                        });
                    }
                })
                ->with([
                    'subscription.template.courseTypes',
                    'subscription.club',
                    'students' => function ($q) {
                        $q->with('user');
                    }
                ])
                ->orderBy('created_at', 'desc')
                ->get();

            // Récupérer les cours de l'élève (via relation many-to-many ou student_id)
            $lessons = \App\Models\Lesson::where(function ($query) use ($studentId) {
                    $query->whereHas('students', function ($q) use ($studentId) {
                        $q->where('students.id', $studentId);
                    })
                    ->orWhere('student_id', $studentId);
                })
                ->with([
                    'teacher.user',
                    'courseType',
                    'location',
                    'club',
                    'students.user'
                ])
                ->orderBy('start_time', 'desc')
                ->limit(100) // Limiter à 100 cours récents
                ->get()
                ->unique('id') // Éviter les doublons si l'élève est à la fois dans students et student_id
                ->values();

            // Statistiques
            $stats = [
                'total_subscriptions' => $subscriptionInstances->count(),
                'active_subscriptions' => $subscriptionInstances->where('status', 'active')->count(),
                'total_lessons' => $lessons->count(),
                'completed_lessons' => $lessons->where('status', 'completed')->count(),
                'total_spent' => $lessons->where('status', 'completed')->sum('price'),
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'student' => $student,
                    'subscriptions' => $subscriptionInstances,
                    'lessons' => $lessons,
                    'stats' => $stats
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la récupération de l\'historique de l\'élève: ' . $e->getMessage(), [
                'student_id' => $studentId,
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de l\'historique: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Créer un nouvel élève (utilisateur + profil étudiant)
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            \Log::info('StudentController::store - Tentative création élève', [
                'user_id' => $user?->id,
                'user_email' => $user?->email,
                'user_role' => $user?->role,
                'has_token' => (bool) $request->bearerToken(),
            ]);
            
            if (!$user) {
                \Log::error('StudentController::store - Utilisateur non authentifié');
                return response()->json([
                    'success' => false,
                    'message' => 'Non authentifié'
                ], 401);
            }
            
            // Vérifier que l'utilisateur est un club
            if ($user->role !== 'club') {
                \Log::warning('StudentController::store - Rôle incorrect', [
                    'expected' => 'club',
                    'actual' => $user->role,
                ]);
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

            // Validation - tous les champs sont maintenant optionnels
            $validated = $request->validate([
                'first_name' => 'nullable|string|max:255',
                'last_name' => 'nullable|string|max:255',
                'email' => 'nullable|email|unique:users,email',
                'password' => 'nullable|string|min:8',
                'phone' => 'nullable|string|max:20',
                'date_of_birth' => 'nullable|date|before:today',
                // 'level' supprimé - n'est plus utilisé
                'goals' => 'nullable|string',
                'medical_info' => 'nullable|string',
                'disciplines' => 'nullable|array',
                'disciplines.*' => 'integer|exists:disciplines,id',
                'medical_documents' => 'nullable|array', // Documents médicaux (pour futur usage)
            ]);
            
            \Log::info('Données validées pour création élève', [
                'validated' => $validated,
                'club_id' => $club->id
            ]);

            DB::beginTransaction();

            $newUser = null;
            $student = null;
            $emailSent = false;

            // Créer un utilisateur UNIQUEMENT si un email est fourni
            if (!empty($validated['email'])) {
                // Construire le nom (utiliser "Élève" si pas de nom fourni)
                $firstName = $validated['first_name'] ?? 'Élève';
                $lastName = $validated['last_name'] ?? '';
                $fullName = trim($firstName . ' ' . $lastName);
                if (empty($fullName) || $fullName === 'Élève') {
                    $fullName = 'Élève ' . ($club->students()->count() + 1);
                }
                
                $newUser = User::create([
                    'name' => $fullName,
                    'first_name' => $validated['first_name'] ?? null,
                    'last_name' => $validated['last_name'] ?? null,
                    'email' => $validated['email'],
                    'password' => isset($validated['password']) ? Hash::make($validated['password']) : Hash::make(bin2hex(random_bytes(16))),
                    'phone' => $validated['phone'] ?? null,
                    'role' => 'student'
                ]);

                // Générer un token de réinitialisation de mot de passe
                $resetToken = Password::broker()->createToken($newUser);
                
                // Envoyer l'email de bienvenue UNIQUEMENT si email est présent
                try {
                    $newUser->notify(new StudentWelcomeNotification($club->name, $resetToken));
                    $emailSent = true;
                    
                    \Log::info('Email de bienvenue envoyé à l\'élève', [
                        'user_id' => $newUser->id,
                        'club_id' => $club->id,
                        'email' => $newUser->email
                    ]);
                } catch (\Exception $e) {
                    \Log::error('Erreur lors de l\'envoi de l\'email de bienvenue', [
                        'user_id' => $newUser->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Créer le profil étudiant (même sans utilisateur si pas d'email)
            $student = Student::create([
                'user_id' => $newUser?->id, // Peut être null si pas d'email
                'club_id' => $club->id,
                'first_name' => $validated['first_name'] ?? null,
                'last_name' => $validated['last_name'] ?? null,
                'date_of_birth' => $validated['date_of_birth'] ?? null,
                // 'level' supprimé - n'est plus utilisé
                'goals' => $validated['goals'] ?? null,
                'medical_info' => $validated['medical_info'] ?? null,
            ]);

            // Lier l'élève au club via la table pivot
            DB::table('club_students')->insert([
                'club_id' => $club->id,
                'student_id' => $student->id,
                // 'level' supprimé de la table pivot également
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

            DB::commit();

            // Charger les relations
            $student->load('user');

            // Message de succès adapté
            $message = 'Élève créé avec succès !';
            if ($emailSent && $newUser) {
                $message .= ' Un email a été envoyé à ' . $newUser->email . ' pour définir son mot de passe.';
            } elseif (!$newUser) {
                $message .= ' Aucun compte utilisateur n\'a été créé car aucun email n\'a été fourni. Vous pourrez compléter ces informations plus tard.';
            }

            return response()->json([
                'success' => true,
                'data' => $student,
                'student' => $student,
                'user_created' => $newUser !== null,
                'email_sent' => $emailSent,
                'message' => $message
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
    public function update(Request $request, $studentId): JsonResponse
    {
        try {
            $user = Auth::user();
            
            $student = Student::findOrFail($studentId);

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

            // Validation - email peut être requis si l'élève n'a pas encore de compte
            $validationRules = [
                'first_name' => 'nullable|string|max:255',
                'last_name' => 'nullable|string|max:255',
                'phone' => 'nullable|string|max:20',
                'date_of_birth' => 'nullable|date|before:today',
                'goals' => 'nullable|string',
                'medical_info' => 'nullable|string',
                'disciplines' => 'nullable|array',
                'disciplines.*' => 'integer|exists:disciplines,id',
            ];
            
            // Si l'élève n'a pas de user_id, l'email devient requis si fourni
            if (!$student->user_id) {
                $validationRules['email'] = 'nullable|email|unique:users,email';
            } else {
                $validationRules['email'] = 'sometimes|email|unique:users,email,' . $student->user_id;
            }
            
            $validated = $request->validate($validationRules);

            DB::beginTransaction();

            $emailSent = false;
            $userCreated = false;

            // Si l'élève n'a pas de compte utilisateur ET qu'un email est fourni, créer le compte
            if (!$student->user_id && !empty($validated['email'])) {
                $firstName = $validated['first_name'] ?? 'Élève';
                $lastName = $validated['last_name'] ?? '';
                $fullName = trim($firstName . ' ' . $lastName);
                if (empty($fullName) || $fullName === 'Élève') {
                    $fullName = 'Élève ' . $student->id;
                }
                
                $newUser = User::create([
                    'name' => $fullName,
                    'first_name' => $validated['first_name'] ?? null,
                    'last_name' => $validated['last_name'] ?? null,
                    'email' => $validated['email'],
                    'password' => Hash::make(bin2hex(random_bytes(16))),
                    'phone' => $validated['phone'] ?? null,
                    'role' => 'student'
                ]);
                
                $student->user_id = $newUser->id;
                $student->save();
                
                // Générer un token et envoyer l'email
                $resetToken = Password::broker()->createToken($newUser);
                try {
                    $club = $user->getFirstClub();
                    $newUser->notify(new StudentWelcomeNotification($club->name, $resetToken));
                    $emailSent = true;
                } catch (\Exception $e) {
                    \Log::error('Erreur lors de l\'envoi de l\'email', ['error' => $e->getMessage()]);
                }
                
                $userCreated = true;
            } elseif ($student->user_id) {
                // Mettre à jour l'utilisateur existant
                $studentUser = $student->user;
                
                if (isset($validated['first_name']) || isset($validated['last_name'])) {
                    $firstName = $validated['first_name'] ?? $studentUser->first_name;
                    $lastName = $validated['last_name'] ?? $studentUser->last_name;
                    $studentUser->update([
                        'name' => trim($firstName . ' ' . $lastName),
                        'first_name' => $firstName,
                        'last_name' => $lastName
                    ]);
                }
                
                if (isset($validated['email'])) {
                    $studentUser->update(['email' => $validated['email']]);
                }
                
                if (isset($validated['phone'])) {
                    $studentUser->update(['phone' => $validated['phone']]);
                }
            }

            // Mettre à jour le profil étudiant
            $studentData = [];
            if (isset($validated['first_name'])) $studentData['first_name'] = $validated['first_name'];
            if (isset($validated['last_name'])) $studentData['last_name'] = $validated['last_name'];
            if (isset($validated['date_of_birth'])) $studentData['date_of_birth'] = $validated['date_of_birth'];
            if (isset($validated['goals'])) $studentData['goals'] = $validated['goals'];
            if (isset($validated['medical_info'])) $studentData['medical_info'] = $validated['medical_info'];

            if (!empty($studentData)) {
                $student->update($studentData);
            }

            // Gérer les disciplines
            if (isset($validated['disciplines'])) {
                // Synchroniser les disciplines de l'étudiant
                $student->disciplines()->sync($validated['disciplines']);
                
                \Log::info('Disciplines liées à l\'élève', [
                    'student_id' => $student->id,
                    'disciplines' => $validated['disciplines']
                ]);
            }

            DB::commit();

            $student->refresh();
            $student->load('user');

            $message = 'Élève mis à jour avec succès';
            if ($userCreated && $emailSent) {
                $message .= '. Un compte utilisateur a été créé et un email de bienvenue a été envoyé.';
            } elseif ($userCreated) {
                $message .= '. Un compte utilisateur a été créé.';
            }

            return response()->json([
                'success' => true,
                'data' => $student,
                'user_created' => $userCreated,
                'email_sent' => $emailSent,
                'message' => $message
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Erreur lors de la mise à jour de l\'élève', [
                'student_id' => $studentId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
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
    public function resendInvitation(Request $request, $studentId): JsonResponse
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

            $student = Student::with('user')->findOrFail($studentId);

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
                'student_id' => $studentId,
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

    /**
     * Activer ou désactiver un élève
     */
    public function toggleStatus(Request $request, $studentId): JsonResponse
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

            $student = Student::findOrFail($studentId);

            // Vérifier que l'élève appartient au club
            $clubStudent = DB::table('club_students')
                ->where('club_id', $club->id)
                ->where('student_id', $student->id)
                ->first();
            
            if (!$clubStudent) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cet élève n\'appartient pas à votre club'
                ], 403);
            }

            DB::beginTransaction();

            // Basculer le statut
            $newStatus = !$clubStudent->is_active;
            
            DB::table('club_students')
                ->where('club_id', $club->id)
                ->where('student_id', $student->id)
                ->update([
                    'is_active' => $newStatus,
                    'updated_at' => now()
                ]);

            DB::commit();

            \Log::info('Statut de l\'élève modifié', [
                'student_id' => $studentId,
                'club_id' => $club->id,
                'is_active' => $newStatus
            ]);

            return response()->json([
                'success' => true,
                'message' => $newStatus ? 'Élève activé avec succès' : 'Élève désactivé avec succès',
                'data' => [
                    'is_active' => $newStatus
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            \Log::error('Erreur lors de la modification du statut de l\'élève', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la modification du statut de l\'élève: ' . $e->getMessage()
            ], 500);
        }
    }
}
