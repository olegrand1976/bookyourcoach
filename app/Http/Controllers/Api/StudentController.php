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

            $student = $user->student;
            if (!$student) {
                return response()->json([
                    'success' => false,
                    'message' => 'Profil élève non trouvé'
                ], 404);
            }

            // Récupérer les clubs actifs de l'élève
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

            $student = $user->student;
            if (!$student) {
                return response()->json([
                    'success' => false,
                    'message' => 'Profil élève non trouvé'
                ], 404);
            }

            DB::beginTransaction();

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

            $student = $user->student;
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
}
