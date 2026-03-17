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
use Illuminate\Validation\Rule;
use App\Notifications\TeacherWelcomeNotification;
use App\Notifications\StudentWelcomeNotification;
use Carbon\Carbon;

class StudentController extends Controller
{
    /**
     * Récupère l'étudiant actif depuis le contexte de la requête.
     * Utilise active_student_id injecté par le middleware SetActiveStudentContext.
     * 
     * @param Request $request
     * @return Student|null
     */
    protected function getActiveStudent(Request $request)
    {
        $user = $request->user();
        
        if (!$user || !$user->student) {
            return null;
        }

        // Récupérer l'ID de l'étudiant actif depuis la requête (injecté par le middleware)
        $activeStudentId = $request->input('active_student_id', $user->student->id);
        
        // Vérifier que l'étudiant est bien lié au compte ou est le compte principal
        $linkedStudents = $user->getLinkedStudents();
        $isLinked = $linkedStudents->contains('id', $activeStudentId) 
                 || $user->student->id === $activeStudentId;
        
        if (!$isLinked) {
            // Si l'étudiant n'est plus lié, retourner le compte principal
            return $user->student;
        }

        // Récupérer et retourner l'étudiant actif
        return Student::with('user')->find($activeStudentId) ?? $user->student;
    }

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

            // Récupérer les abonnements de l'élève pour ce club
            // Utiliser la table pivot subscription_instance_students pour trouver les abonnements
            $subscriptionInstances = \App\Models\SubscriptionInstance::whereHas('students', function ($query) use ($studentId) {
                    $query->where('students.id', $studentId);
                })
                ->whereHas('subscription', function ($q) use ($club) {
                    // Filtrer par club_id directement sur subscription (colonne existe)
                    $q->where('club_id', $club->id);
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
            
            // Log pour debug
            \Log::info('Abonnements trouvés pour élève', [
                'student_id' => $studentId,
                'club_id' => $club->id,
                'count' => $subscriptionInstances->count(),
                'subscription_ids' => $subscriptionInstances->pluck('id')->toArray(),
                'subscription_numbers' => $subscriptionInstances->pluck('subscription.subscription_number')->toArray()
            ]);

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
                    'students.user',
                    'subscriptionInstances.subscription.template.courseTypes' // Inclure les abonnements liés avec leurs types de cours
                ])
                ->orderBy('start_time', 'desc')
                ->limit(100) // Limiter à 100 cours récents
                ->get()
                ->unique('id') // Éviter les doublons si l'élève est à la fois dans students et student_id
                ->values();

            // Calculer la couverture d'abonnement pour les cours futurs
            $now = Carbon::now();
            $uncoveredLessonsCount = 0;
            
            // Pré-charger les abonnements actifs avec leurs types de cours pour optimiser
            $activeSubscriptions = $subscriptionInstances->where('status', 'active');
            
            $lessons = $lessons->map(function ($lesson) use ($activeSubscriptions, $now, &$uncoveredLessonsCount) {
                $lessonDate = Carbon::parse($lesson->start_time);
                $isFuture = $lessonDate->isAfter($now);
                $courseTypeId = $lesson->course_type_id;
                
                // Par défaut, considérer comme couvert (pour les cours passés ou déjà liés à un abonnement)
                $lesson->subscription_coverage = [
                    'is_future' => $isFuture,
                    'is_covered' => true,
                    'coverage_end_date' => null,
                    'covering_subscription_id' => null,
                    'warning' => null
                ];
                
                // Si le cours est dans le futur, vérifier la couverture
                if ($isFuture && $lesson->status !== 'cancelled') {
                    // Vérifier si le cours est déjà attaché à un abonnement
                    $isAttachedToSubscription = $lesson->subscriptionInstances && $lesson->subscriptionInstances->count() > 0;
                    
                    // Trouver un abonnement actif qui couvre ce type de cours et cette date
                    $coveringSubscription = null;
                    $latestExpiresAt = null;
                    
                    foreach ($activeSubscriptions as $subscription) {
                        // Vérifier si l'abonnement expire après la date du cours
                        if ($subscription->expires_at && Carbon::parse($subscription->expires_at)->isBefore($lessonDate)) {
                            continue; // Abonnement expiré avant la date du cours
                        }
                        
                        // Vérifier si l'abonnement couvre le type de cours
                        $template = $subscription->subscription?->template;
                        if ($template && $template->courseTypes) {
                            $coveredCourseTypeIds = $template->courseTypes->pluck('id')->toArray();
                            if (in_array($courseTypeId, $coveredCourseTypeIds)) {
                                // Cet abonnement couvre ce type de cours
                                if (!$latestExpiresAt || ($subscription->expires_at && Carbon::parse($subscription->expires_at)->isAfter($latestExpiresAt))) {
                                    $latestExpiresAt = $subscription->expires_at ? Carbon::parse($subscription->expires_at) : null;
                                    $coveringSubscription = $subscription;
                                }
                            }
                        }
                    }
                    
                    if ($coveringSubscription) {
                        $lesson->subscription_coverage = [
                            'is_future' => true,
                            'is_covered' => true,
                            'coverage_end_date' => $latestExpiresAt ? $latestExpiresAt->toDateString() : null,
                            'covering_subscription_id' => $coveringSubscription->id,
                            'warning' => null
                        ];
                    } else {
                        // Aucun abonnement actif ne couvre ce cours
                        $uncoveredLessonsCount++;
                        $lesson->subscription_coverage = [
                            'is_future' => true,
                            'is_covered' => false,
                            'coverage_end_date' => null,
                            'covering_subscription_id' => null,
                            'warning' => 'Ce cours n\'est pas couvert par un abonnement actif'
                        ];
                    }
                }
                
                return $lesson;
            });

