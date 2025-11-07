<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\SubscriptionTemplate;
use App\Models\SubscriptionInstance;
use App\Models\SubscriptionStudent;
use App\Models\Discipline;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class SubscriptionController extends Controller
{
    /**
     * Liste tous les abonnements du club
     */
    public function index(Request $request): JsonResponse
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

            // Vérifier si les tables nécessaires existent
            if (!Schema::hasTable('subscriptions')) {
                Log::warning('Table subscriptions n\'existe pas. Migrations non exécutées.');
                return response()->json([
                    'success' => true,
                    'data' => []
                ]);
            }

            // Si pas d'abonnements, retourner un tableau vide directement
            // Utiliser scopeForClub qui gère automatiquement le cas où club_id n'existe pas
            $subscriptionCount = Subscription::forClub($club->id)->count();
            if ($subscriptionCount === 0) {
                return response()->json([
                    'success' => true,
                    'data' => []
                ]);
            }

            $subscriptions = Subscription::forClub($club->id)
                ->with([
                    'template' => function ($query) {
                        $query->with('courseTypes');
                    },
                    'instances' => function ($query) {
                        $query->with(['students' => function ($q) {
                            $q->with('user');
                        }]);
                    }
                ])
                ->orderBy('created_at', 'desc')
                ->get();
            
            // Recalculer automatiquement et lier les cours manquants pour chaque instance active
            foreach ($subscriptions as $subscription) {
                if ($subscription->instances && $subscription->instances->count() > 0) {
                    foreach ($subscription->instances as $instance) {
                        // Ne traiter que les instances actives
                        if ($instance->status !== 'active') {
                            continue;
                        }
                        
                        // 🔧 Trouver et lier les cours manquants
                        $studentIds = $instance->students->pluck('id')->toArray();
                        
                        if (!empty($studentIds)) {
                            // Récupérer les types de cours acceptés par cet abonnement
                            $courseTypeIds = $subscription->courseTypes->pluck('id')->toArray();
                            
                            if (!empty($courseTypeIds)) {
                                // Trouver les cours des élèves qui ne sont pas encore liés à un abonnement
                                $unlinkedLessons = \App\Models\Lesson::whereIn('student_id', $studentIds)
                                    ->whereIn('course_type_id', $courseTypeIds)
                                    ->whereNotIn('status', ['cancelled'])
                                    ->whereDoesntHave('subscriptionInstances')
                                    ->get();
                                
                                foreach ($unlinkedLessons as $lesson) {
                                    try {
                                        // Vérifier s'il reste des cours disponibles
                                        $totalLessons = $instance->subscription->total_available_lessons;
                                        $lessonsUsed = $instance->lessons_used;
                                        
                                        if ($lessonsUsed < $totalLessons) {
                                            $instance->consumeLesson($lesson);
                                            Log::info("🔗 Cours {$lesson->id} lié automatiquement à l'abonnement {$instance->id} au chargement");
                                        }
                                    } catch (\Exception $e) {
                                        Log::warning("Impossible de lier le cours {$lesson->id} à l'abonnement {$instance->id}: " . $e->getMessage());
                                    }
                                }
                            }
                        }
                        
                        // Recalculer après avoir lié les cours
                        $instance->recalculateLessonsUsed();
                    }
                }
            }
            
            // Ajouter l'alias subscriptionStudents pour compatibilité frontend
            foreach ($subscriptions as $subscription) {
                $subscription->subscription_students = $subscription->instances ?? collect([]);
            }

            return response()->json([
                'success' => true,
                'data' => $subscriptions
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des abonnements: ' . $e->getMessage(), [
                'club_id' => $club->id ?? null,
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Retourner un tableau vide au lieu d'une erreur 500
            // pour ne pas casser l'interface utilisateur
            return response()->json([
                'success' => true,
                'data' => []
            ]);
        }
    }

    /**
     * Créer un nouvel abonnement depuis un modèle
     */
    public function store(Request $request): JsonResponse
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

            $validated = $request->validate([
                'subscription_template_id' => 'required|exists:subscription_templates,id',
                'student_ids' => 'required|array|min:1',
                'student_ids.*' => 'exists:students,id',
                'started_at' => 'nullable|date|after_or_equal:today',
                'expires_at' => 'nullable|date'
            ]);

            // Vérifier que le template appartient au club
            $template = SubscriptionTemplate::where('club_id', $club->id)
                ->where('is_active', true)
                ->findOrFail($validated['subscription_template_id']);

            DB::beginTransaction();

            // Créer l'abonnement (le numéro sera généré automatiquement)
            $subscription = Subscription::createSafe([
                'club_id' => $club->id,
                'subscription_template_id' => $template->id,
            ]);

            // Date de début (aujourd'hui par défaut)
            $startedAt = $validated['started_at'] ? Carbon::parse($validated['started_at']) : Carbon::now();

            // Créer l'instance d'abonnement
            $subscriptionInstance = SubscriptionInstance::create([
                'subscription_id' => $subscription->id,
                'lessons_used' => 0,
                'started_at' => $startedAt,
                'expires_at' => $validated['expires_at'] ? Carbon::parse($validated['expires_at']) : null,
                'status' => 'active'
            ]);

            // Calculer expires_at si non fourni
            if (!$subscriptionInstance->expires_at) {
                $subscriptionInstance->calculateExpiresAt();
                $subscriptionInstance->save();
            }

            // Attacher les élèves
            $subscriptionInstance->students()->attach($validated['student_ids']);

            DB::commit();

            $subscription->load([
                'template.courseTypes',
                'instances' => function ($query) {
                    $query->with(['students' => function ($q) {
                        $q->with('user');
                    }]);
                }
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Abonnement créé avec succès (Numéro: ' . $subscription->subscription_number . ')',
                'data' => $subscription
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la création de l\'abonnement: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de l\'abonnement'
            ], 500);
        }
    }

    /**
     * Afficher un abonnement spécifique
     */
    public function show($id): JsonResponse
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

            $subscription = Subscription::forClub($club->id)
                ->with([
                    'template.courseTypes',
                    'instances' => function ($query) {
                        $query->with([
                            'students' => function ($q) {
                                $q->with('user');
                            },
                            'lessons' => function ($q) {
                                $q->with(['teacher.user', 'courseType', 'location'])
                                  ->orderBy('start_time', 'desc');
                            }
                        ]);
                    }
                ])
                ->findOrFail($id);
            
            // Calculer le nombre réel de cours pour chaque instance
            foreach ($subscription->instances as $instance) {
                // Recalculer automatiquement lessons_used
                $instance->recalculateLessonsUsed();
            }

            return response()->json([
                'success' => true,
                'data' => $subscription
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération de l\'abonnement: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Abonnement non trouvé'
            ], 404);
        }
    }


    /**
     * Attribuer un abonnement à un ou plusieurs élèves
     */
    public function assignToStudent(Request $request): JsonResponse
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

            $validated = $request->validate([
                'subscription_template_id' => 'required|exists:subscription_templates,id',
                'student_ids' => 'required|array|min:1',
                'student_ids.*' => 'exists:students,id',
                'started_at' => 'nullable|date|after_or_equal:today',
                'expires_at' => 'nullable|date',
                'lessons_used' => 'nullable|integer|min:0'
            ]);

            // Vérifier que le template appartient au club et est actif
            $template = SubscriptionTemplate::where('club_id', $club->id)
                ->where('is_active', true)
                ->findOrFail($validated['subscription_template_id']);

            // 🔒 VALIDATION : Vérifier qu'aucun élève n'a déjà une instance active pour ce type d'abonnement
            foreach ($validated['student_ids'] as $studentId) {
                $existingActiveInstance = SubscriptionInstance::whereHas('subscription', function ($query) use ($template, $club) {
                        $query->where('subscription_template_id', $template->id);
                        if (Subscription::hasClubIdColumn()) {
                            $query->where('club_id', $club->id);
                        }
                    })
                    ->whereHas('students', function ($query) use ($studentId) {
                        $query->where('students.id', $studentId);
                    })
                    ->where('status', 'active')
                    ->first();

                if ($existingActiveInstance) {
                    $student = \App\Models\Student::with('user')->find($studentId);
                    $studentName = $student->user->name ?? 'Élève #' . $studentId;
                    
                    return response()->json([
                        'success' => false,
                        'message' => "{$studentName} a déjà un abonnement actif de type '{$template->model_number}'. Veuillez d'abord clôturer l'abonnement existant avant d'en créer un nouveau."
                    ], 422);
                }
            }

            // Créer un nouvel abonnement depuis le template
            // Utiliser createSafe pour gérer automatiquement club_id
            $subscription = Subscription::createSafe([
                'club_id' => $club->id,
                'subscription_template_id' => $template->id,
            ]);

            DB::beginTransaction();

            // Date de début (aujourd'hui par défaut)
            $startedAt = $validated['started_at'] ? Carbon::parse($validated['started_at']) : Carbon::now();

            // Nombre de cours déjà utilisés (par défaut 0)
            $lessonsUsed = isset($validated['lessons_used']) ? (int) $validated['lessons_used'] : 0;
            
            // Vérifier que lessons_used ne dépasse pas le total disponible
            $totalAvailable = $template->total_lessons + $template->free_lessons;
            if ($lessonsUsed > $totalAvailable) {
                return response()->json([
                    'success' => false,
                    'message' => "Le nombre de cours utilisés ({$lessonsUsed}) ne peut pas dépasser le total disponible ({$totalAvailable})"
                ], 422);
            }

            // Créer une instance d'abonnement
            $subscriptionInstance = SubscriptionInstance::create([
                'subscription_id' => $subscription->id,
                'lessons_used' => $lessonsUsed,
                'started_at' => $startedAt,
                'expires_at' => $validated['expires_at'] ? Carbon::parse($validated['expires_at']) : null,
                'status' => 'active'
            ]);
            
            // Calculer expires_at si non fourni
            if (!$subscriptionInstance->expires_at) {
                $subscriptionInstance->calculateExpiresAt();
                $subscriptionInstance->save();
            }

            // Attacher les élèves à cette instance
            $subscriptionInstance->students()->attach($validated['student_ids']);

            DB::commit();

            // Charger les relations en gérant le cas où students.user peut être null
            $subscriptionInstance->load([
                'subscription.template.courseTypes',
                'students' => function ($query) {
                    $query->with('user');
                }
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Abonnement créé avec succès (Numéro: ' . $subscription->subscription_number . ')',
                'data' => [
                    'subscription' => $subscription,
                    'instance' => $subscriptionInstance
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de l\'attribution de l\'abonnement: ' . $e->getMessage(), [
                'student_ids' => $validated['student_ids'] ?? null,
                'template_id' => $validated['subscription_template_id'] ?? null,
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'attribution de l\'abonnement: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Liste des abonnements actifs d'un élève
     */
    public function studentSubscriptions($studentId): JsonResponse
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

            // Récupérer les instances d'abonnements via la table pivot
            $subscriptionInstances = SubscriptionInstance::whereHas('students', function ($query) use ($studentId) {
                    $query->where('students.id', $studentId);
                })
                ->whereHas('subscription', function ($query) use ($club) {
                    // Utiliser le scope forClub pour gérer le cas où club_id n'existe pas
                    if (Subscription::hasClubIdColumn()) {
                        $query->where('club_id', $club->id);
                    } else {
                        // Si club_id n'existe pas, filtrer via template
                        $query->whereHas('template', function ($q) use ($club) {
                            $q->where('club_id', $club->id);
                        });
                    }
                })
                ->with([
                    'subscription.template.courseTypes',
                    'subscription.club',
                    'students' => function ($query) {
                        $query->with('user');
                    }
                ])
                ->orderBy('status', 'asc')
                ->orderBy('created_at', 'desc')
                ->get();

            // Mettre à jour les statuts si nécessaire
            foreach ($subscriptionInstances as $sub) {
                try {
                    // S'assurer que la relation subscription est chargée
                    if (!$sub->relationLoaded('subscription')) {
                        $sub->load('subscription.template');
                    }
                    $sub->checkAndUpdateStatus();
                } catch (\Exception $e) {
                    Log::warning('Erreur lors de la mise à jour du statut de l\'instance ' . $sub->id . ': ' . $e->getMessage());
                    // Continuer même en cas d'erreur de statut
                }
            }

            return response()->json([
                'success' => true,
                'data' => $subscriptionInstances
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des abonnements de l\'élève: ' . $e->getMessage(), [
                'student_id' => $studentId,
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des abonnements',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Renouveler un abonnement pour un élève (par le club)
     */
    public function renew(Request $request, $instanceId): JsonResponse
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

            $validated = $request->validate([
                'started_at' => 'nullable|date|after_or_equal:today',
            ]);

            // Récupérer l'instance d'abonnement existante
            $existingInstance = SubscriptionInstance::whereHas('subscription', function ($query) use ($club) {
                    // Utiliser le scope forClub pour gérer le cas où club_id n'existe pas
                    if (Subscription::hasClubIdColumn()) {
                        $query->where('club_id', $club->id);
                    } else {
                        // Si club_id n'existe pas, filtrer via template
                        $query->whereHas('template', function ($q) use ($club) {
                            $q->where('club_id', $club->id);
                        });
                    }
                })
                ->with(['subscription.template', 'students'])
                ->findOrFail($instanceId);

            // Vérifier que l'abonnement peut être renouvelé
            if ($existingInstance->status === 'cancelled') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cet abonnement a été annulé et ne peut pas être renouvelé'
                ], 422);
            }

            // Vérifier que le template d'abonnement est toujours actif
            $template = $existingInstance->subscription->template;
            if (!$template || !$template->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce type d\'abonnement n\'est plus disponible'
                ], 422);
            }

            DB::beginTransaction();

            // Créer un nouvel abonnement depuis le même template (pour le renouvellement)
            $newSubscription = Subscription::createSafe([
                'club_id' => $club->id,
                'subscription_template_id' => $template->id,
            ]);

            // Date de début (aujourd'hui par défaut, ou celle fournie)
            $startedAt = $validated['started_at'] ? Carbon::parse($validated['started_at']) : Carbon::now();

            // Créer une nouvelle instance d'abonnement (renouvellement)
            $newInstance = SubscriptionInstance::create([
                'subscription_id' => $newSubscription->id,
                'lessons_used' => 0,
                'started_at' => $startedAt,
                'expires_at' => null, // Sera calculé automatiquement
                'status' => 'active'
            ]);

            // Calculer la date d'expiration
            $newInstance->calculateExpiresAt();
            $newInstance->save();

            // Attacher les mêmes élèves (pour les abonnements familiaux, on garde la même structure)
            $studentIds = $existingInstance->students->pluck('id')->toArray();
            $newInstance->students()->attach($studentIds);

            DB::commit();

            $newInstance->load([
                'subscription.club',
                'subscription.template.courseTypes',
                'students' => function ($query) {
                    $query->with('user');
                }
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Abonnement renouvelé avec succès',
                'data' => $newInstance
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors du renouvellement de l\'abonnement par le club: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du renouvellement de l\'abonnement'
            ], 500);
        }
    }

    /**
     * Recalculer le nombre de cours restants pour tous les abonnements actifs
     * Utile pour corriger les compteurs en se basant sur l'historique réel
     */
    public function recalculateAll(Request $request): JsonResponse
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

            // Récupérer tous les abonnements actifs du club
            $subscriptions = Subscription::forClub($club->id)
                ->with('instances.students')
                ->get();

            $stats = [
                'total_checked' => 0,
                'total_updated' => 0,
                'lessons_linked' => 0,
                'details' => []
            ];

            foreach ($subscriptions as $subscription) {
                foreach ($subscription->instances as $instance) {
                    // Ne recalculer que les instances actives
                    if ($instance->status !== 'active') {
                        continue;
                    }

                    $stats['total_checked']++;
                    
                    // 🔧 NOUVELLE FONCTIONNALITÉ : Trouver et lier les cours manquants
                    // Récupérer les élèves de cet abonnement
                    $studentIds = $instance->students->pluck('id')->toArray();
                    
                    if (!empty($studentIds)) {
                        // Récupérer les types de cours acceptés par cet abonnement
                        $courseTypeIds = $subscription->courseTypes->pluck('id')->toArray();
                        
                        if (!empty($courseTypeIds)) {
                            // Trouver les cours des élèves qui ne sont pas encore liés à un abonnement
                            // et qui correspondent aux types de cours de cet abonnement
                            $unlinkedLessons = \App\Models\Lesson::whereIn('student_id', $studentIds)
                                ->whereIn('course_type_id', $courseTypeIds)
                                ->whereNotIn('status', ['cancelled'])
                                ->whereDoesntHave('subscriptionInstances') // Cours non encore liés à un abonnement
                                ->get();
                            
                            foreach ($unlinkedLessons as $lesson) {
                                try {
                                    // Vérifier s'il reste des cours disponibles
                                    $totalLessons = $instance->subscription->total_available_lessons;
                                    $lessonsUsed = $instance->lessons_used;
                                    
                                    if ($lessonsUsed < $totalLessons) {
                                        $instance->consumeLesson($lesson);
                                        $stats['lessons_linked']++;
                                        
                                        Log::info("🔗 Cours {$lesson->id} lié automatiquement à l'abonnement {$instance->id} lors du recalcul");
                                    }
                                } catch (\Exception $e) {
                                    Log::warning("Impossible de lier le cours {$lesson->id} à l'abonnement {$instance->id}: " . $e->getMessage());
                                }
                            }
                        }
                    }
                    
                    // Sauvegarder l'ancienne valeur
                    $oldLessonsUsed = $instance->lessons_used;
                    
                    // Recalculer après avoir lié les cours
                    $instance->recalculateLessonsUsed();
                    
                    // Si la valeur a changé, compter comme mise à jour
                    if ($oldLessonsUsed != $instance->lessons_used) {
                        $stats['total_updated']++;
                        $stats['details'][] = [
                            'subscription_number' => $subscription->subscription_number,
                            'instance_id' => $instance->id,
                            'old_lessons_used' => $oldLessonsUsed,
                            'new_lessons_used' => $instance->lessons_used,
                            'remaining_lessons' => $instance->remaining_lessons
                        ];
                    }
                }
            }

            Log::info('Recalcul de tous les abonnements', [
                'club_id' => $club->id,
                'total_checked' => $stats['total_checked'],
                'total_updated' => $stats['total_updated'],
                'lessons_linked' => $stats['lessons_linked']
            ]);

            $message = "Recalcul terminé : {$stats['total_updated']} abonnement(s) mis à jour sur {$stats['total_checked']} vérifié(s)";
            if ($stats['lessons_linked'] > 0) {
                $message .= " - {$stats['lessons_linked']} cours lié(s) automatiquement";
            } else {
                $message .= " - Les compteurs sont déjà corrects";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors du recalcul des abonnements: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du recalcul: ' . $e->getMessage()
            ], 500);
        }
    }

}