            // Statistiques
            $stats = [
                'total_subscriptions' => $subscriptionInstances->count(),
                'active_subscriptions' => $subscriptionInstances->where('status', 'active')->count(),
                'total_lessons' => $lessons->count(),
                'completed_lessons' => $lessons->where('status', 'completed')->count(),
                'total_spent' => $lessons->where('status', 'completed')->sum('price'),
                'uncovered_future_lessons' => $uncoveredLessonsCount, // Nouveau: cours futurs non couverts
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

            // Validation - champs requis pour la création
            $rules = [
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'password' => 'nullable|string|min:8',
                'phone' => 'nullable|string|max:20',
                'date_of_birth' => 'nullable|date|before:today',
                'level' => 'nullable|string|max:255',
                'goals' => 'nullable|string',
                'medical_info' => 'nullable|string',
                'disciplines' => 'nullable|array',
                'disciplines.*' => 'integer|exists:disciplines,id',
                'medical_documents' => 'nullable|array', // Documents médicaux (pour futur usage)
            ];
            
            // Email optionnel - si fourni, doit être valide et unique pour le rôle student
            if ($request->has('email') && $request->email !== null) {
                $rules['email'] = [
                    'required',
                    'email',
                    \Illuminate\Validation\Rule::unique('users')->where(function ($query) {
                        return $query->where('role', 'student');
                    }),
                ];
            } else {
                $rules['email'] = 'nullable|email';
            }
            
            $validated = $request->validate($rules);
            
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
                
                // Récupérer le téléphone même s'il n'est pas dans validated
                $userPhone = $validated['phone'] ?? ($request->input('phone') ?: null);
                
                $newUser = User::create([
                    'name' => $fullName,
                    'first_name' => $validated['first_name'] ?? null,
                    'last_name' => $validated['last_name'] ?? null,
                    'email' => $validated['email'],
                    'password' => isset($validated['password']) ? Hash::make($validated['password']) : Hash::make(bin2hex(random_bytes(16))),
                    'phone' => $userPhone,
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
            // S'assurer que phone et email sont bien récupérés même s'ils ne sont pas dans validated
            $phone = $validated['phone'] ?? ($request->input('phone') ?: null);
            $email = $validated['email'] ?? ($request->input('email') ?: null);
            
            $student = Student::create([
                'user_id' => $newUser?->id, // Peut être null si pas d'email
                'club_id' => $club->id,
                'first_name' => $validated['first_name'] ?? null,
                'last_name' => $validated['last_name'] ?? null,
                'date_of_birth' => $validated['date_of_birth'] ?? null,
                'phone' => $phone,
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
                    ], 404);
                }
            }

            // Validation - email peut être requis si l'élève n'a pas encore de compte
            $validationRules = [
                'first_name' => 'nullable|string|max:255',
                'last_name' => 'nullable|string|max:255',
                'phone' => 'nullable|string|max:20',
                'date_of_birth' => 'nullable|date|before:today',
                'level' => 'nullable|string|max:255',
                'goals' => 'nullable|string',
                'medical_info' => 'nullable|string',
                'disciplines' => 'nullable|array',
                'disciplines.*' => 'integer|exists:disciplines,id',
            ];
            
            // Si l'élève n'a pas de user_id, l'email devient requis si fourni
            if (!$student->user_id) {
                $validationRules['email'] = [
                    'nullable',
                    'email',
                    // Vérifier l'unicité uniquement pour le rôle student
                    Rule::unique('users')->where(function ($query) {
                        return $query->where('role', 'student');
                    }),
                ];
            } else {
                $validationRules['email'] = [
                    'sometimes',
                    'nullable',
                    'email',
                    // Vérifier l'unicité uniquement pour le rôle student (ignorer si email est vide)
                    Rule::unique('users')->where(function ($query) {
                        return $query->where('role', 'student');
                    })->ignore($student->user_id),
                ];
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
                
                // Gérer l'email : accepter les chaînes vides pour permettre l'effacement
                if (array_key_exists('email', $validated)) {
                    $emailValue = $validated['email'];
                    // Convertir les chaînes vides en null pour la base de données
                    $studentUser->update(['email' => $emailValue === '' ? null : $emailValue]);
                }
                
                // Gérer le téléphone : accepter les chaînes vides pour permettre l'effacement
                if (array_key_exists('phone', $validated)) {
                    $phoneValue = $validated['phone'];
                    // Convertir les chaînes vides en null pour la base de données
                    $studentUser->update(['phone' => $phoneValue === '' ? null : $phoneValue]);
                }
            }

            // Mettre à jour le profil étudiant
            $studentData = [];
            if (isset($validated['first_name'])) $studentData['first_name'] = $validated['first_name'];
            if (isset($validated['last_name'])) $studentData['last_name'] = $validated['last_name'];
            if (isset($validated['date_of_birth'])) $studentData['date_of_birth'] = $validated['date_of_birth'];
            // Gérer le téléphone : synchroniser avec users.phone pour éviter les incohérences
            // Toujours mettre à jour students.phone si phone est fourni dans la requête
            if (array_key_exists('phone', $validated)) {
                $phoneValue = $validated['phone'];
                // Convertir les chaînes vides en null pour la base de données
                $studentData['phone'] = $phoneValue === '' ? null : $phoneValue;
            }
            if (isset($validated['level'])) $studentData['level'] = $validated['level'];
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

            DB::beginTransaction();

            try {
                $studentUser = $student->user;
                
                // Si l'élève n'a pas de compte utilisateur, on ne peut pas envoyer d'invitation
                if (!$studentUser) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Cet élève n\'a pas de compte utilisateur. Veuillez d\'abord mettre à jour l\'élève avec une adresse email valide pour créer son compte et envoyer l\'invitation.'
                    ], 400);
                }

                // Vérifier que l'email est valide
                if (!$studentUser->email || !filter_var($studentUser->email, FILTER_VALIDATE_EMAIL)) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'L\'adresse email de cet élève est invalide (' . ($studentUser->email ?? 'non définie') . '). Veuillez la corriger avant d\'envoyer l\'invitation.'
                    ], 400);
                }

                // Générer un token de réinitialisation de mot de passe
                $resetToken = Password::broker()->createToken($studentUser);
                
                // Envoyer la notification
                try {
                    if (app()->runningInConsole() || app()->environment('testing')) {
                        // En mode test ou console, envoyer immédiatement sans queue
                        Notification::sendNow(
                            $studentUser,
                            new StudentWelcomeNotification($club->name, $resetToken)
                        );
                    } else {
                        // En production, utiliser la queue normale
                        $studentUser->notify(new StudentWelcomeNotification($club->name, $resetToken));
                    }
                } catch (\Exception $mailException) {
                    \Log::warning('Impossible d\'envoyer l\'email d\'invitation', [
                        'student_id' => $studentId,
                        'user_id' => $studentUser->id,
                        'email' => $studentUser->email,
                        'error' => $mailException->getMessage(),
                        'note' => 'Le token a été généré mais l\'email n\'a pas pu être envoyé. Vérifiez la configuration MailHog.'
                    ]);
                    // Ne pas bloquer l'opération si l'email échoue
                }

                DB::commit();

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
                DB::rollBack();
                \Log::error('Erreur lors du renvoi de l\'invitation', [
                    'student_id' => $studentId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors du renvoi de l\'invitation: ' . $e->getMessage()
                ], 500);
            }
        } catch (\Exception $e) {
            \Log::error('Erreur lors du renvoi de l\'invitation', [
                'student_id' => $studentId,
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

    /**
     * Mettre à jour les indicateurs de blocage (compte, création d'abonnement par l'élève).
     */
    public function updateBlockFlags(Request $request, $studentId): JsonResponse
    {
        try {
            $user = Auth::user();
            if ($user->role !== 'club') {
                return response()->json(['success' => false, 'message' => 'Accès réservé aux clubs'], 403);
            }
            $club = $user->getFirstClub();
            if (!$club) {
                return response()->json(['success' => false, 'message' => 'Club non trouvé'], 404);
            }
            $student = Student::findOrFail($studentId);
            $clubStudent = DB::table('club_students')
                ->where('club_id', $club->id)
                ->where('student_id', $student->id)
                ->first();
            if (!$clubStudent) {
                return response()->json(['success' => false, 'message' => 'Cet élève n\'appartient pas à votre club'], 403);
            }
            $validated = $request->validate([
                'is_blocked' => 'sometimes|boolean',
                'subscription_creation_blocked' => 'sometimes|boolean',
            ]);
            $updates = array_intersect_key($validated, array_flip(['is_blocked', 'subscription_creation_blocked']));
            if (empty($updates)) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'is_blocked' => (bool) $clubStudent->is_blocked,
                        'subscription_creation_blocked' => (bool) ($clubStudent->subscription_creation_blocked ?? true),
                    ],
                ]);
            }
            $updates['updated_at'] = now();
            DB::table('club_students')
                ->where('club_id', $club->id)
                ->where('student_id', $student->id)
                ->update($updates);
            $updated = DB::table('club_students')
                ->where('club_id', $club->id)
                ->where('student_id', $student->id)
                ->first();
            return response()->json([
                'success' => true,
                'message' => 'Paramètres de blocage mis à jour',
                'data' => [
                    'is_blocked' => (bool) ($updated->is_blocked ?? true),
                    'subscription_creation_blocked' => (bool) ($updated->subscription_creation_blocked ?? true),
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            \Log::error('Erreur updateBlockFlags: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Erreur lors de la mise à jour'], 500);
        }
    }

    /**
     * Bloquer ou débloquer la création d'abonnement pour tous les élèves du club.
     */
    public function bulkSetSubscriptionCreationBlocked(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            if ($user->role !== 'club') {
                return response()->json(['success' => false, 'message' => 'Accès réservé aux clubs'], 403);
            }
            $club = $user->getFirstClub();
            if (!$club) {
                return response()->json(['success' => false, 'message' => 'Club non trouvé'], 404);
            }
            $validated = $request->validate([
                'subscription_creation_blocked' => 'required|boolean',
            ]);
            $blocked = (bool) $validated['subscription_creation_blocked'];
            $count = DB::table('club_students')
                ->where('club_id', $club->id)
                ->update([
                    'subscription_creation_blocked' => $blocked,
                    'updated_at' => now(),
                ]);
            return response()->json([
                'success' => true,
                'message' => $blocked
                    ? "Création d'abonnement bloquée pour les {$count} élève(s)."
                    : "Création d'abonnement autorisée pour les {$count} élève(s).",
                'data' => ['updated_count' => $count, 'subscription_creation_blocked' => $blocked],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            \Log::error('Erreur bulkSetSubscriptionCreationBlocked: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Erreur lors de la mise à jour'], 500);
        }
    }

    /**
     * Archiver en masse les élèves selon un filtre (ex: sans abonnement actif).
     * Met is_active = false sur club_students pour tous les élèves concernés.
     */
    public function bulkArchive(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            if ($user->role !== 'club') {
                return response()->json(['success' => false, 'message' => 'Accès réservé aux clubs'], 403);
            }
            $club = $user->getFirstClub();
            if (!$club) {
                return response()->json(['success' => false, 'message' => 'Club non trouvé'], 404);
            }
            $validated = $request->validate([
                'filter' => 'required|in:no_active_subscription',
            ]);
            $filter = $validated['filter'];
            $clubId = $club->id;

            if ($filter === 'no_active_subscription') {
                $studentIds = DB::table('club_students')
                    ->join('students', 'club_students.student_id', '=', 'students.id')
                    ->where('club_students.club_id', $clubId)
                    ->where('club_students.is_active', true)
                    ->whereNotExists(function ($q) use ($clubId) {
                        $q->select(DB::raw(1))
                            ->from('subscription_instance_students as sis')
                            ->join('subscription_instances as si', 'si.id', '=', 'sis.subscription_instance_id')
                            ->join('subscriptions as sub', 'sub.id', '=', 'si.subscription_id')
                            ->whereColumn('sis.student_id', 'students.id')
                            ->where('sub.club_id', $clubId)
                            ->where('si.status', 'active');
                    })
                    ->pluck('students.id');
                $count = DB::table('club_students')
                    ->where('club_id', $clubId)
                    ->whereIn('student_id', $studentIds)
                    ->update(['is_active' => false, 'updated_at' => now()]);
                return response()->json([
                    'success' => true,
                    'message' => "{$count} élève(s) sans abonnement actif ont été archivés.",
                    'data' => ['archived_count' => $count],
                ]);
            }
            return response()->json(['success' => false, 'message' => 'Filtre non supporté'], 400);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            \Log::error('Erreur bulkArchive: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Erreur lors de l\'archivage'], 500);
        }
    }

    /**
     * Récupérer les clubs de l'élève connecté
     */
    public function getMyClubs(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if ($user->role !== 'student') {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès réservé aux élèves'
                ], 403);
            }

            // Récupérer l'étudiant actif depuis le contexte
            $student = $this->getActiveStudent($request);
            if (!$student) {
                return response()->json([
                    'success' => false,
                    'message' => 'Profil élève non trouvé'
                ], 404);
            }

            // Récupérer les clubs actifs de l'étudiant actif
            $clubs = $student->clubs()
                ->wherePivot('is_active', true)
                ->select('clubs.id', 'clubs.name', 'clubs.city', 'clubs.postal_code')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $clubs
            ]);

        } catch (\Exception $e) {
            \Log::error('Erreur lors de la récupération des clubs de l\'élève: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des clubs'
            ], 500);
        }
    }

    /**
     * Ajouter des clubs à l'élève connecté
     */
    public function addClubs(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if ($user->role !== 'student') {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès réservé aux élèves'
                ], 403);
            }

            $request->validate([
                'club_ids' => ['required', 'array', 'min:1'],
                'club_ids.*' => ['integer', 'exists:clubs,id'],
            ]);

            // Récupérer l'étudiant actif depuis le contexte
            $student = $this->getActiveStudent($request);
            if (!$student) {
                return response()->json([
                    'success' => false,
                    'message' => 'Profil élève non trouvé'
                ], 404);
            }

            DB::beginTransaction();

            // Vérifier si l'étudiant a déjà un club principal
            $hasPrimaryClub = $student->club_id !== null;
            
            $addedClubs = [];
            foreach ($request->club_ids as $clubId) {
                // Vérifier si l'élève n'est pas déjà affilié à ce club
                $existing = DB::table('club_students')
                    ->where('club_id', $clubId)
                    ->where('student_id', $student->id)
                    ->first();

                if (!$existing) {
                    // Créer une nouvelle affiliation
                    DB::table('club_students')->insert([
                        'club_id' => $clubId,
                        'student_id' => $student->id,
                        'is_active' => true,
                        'joined_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $addedClubs[] = $clubId;
                    
                    // Si l'étudiant n'a pas de club principal, définir le premier ajouté comme club principal
                    if (!$hasPrimaryClub && count($addedClubs) === 1) {
                        $student->club_id = $clubId;
                        $student->save();
                        $hasPrimaryClub = true;
                    }
                } elseif (!$existing->is_active) {
                    // Réactiver l'affiliation existante
                    DB::table('club_students')
                        ->where('club_id', $clubId)
                        ->where('student_id', $student->id)
                        ->update([
                            'is_active' => true,
                            'joined_at' => now(),
                            'updated_at' => now(),
                        ]);
                    $addedClubs[] = $clubId;
                    
                    // Si l'étudiant n'a pas de club principal, définir le premier réactivé comme club principal
                    if (!$hasPrimaryClub && count($addedClubs) === 1) {
                        $student->club_id = $clubId;
                        $student->save();
                        $hasPrimaryClub = true;
                    }
                }
            }

            DB::commit();

            \Log::info('Clubs ajoutés à l\'élève', [
                'student_id' => $student->id,
                'club_ids' => $addedClubs
            ]);

            return response()->json([
                'success' => true,
                'message' => count($addedClubs) . ' club(s) ajouté(s) avec succès',
                'data' => [
                    'added_clubs' => $addedClubs
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Erreur lors de l\'ajout des clubs à l\'élève: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'ajout des clubs'
            ], 500);
        }
    }

    /**
     * Retirer un club de l'élève connecté
     */
    public function removeClub(Request $request, $clubId): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if ($user->role !== 'student') {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès réservé aux élèves'
                ], 403);
            }

            // Récupérer l'étudiant actif depuis le contexte
            $student = $this->getActiveStudent($request);
            if (!$student) {
                return response()->json([
                    'success' => false,
                    'message' => 'Profil élève non trouvé'
                ], 404);
            }

            // Vérifier que l'élève est affilié à ce club
            $clubStudent = DB::table('club_students')
                ->where('club_id', $clubId)
                ->where('student_id', $student->id)
                ->first();

            if (!$clubStudent) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous n\'êtes pas affilié à ce club'
                ], 404);
            }

            DB::beginTransaction();

            // Soft delete : désactiver l'affiliation
            DB::table('club_students')
                ->where('club_id', $clubId)
                ->where('student_id', $student->id)
                ->update([
                    'is_active' => false,
                    'updated_at' => now()
                ]);

            DB::commit();

            \Log::info('Club retiré de l\'élève', [
                'student_id' => $student->id,
                'club_id' => $clubId
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Club retiré avec succès'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Erreur lors de la suppression du club de l\'élève: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression du club'
            ], 500);
        }
    }

    /**
     * Récupérer le profil de l'étudiant connecté
     */
    public function getProfile(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            \Log::info('StudentController::getProfile - User:', [
                'user_id' => $user->id,
                'email' => $user->email,
                'role' => $user->role
            ]);
            
            // Récupérer l'étudiant actif depuis le contexte
            $student = $this->getActiveStudent($request);

            // Si l'étudiant actif n'existe pas, utiliser le compte principal
            if (!$student) {
                $student = $user->student;
                
                // Si l'utilisateur a le rôle student mais pas de profil, créer le profil automatiquement
                if (!$student && $user->role === 'student') {
                    \Log::info('StudentController::getProfile - Création automatique du profil étudiant', [
                        'user_id' => $user->id,
                        'email' => $user->email
                    ]);
                    
                    $student = $user->getOrCreateStudent();
                    
                    // Si l'étudiant a des clubs mais pas de club_id défini, définir le premier comme club principal
                    if ($student && !$student->club_id) {
                        $firstClub = DB::table('club_students')
                            ->where('student_id', $student->id)
                            ->where('is_active', true)
                            ->orderBy('joined_at', 'asc')
                            ->first();
                        
                        if ($firstClub) {
                            $student->club_id = $firstClub->club_id;
                            $student->save();
                        }
                    }
                }
            }

            if (!$student) {
                \Log::warning('StudentController::getProfile - Aucun profil étudiant trouvé', [
                    'user_id' => $user->id,
                    'email' => $user->email
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Profil étudiant introuvable'
                ], 404);
            }

            // Charger les relations nécessaires
            $student->load(['user', 'club', 'clubs', 'disciplines']);

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'first_name' => $user->first_name,
                        'last_name' => $user->last_name,
                        'email' => $user->email,
                        'phone' => $user->phone,
                        'role' => $user->role,
                        'status' => $user->status,
                        'email_verified_at' => $user->email_verified_at,
                        'created_at' => $user->created_at,
                        'updated_at' => $user->updated_at,
                    ],
                    'student' => [
                        'id' => $student->id,
                        'user_id' => $student->user_id,
                        'club_id' => $student->club_id,
                        'first_name' => $student->first_name,
                        'last_name' => $student->last_name,
                        'date_of_birth' => $student->date_of_birth,
                        'phone' => $student->phone,
                        'level' => $student->level,
                        'goals' => $student->goals,
                        'medical_info' => $student->medical_info,
                        'emergency_contacts' => $student->emergency_contacts,
                        'preferred_disciplines' => $student->preferred_disciplines,
                        'preferred_levels' => $student->preferred_levels,
                        'preferred_formats' => $student->preferred_formats,
                        'location' => $student->location,
                        'max_price' => $student->max_price,
                        'max_distance' => $student->max_distance,
                        'notifications_enabled' => $student->notifications_enabled,
                        'club' => $student->club ? [
                            'id' => $student->club->id,
                            'name' => $student->club->name,
                            'city' => $student->club->city,
                            'postal_code' => $student->club->postal_code,
                        ] : null,
                        'clubs' => $student->clubs->map(function ($club) {
                            return [
                                'id' => $club->id,
                                'name' => $club->name,
                                'city' => $club->city,
                                'postal_code' => $club->postal_code,
                                'is_active' => $club->pivot->is_active ?? false,
                                'joined_at' => $club->pivot->joined_at ?? null,
                            ];
                        }),
                        'disciplines' => $student->disciplines->map(function ($discipline) {
                            return [
                                'id' => $discipline->id,
                                'name' => $discipline->name,
                            ];
                        }),
                        'created_at' => $student->created_at,
                        'updated_at' => $student->updated_at,
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Erreur lors de la récupération du profil étudiant: ' . $e->getMessage(), [
                'user_id' => $request->user()->id ?? null,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du profil',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Met à jour le profil de l'étudiant connecté
     */
    public function updateProfile(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            if ($user->role !== 'student') {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès réservé aux élèves'
                ], 403);
            }

            // Récupérer l'étudiant actif depuis le contexte
            $student = $this->getActiveStudent($request);
            if (!$student) {
                return response()->json([
                    'success' => false,
                    'message' => 'Profil étudiant non trouvé'
                ], 404);
            }

            // Validation des champs modifiables par l'étudiant
            $validated = $request->validate([
                'first_name' => 'sometimes|string|max:255',
                'last_name' => 'sometimes|string|max:255',
                'phone' => 'sometimes|nullable|string|max:20',
                'date_of_birth' => 'sometimes|nullable|date|before:today',
                'goals' => 'sometimes|nullable|string',
                'medical_info' => 'sometimes|nullable|string',
                'emergency_contacts' => 'sometimes|nullable|array',
                'preferred_disciplines' => 'sometimes|nullable|array',
                'preferred_levels' => 'sometimes|nullable|array',
                'preferred_formats' => 'sometimes|nullable|array',
                'location' => 'sometimes|nullable|string|max:255',
                'max_price' => 'sometimes|nullable|numeric|min:0',
                'max_distance' => 'sometimes|nullable|numeric|min:0',
                'notifications_enabled' => 'sometimes|boolean',
                // Champs utilisateur modifiables
                'email' => [
                    'sometimes',
                    'email',
                    Rule::unique('users')->ignore($user->id),
                ],
            ]);

            DB::beginTransaction();

            // Mettre à jour les informations utilisateur
            $userData = [];
            if (isset($validated['first_name'])) {
                $userData['first_name'] = $validated['first_name'];
            }
            if (isset($validated['last_name'])) {
                $userData['last_name'] = $validated['last_name'];
            }
            if (isset($validated['phone'])) {
                $userData['phone'] = $validated['phone'] === '' ? null : $validated['phone'];
            }
            if (isset($validated['email'])) {
                $userData['email'] = $validated['email'] === '' ? null : $validated['email'];
            }

            if (!empty($userData)) {
                // Mettre à jour le nom complet si first_name ou last_name changent
                if (isset($userData['first_name']) || isset($userData['last_name'])) {
                    $firstName = $userData['first_name'] ?? $user->first_name;
                    $lastName = $userData['last_name'] ?? $user->last_name;
                    $userData['name'] = trim($firstName . ' ' . $lastName);
                }
                $user->update($userData);
            }

            // Mettre à jour le profil étudiant
            $studentData = [];
            $allowedFields = [
                'first_name', 'last_name', 'phone', 'date_of_birth', 'goals',
                'medical_info', 'emergency_contacts', 'preferred_disciplines',
                'preferred_levels', 'preferred_formats', 'location',
                'max_price', 'max_distance', 'notifications_enabled'
            ];

            foreach ($allowedFields as $field) {
                if (array_key_exists($field, $validated)) {
                    $value = $validated[$field];
                    // Convertir les chaînes vides en null pour les champs nullable
                    if (in_array($field, ['phone', 'goals', 'medical_info', 'location']) && $value === '') {
                        $studentData[$field] = null;
                    } else {
                        $studentData[$field] = $value;
                    }
                }
            }

            if (!empty($studentData)) {
                $student->update($studentData);
            }

            DB::commit();

            // Recharger les relations
            $student->refresh();
            $student->load(['user', 'club', 'clubs', 'disciplines']);

            \Log::info('Profil étudiant mis à jour', [
                'student_id' => $student->id,
                'user_id' => $user->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Profil mis à jour avec succès',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'first_name' => $user->first_name,
                        'last_name' => $user->last_name,
                        'email' => $user->email,
                        'phone' => $user->phone,
                    ],
                    'student' => $student
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Erreur lors de la mise à jour du profil étudiant: ' . $e->getMessage(), [
                'user_id' => $request->user()->id ?? null,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du profil',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupérer tous les comptes étudiants liés au compte actuel de l'utilisateur.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getLinkedAccounts(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user->student) {
                return response()->json([
                    'success' => false,
                    'message' => 'Profil étudiant introuvable'
                ], 404);
            }

            // Récupérer tous les comptes étudiants liés
            $linkedStudents = $user->getLinkedStudents();
            
            // Inclure le compte principal dans la liste
            $allAccounts = collect([$user->student])->merge($linkedStudents)->unique('id');
            
            // Formater les données
            $formatted = $allAccounts->map(function ($student) use ($user) {
                $isActive = session('active_student_id', $user->student->id) === $student->id;
                
                return [
                    'id' => $student->id,
                    'name' => $student->name,
                    'email' => $student->user?->email ?? null,
                    'user_id' => $student->user_id,
                    'is_active' => $isActive,
                    'is_primary' => $student->id === $user->student->id,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formatted
            ]);
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la récupération des comptes liés: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des comptes liés',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Changer de compte étudiant actif (contexte de session).
     * 
     * @param Request $request
     * @param int $studentId
     * @return JsonResponse
     */
    public function switchAccount(Request $request, $studentId): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user->student) {
                return response()->json([
                    'success' => false,
                    'message' => 'Profil étudiant introuvable'
                ], 404);
            }

            // Vérifier que l'étudiant demandé existe
            $targetStudent = Student::with('user')->findOrFail($studentId);
            
            // Vérifier que l'étudiant est bien lié au compte actuel ou est le compte principal
            $linkedStudents = $user->getLinkedStudents();
            $isLinked = $linkedStudents->contains('id', $studentId) 
                     || $user->student->id === $studentId;
            
            if (!$isLinked) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous n\'avez pas accès à ce compte étudiant'
                ], 403);
            }

            // Vérifier que l'étudiant est actif
            if ($targetStudent->user && !$targetStudent->user->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce compte étudiant est désactivé'
                ], 403);
            }

            // Stocker l'ID de l'étudiant actif dans la session
            session(['active_student_id' => $studentId]);

            return response()->json([
                'success' => true,
                'message' => 'Compte changé avec succès',
                'data' => [
                    'id' => $targetStudent->id,
                    'name' => $targetStudent->name,
                    'email' => $targetStudent->user?->email ?? null,
                    'user_id' => $targetStudent->user_id,
                ]
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Étudiant non trouvé'
            ], 404);
        } catch (\Exception $e) {
            \Log::error('Erreur lors du changement de compte: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du changement de compte',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupérer le compte étudiant actuellement actif.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getActiveAccount(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user->student) {
                return response()->json([
                    'success' => false,
                    'message' => 'Profil étudiant introuvable'
                ], 404);
            }

            // Récupérer l'ID de l'étudiant actif depuis la session
            $activeStudentId = session('active_student_id', $user->student->id);
            $activeStudent = Student::with('user')->findOrFail($activeStudentId);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $activeStudent->id,
                    'name' => $activeStudent->name,
                    'email' => $activeStudent->user?->email ?? null,
                    'user_id' => $activeStudent->user_id,
                    'is_primary' => $activeStudent->id === $user->student->id,
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la récupération du compte actif: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du compte actif',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Vérifier qu'un élève appartient au club de l'utilisateur connecté (rôle club).
     */
    protected function studentBelongsToClub(int $studentId, $club): bool
    {
        return DB::table('club_students')
            ->where('club_id', $club->id)
            ->where('student_id', $studentId)
            ->exists();
    }

    /**
     * GET /api/club/students/{studentId}/linked — Liste des élèves liés (fiche élève, côté club).
     */
    public function getLinkedForClub(Request $request, $studentId): JsonResponse
    {
        try {
            $user = Auth::user();
            $club = $user->getFirstClub();
            if (!$club) {
                return response()->json([
                    'success' => false,
                    'message' => 'Club non trouvé'
                ], 404);
            }

            if (!$this->studentBelongsToClub((int) $studentId, $club)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cet élève n\'appartient pas à votre club'
                ], 403);
            }

            $student = Student::with(['linkedStudents.user', 'linkedFromStudents.user'])->findOrFail($studentId);
            $linkedStudents = $student->getAllLinkedStudents();

            // Isolation multi-tenant : ne retourner que les élèves liés qui appartiennent au même club
            $linkedStudents = $linkedStudents->filter(function ($linkedStudent) use ($club) {
                return $this->studentBelongsToClub((int) $linkedStudent->id, $club);
            })->values();

            $formatted = $linkedStudents->map(function ($linkedStudent) {
                return [
                    'id' => $linkedStudent->id,
                    'name' => $linkedStudent->name,
                    'email' => $linkedStudent->user?->email ?? null,
                    'user_id' => $linkedStudent->user_id,
                    'relationship_type' => $linkedStudent->pivot->relationship_type ?? null,
                    'linked_at' => $linkedStudent->pivot->created_at ?? null,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formatted
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Élève non trouvé'
            ], 404);
        } catch (\Exception $e) {
            \Log::error('Erreur getLinkedForClub: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des élèves liés',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * POST /api/club/students/{studentId}/link — Lier un élève (fiche élève, côté club).
     */
    public function linkForClub(Request $request, $studentId): JsonResponse
    {
        try {
            $user = Auth::user();
            $club = $user->getFirstClub();
            if (!$club) {
                return response()->json([
                    'success' => false,
                    'message' => 'Club non trouvé'
                ], 404);
            }

            $validated = $request->validate([
                'linked_student_id' => 'required|integer|exists:students,id',
                'relationship_type' => 'nullable|string|max:50',
            ]);

            $primaryStudent = Student::findOrFail($studentId);
            $linkedStudentId = (int) $validated['linked_student_id'];
            $linkedStudent = Student::findOrFail($linkedStudentId);

            if (!$this->studentBelongsToClub($primaryStudent->id, $club) || !$this->studentBelongsToClub($linkedStudent->id, $club)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Les deux élèves doivent appartenir à votre club'
                ], 403);
            }

            if ($primaryStudent->id === $linkedStudent->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Un élève ne peut pas être lié à lui-même'
                ], 422);
            }

            if (!$primaryStudent->user_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Seul un élève avec compte (email) peut être le compte principal'
                ], 422);
            }

            $existingLink = DB::table('student_family_links')
                ->where(function ($q) use ($primaryStudent, $linkedStudentId) {
                    $q->where('primary_student_id', $primaryStudent->id)->where('linked_student_id', $linkedStudentId);
                })
                ->orWhere(function ($q) use ($primaryStudent, $linkedStudentId) {
                    $q->where('primary_student_id', $linkedStudentId)->where('linked_student_id', $primaryStudent->id);
                })
                ->first();

            if ($existingLink) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ces deux élèves sont déjà liés'
                ], 422);
            }

            $relationshipType = $validated['relationship_type'] ?? null;

            if ($linkedStudent->user_id) {
                DB::table('student_family_links')->insert([
                    [
                        'primary_student_id' => $primaryStudent->id,
                        'linked_student_id' => $linkedStudentId,
                        'relationship_type' => $relationshipType,
                        'created_by' => $user->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                    [
                        'primary_student_id' => $linkedStudentId,
                        'linked_student_id' => $primaryStudent->id,
                        'relationship_type' => $relationshipType,
                        'created_by' => $user->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                ]);
            } else {
                DB::table('student_family_links')->insert([
                    'primary_student_id' => $primaryStudent->id,
                    'linked_student_id' => $linkedStudentId,
                    'relationship_type' => $relationshipType,
                    'created_by' => $user->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Les élèves ont été liés avec succès'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Élève non trouvé'
            ], 404);
        } catch (\Exception $e) {
            \Log::error('Erreur linkForClub: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la liaison',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * DELETE /api/club/students/{studentId}/unlink/{linkedStudentId} — Délier (fiche élève, côté club).
     */
    public function unlinkForClub(Request $request, $studentId, $linkedStudentId): JsonResponse
    {
        try {
            $user = Auth::user();
            $club = $user->getFirstClub();
            if (!$club) {
                return response()->json([
                    'success' => false,
                    'message' => 'Club non trouvé'
                ], 404);
            }

            $primaryStudent = Student::findOrFail($studentId);
            $linkedStudent = Student::findOrFail($linkedStudentId);

            if (!$this->studentBelongsToClub($primaryStudent->id, $club) || !$this->studentBelongsToClub($linkedStudent->id, $club)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Les deux élèves doivent appartenir à votre club'
                ], 403);
            }

            DB::table('student_family_links')
                ->where(function ($q) use ($primaryStudent, $linkedStudentId) {
                    $q->where('primary_student_id', $primaryStudent->id)->where('linked_student_id', $linkedStudentId);
                })
                ->orWhere(function ($q) use ($primaryStudent, $linkedStudentId) {
                    $q->where('primary_student_id', $linkedStudentId)->where('linked_student_id', $primaryStudent->id);
                })
                ->delete();

            return response()->json([
                'success' => true,
                'message' => 'Les élèves ont été déliés avec succès'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Élève non trouvé'
            ], 404);
        } catch (\Exception $e) {
            \Log::error('Erreur unlinkForClub: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression du lien',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/club/students/available-for-linking — Élèves du club disponibles pour liaison (avec ou sans email).
     */
    public function getAvailableForLinkingForClub(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $club = $user->getFirstClub();
            if (!$club) {
                return response()->json([
                    'success' => false,
                    'message' => 'Club non trouvé'
                ], 404);
            }

            $excludeStudentId = $request->input('exclude_student_id');
            $studentIdsInClub = DB::table('club_students')
                ->where('club_id', $club->id)
                ->pluck('student_id')
                ->toArray();

            $query = Student::with('user')->whereIn('id', $studentIdsInClub);

            if ($excludeStudentId) {
                $excludeStudent = Student::find($excludeStudentId);
                if ($excludeStudent) {
                    $linkedIds = $excludeStudent->getAllLinkedStudents()->pluck('id')->toArray();
                    $linkedIds[] = (int) $excludeStudentId;
                    $query->whereNotIn('id', $linkedIds);
                } else {
                    $query->where('id', '!=', $excludeStudentId);
                }
            }

            $students = $query->get();
            $formatted = $students->map(function ($student) {
                return [
                    'id' => $student->id,
                    'name' => $student->name,
                    'email' => $student->user?->email ?? null,
                    'user_id' => $student->user_id,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formatted
            ]);
        } catch (\Exception $e) {
            \Log::error('Erreur getAvailableForLinkingForClub: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des élèves disponibles',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}