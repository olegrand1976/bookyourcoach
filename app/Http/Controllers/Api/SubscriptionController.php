<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\SubscriptionTemplate;
use App\Models\SubscriptionInstance;
use App\Models\SubscriptionStudent;
use App\Models\Discipline;
use App\Models\AuditLog;
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
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated',
                    'error' => 'Missing token'
                ], 401);
            }
            
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
            try {
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
            } catch (\Exception $e) {
                Log::error('Erreur lors de la récupération des abonnements (query): ' . $e->getMessage(), [
                    'club_id' => $club->id,
                    'user_id' => Auth::id(),
                    'trace' => $e->getTraceAsString()
                ]);
                // Retourner un tableau vide en cas d'erreur
                return response()->json([
                    'success' => true,
                    'data' => []
                ]);
            }
            
            // 🔧 Lier automatiquement les cours non liés aux abonnements actifs
            // Utiliser l'ordre chronologique : les abonnements les plus anciens sont utilisés en premier
            $allStudentIds = [];
            foreach ($subscriptions as $subscription) {
                if ($subscription->instances && $subscription->instances->count() > 0) {
                    foreach ($subscription->instances as $instance) {
                        if ($instance->status === 'active') {
                            $allStudentIds = array_merge($allStudentIds, $instance->students->pluck('id')->toArray());
                        }
                    }
                }
            }
            $allStudentIds = array_unique($allStudentIds);

            // Pour chaque élève, trouver ses cours non liés et les lier au bon abonnement
            foreach ($allStudentIds as $studentId) {
                // Récupérer tous les cours non liés de cet élève
                $unlinkedLessons = \App\Models\Lesson::where(function ($query) use ($studentId) {
                        $query->where('student_id', $studentId)
                              ->orWhereHas('students', function ($q) use ($studentId) {
                                  $q->where('students.id', $studentId);
                              });
                    })
                    ->whereNotIn('status', ['cancelled'])
                    ->whereDoesntHave('subscriptionInstances')
                    ->get();

                foreach ($unlinkedLessons as $lesson) {
                    if (!$lesson->course_type_id) {
                        continue;
                    }

                    try {
                        // Trouver le bon abonnement actif pour cet élève et ce type de cours
                        // (le plus ancien qui a encore des cours disponibles)
                        $instance = SubscriptionInstance::findActiveSubscriptionForLesson(
                            $studentId,
                            $lesson->course_type_id,
                            $club->id
                        );

                        if ($instance) {
                            $instance->consumeLesson($lesson);
                            Log::info("🔗 Cours {$lesson->id} lié automatiquement à l'abonnement {$instance->id} (le plus ancien disponible)", [
                                'lesson_id' => $lesson->id,
                                'student_id' => $studentId,
                                'course_type_id' => $lesson->course_type_id,
                                'subscription_instance_id' => $instance->id,
                                'subscription_created_at' => $instance->created_at
                            ]);
                        }
                    } catch (\Exception $e) {
                        Log::warning("Impossible de lier le cours {$lesson->id} à un abonnement: " . $e->getMessage(), [
                            'lesson_id' => $lesson->id,
                            'student_id' => $studentId,
                            'course_type_id' => $lesson->course_type_id
                        ]);
                    }
                }
            }

            // ⚠️ IMPORTANT : Recalculer lessons_used pour ne compter que les cours passés
            // Cela garantit que seuls les cours réellement passés sont comptabilisés
            // Les valeurs manuelles sont préservées si elles sont supérieures au nombre de cours passés
            foreach ($subscriptions as $subscription) {
                if ($subscription->instances && $subscription->instances->count() > 0) {
                    foreach ($subscription->instances as $instance) {
                        try {
                            // Recalculer lessons_used pour ne compter que les cours passés
                            $instance->recalculateLessonsUsed();
                            // Mettre à jour le statut (expired si expires_at dépassée, completed si 100% utilisé)
                            $instance->checkAndUpdateStatus();
                        } catch (\Exception $e) {
                            Log::warning('Erreur lors du recalcul pour l\'instance: ' . $e->getMessage(), [
                                'instance_id' => $instance->id ?? null
                            ]);
                        }
                    }
                }
                
                // Ajouter l'alias subscriptionStudents pour compatibilité frontend
                try {
                    $subscription->subscription_students = $subscription->instances ?? collect([]);
                } catch (\Exception $e) {
                    Log::warning('Erreur lors de l\'ajout de subscription_students: ' . $e->getMessage());
                    $subscription->subscription_students = collect([]);
                }
            }

            // Sérialiser manuellement pour éviter les problèmes avec les accesseurs
            try {
                $serializedData = $subscriptions->map(function ($subscription) {
                    try {
                        return $subscription->toArray();
                    } catch (\Exception $e) {
                        Log::warning('Erreur lors de la sérialisation d\'un abonnement: ' . $e->getMessage(), [
                            'subscription_id' => $subscription->id ?? null
                        ]);
                        // Retourner seulement les données de base en cas d'erreur
                        return [
                            'id' => $subscription->id ?? null,
                            'subscription_number' => $subscription->subscription_number ?? null,
                            'subscription_template_id' => $subscription->subscription_template_id ?? null,
                            'created_at' => $subscription->created_at ?? null,
                            'updated_at' => $subscription->updated_at ?? null,
                        ];
                    }
                });
                
                return response()->json([
                    'success' => true,
                    'data' => $serializedData
                ]);
            } catch (\Exception $e) {
                Log::error('Erreur lors de la sérialisation des abonnements: ' . $e->getMessage(), [
                    'trace' => $e->getTraceAsString()
                ]);
                // Retourner un tableau vide en cas d'erreur de sérialisation
                return response()->json([
                    'success' => true,
                    'data' => []
                ]);
            }

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
     * Supporte deux modes :
     * 1. Création directe avec name, total_lessons, price (legacy)
     * 2. Création depuis un template avec subscription_template_id (nouveau)
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated',
                    'error' => 'Missing token'
                ], 401);
            }
            
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

            // Mode 1: Création directe (legacy) - si aucun template_id n'est fourni
            if (!$request->has('subscription_template_id')) {
                $validated = $request->validate([
                    'name' => 'required|string|max:255',
                    'total_lessons' => 'required|integer|min:1',
                    'free_lessons' => 'nullable|integer|min:0',
                    'price' => 'required|numeric|min:0',
                    'description' => 'nullable|string',
                    'is_active' => 'nullable|boolean',
                    'course_type_ids' => 'required|array|min:1',
                    'course_type_ids.*' => 'exists:course_types,id'
                ]);

                DB::beginTransaction();

                // Vérifier quelles colonnes existent dans la table
                $existingColumns = Schema::getColumnListing('subscriptions');
                
                // Créer l'abonnement directement avec les données fournies
                $subscriptionData = [
                    'club_id' => $club->id,
                ];
                
                // Ajouter seulement les colonnes qui existent
                foreach (['name', 'total_lessons', 'free_lessons', 'price', 'description', 'is_active'] as $col) {
                    if (in_array($col, $existingColumns) && isset($validated[$col])) {
                        $subscriptionData[$col] = $validated[$col];
                    }
                }
                
                // Utiliser DB::table pour insérer directement et éviter les problèmes avec $fillable
                $subscriptionId = DB::table('subscriptions')->insertGetId(array_merge($subscriptionData, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
                
                $subscription = Subscription::find($subscriptionId);
                
                // Attacher les types de cours si fournis
                if (!empty($validated['course_type_ids']) && Schema::hasTable('subscription_course_types')) {
                    foreach ($validated['course_type_ids'] as $courseTypeId) {
                        // Récupérer la discipline_id depuis le course_type
                        $courseType = \App\Models\CourseType::find($courseTypeId);
                        if ($courseType && $courseType->discipline_id) {
                            DB::table('subscription_course_types')->insert([
                                'subscription_id' => $subscriptionId,
                                'discipline_id' => $courseType->discipline_id,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }
                }
                
                DB::commit();
                
                $subscription->load('template');
                
                return response()->json([
                    'success' => true,
                    'message' => 'Abonnement créé avec succès',
                    'data' => $subscription
                ], 201);
            }

            // Mode 2: Création depuis un template (nouveau)
            $validated = $request->validate([
                'subscription_template_id' => 'required|exists:subscription_templates,id',
                'student_ids' => 'required|array|min:1',
                'student_ids.*' => 'exists:students,id',
                'started_at' => 'nullable|date',
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
                'lessons_used' => $validated['lessons_used'] ?? 0,
                'started_at' => $startedAt,
                'expires_at' => $validated['expires_at'] ? Carbon::parse($validated['expires_at']) : null,
                'status' => 'active',
                'est_legacy' => $validated['est_legacy'] ?? false,
                'date_paiement' => $validated['date_paiement'] ? Carbon::parse($validated['date_paiement']) : null,
                'montant' => $validated['montant'] ?? null,
            ]);

            // Calculer expires_at si non fourni
            if (!$subscriptionInstance->expires_at) {
                $subscriptionInstance->calculateExpiresAt();
                $subscriptionInstance->save();
            }

            // Attacher les élèves
            $subscriptionInstance->students()->attach($validated['student_ids']);

            // Enregistrer la création dans l'historique
            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'subscription_instance_created',
                'model_type' => SubscriptionInstance::class,
                'model_id' => $subscriptionInstance->id,
                'data' => [
                    'subscription_id' => $subscription->id,
                    'started_at' => $subscriptionInstance->started_at,
                    'expires_at' => $subscriptionInstance->expires_at,
                    'status' => $subscriptionInstance->status,
                    'est_legacy' => $subscriptionInstance->est_legacy,
                    'student_ids' => $validated['student_ids'],
                ],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

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
     * Mettre à jour un abonnement
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated',
                    'error' => 'Missing token'
                ], 401);
            }
            
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

            // Récupérer l'abonnement
            $subscription = Subscription::forClub($club->id)->findOrFail($id);

            // Validation
            $validated = $request->validate([
                'name' => 'nullable|string|max:255',
                'total_lessons' => 'nullable|integer|min:1',
                'free_lessons' => 'nullable|integer|min:0',
                'price' => 'nullable|numeric|min:0',
                'description' => 'nullable|string',
                'is_active' => 'nullable|boolean',
                'course_type_ids' => 'nullable|array',
                'course_type_ids.*' => 'exists:course_types,id'
            ]);

            DB::beginTransaction();

            // Vérifier quelles colonnes existent dans la table
            $existingColumns = Schema::getColumnListing('subscriptions');
            
            // Préparer les données à mettre à jour
            $updateData = [];
            
            // Ajouter seulement les colonnes qui existent et qui sont fournies
            foreach (['name', 'total_lessons', 'free_lessons', 'price', 'description', 'is_active'] as $col) {
                if (in_array($col, $existingColumns) && isset($validated[$col])) {
                    $updateData[$col] = $validated[$col];
                }
            }
            
            // Mettre à jour avec DB::table pour éviter les problèmes avec $fillable
            if (!empty($updateData)) {
                $updateData['updated_at'] = now();
                DB::table('subscriptions')
                    ->where('id', $subscription->id)
                    ->update($updateData);
                
                // Recharger le modèle
                $subscription = Subscription::find($subscription->id);
            }
            
            // Gérer les types de cours si fournis
            if (isset($validated['course_type_ids']) && Schema::hasTable('subscription_course_types')) {
                // Supprimer les anciennes associations
                DB::table('subscription_course_types')
                    ->where('subscription_id', $subscription->id)
                    ->delete();
                
                // Ajouter les nouvelles associations
                foreach ($validated['course_type_ids'] as $courseTypeId) {
                    $courseType = \App\Models\CourseType::find($courseTypeId);
                    if ($courseType && $courseType->discipline_id) {
                        DB::table('subscription_course_types')->insert([
                            'subscription_id' => $subscription->id,
                            'discipline_id' => $courseType->discipline_id,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Abonnement mis à jour avec succès',
                'data' => $subscription
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
            Log::error('Erreur lors de la mise à jour de l\'abonnement: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de l\'abonnement'
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
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated',
                    'error' => 'Missing token'
                ], 401);
            }
            
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
                            },
                            'legacyRecurringSlots' => function ($q) {
                                $q->with(['teacher.user', 'student.user'])
                                  ->orderBy('day_of_week')
                                  ->orderBy('start_time');
                            }
                        ]);
                    }
                ])
                ->findOrFail($id);
            
            // ⚠️ IMPORTANT : Ne pas recalculer automatiquement lessons_used ici
            // car cela écraserait les valeurs manuelles entrées lors de la création.
            // Le recalcul se fait maintenant intelligemment dans le modèle :
            // - Si des cours sont attachés dans subscription_lessons, on utilise le comptage réel
            // - Si aucun cours n'est attaché et qu'une valeur manuelle existe, on la préserve
            // 
            // Pour forcer un recalcul basé sur les cours réels, utiliser l'endpoint /recalculate
            // ou laisser le système recalculer automatiquement lors de l'ajout/suppression de cours
            
            // Log pour debug : vérifier les valeurs de lessons_used avant envoi
            foreach ($subscription->instances as $instance) {
                Log::info("📊 [show] Instance {$instance->id} avant envoi:", [
                    'instance_id' => $instance->id,
                    'lessons_used' => $instance->lessons_used,
                    'lessons_count' => $instance->lessons ? $instance->lessons->count() : 0,
                    'subscription_lessons_count' => \DB::table('subscription_lessons')
                        ->where('subscription_instance_id', $instance->id)
                        ->count()
                ]);
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
     * Supprimer un abonnement
     */
    public function destroy($id): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated',
                    'error' => 'Missing token'
                ], 401);
            }
            
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

            // Récupérer l'abonnement avec ses instances et élèves
            $subscription = Subscription::forClub($club->id)
                ->with(['instances.students'])
                ->findOrFail($id);

            // Vérifier qu'il n'y a aucun élève assigné à cet abonnement
            $hasStudents = false;
            if ($subscription->instances && $subscription->instances->count() > 0) {
                foreach ($subscription->instances as $instance) {
                    if ($instance->students && $instance->students->count() > 0) {
                        $hasStudents = true;
                        break;
                    }
                }
            }

            if ($hasStudents) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de supprimer cet abonnement car il a des élèves assignés'
                ], 422);
            }

            DB::beginTransaction();

            // Supprimer les associations avec les types de cours
            if (Schema::hasTable('subscription_course_types')) {
                DB::table('subscription_course_types')
                    ->where('subscription_id', $subscription->id)
                    ->delete();
            }

            // Soft-delete l'abonnement (les instances seront supprimées en cascade si elles existent)
            $subscription->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Abonnement supprimé avec succès'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la suppression de l\'abonnement: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de l\'abonnement'
            ], 500);
        }
    }

    /**
     * Attribuer un abonnement à un ou plusieurs élèves
     */
    public function assignToStudent(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
                'error' => 'Missing token'
            ], 401);
        }
        
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

        // Support deux formats : legacy (subscription_id + student_id) ou nouveau (subscription_template_id + student_ids)
        if ($request->has('subscription_id') && $request->has('student_id')) {
            // Format legacy
            $validated = $request->validate([
                'subscription_id' => 'required|exists:subscriptions,id',
                'student_id' => 'required|exists:students,id',
                'start_date' => 'nullable|date',
            ]);
            
            try {
                $subscription = Subscription::forClub($club->id)->findOrFail($validated['subscription_id']);
                
                // Vérifier que l'élève appartient au club
                if (!DB::table('club_students')
                    ->where('club_id', $club->id)
                    ->where('student_id', $validated['student_id'])
                    ->exists()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cet élève n\'appartient pas à votre club'
                    ], 404);
                }
                
                DB::beginTransaction();
                
                // Créer une instance d'abonnement
                $startedAt = isset($validated['start_date']) ? Carbon::parse($validated['start_date']) : Carbon::now();
                
                $subscriptionInstance = SubscriptionInstance::create([
                    'subscription_id' => $subscription->id,
                    'lessons_used' => 0,
                    'started_at' => $startedAt,
                    'status' => 'active',
                ]);
                
                // Calculer expires_at si nécessaire
                if (!$subscriptionInstance->expires_at && $subscription->validity_months) {
                    $subscriptionInstance->expires_at = $startedAt->copy()->addMonths($subscription->validity_months);
                    $subscriptionInstance->save();
                }
                
                // Attacher l'élève
                $subscriptionInstance->students()->attach($validated['student_id']);
                
                DB::commit();
                
                return response()->json([
                    'success' => true,
                    'message' => 'Abonnement attribué avec succès',
                    'data' => [
                        'id' => $subscriptionInstance->id,
                        'subscription_id' => $subscription->id,
                        'student_id' => $validated['student_id'],
                    ]
                ]);
                
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Erreur lors de l\'attribution de l\'abonnement (legacy): ' . $e->getMessage());
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de l\'attribution de l\'abonnement'
                ], 500);
            }
        }
        
        // Format nouveau : subscription_template_id + student_ids
        $validated = $request->validate([
            'subscription_template_id' => 'required|exists:subscription_templates,id',
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'exists:students,id',
            'started_at' => 'nullable|date',
            'expires_at' => 'nullable|date',
            'lessons_used' => 'nullable|integer|min:0',
            // Champs pour les commissions
            'est_legacy' => 'nullable|boolean',      // false = DCL (Déclaré), true = NDCL (Non Déclaré)
            'date_paiement' => 'nullable|date',      // Date de paiement (détermine le mois de commission)
            'montant' => 'nullable|numeric|min:0',   // Montant réellement payé (peut différer du prix du template)
        ]);
        
        try {
            
            Log::info('📥 [assignToStudent] Données reçues:', [
                'validated' => $validated,
                'lessons_used_raw' => $request->input('lessons_used'),
                'lessons_used_validated' => $validated['lessons_used'] ?? null
            ]);

            // Vérifier que le template appartient au club et est actif
            $template = SubscriptionTemplate::where('club_id', $club->id)
                ->where('is_active', true)
                ->findOrFail($validated['subscription_template_id']);

            // ✅ Plusieurs abonnements actifs sont maintenant autorisés pour un même élève
            // Les abonnements seront traités par ordre chronologique (les plus anciens en premier)

            DB::beginTransaction();

            try {
                // Créer un nouvel abonnement depuis le template
                // Utiliser createSafe pour gérer automatiquement club_id
                $subscription = Subscription::createSafe([
                    'club_id' => $club->id,
                    'subscription_template_id' => $template->id,
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Erreur lors de la création de l\'abonnement (createSafe): ' . $e->getMessage(), [
                    'template_id' => $template->id,
                    'club_id' => $club->id,
                    'trace' => $e->getTraceAsString()
                ]);
                throw $e;
            }

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

            // Créer une instance d'abonnement avec les champs de commission
            $subscriptionInstanceData = [
                'subscription_id' => $subscription->id,
                'lessons_used' => $lessonsUsed,
                'started_at' => $startedAt,
                'expires_at' => isset($validated['expires_at']) && $validated['expires_at'] ? Carbon::parse($validated['expires_at']) : null,
                'status' => 'active'
            ];
            
            // Ajouter les champs de commission si fournis
            if (isset($validated['est_legacy'])) {
                $subscriptionInstanceData['est_legacy'] = $validated['est_legacy'];
            }
            if (isset($validated['date_paiement'])) {
                $subscriptionInstanceData['date_paiement'] = Carbon::parse($validated['date_paiement'])->format('Y-m-d');
            }
            if (isset($validated['montant'])) {
                $subscriptionInstanceData['montant'] = $validated['montant'];
            }
            
            $subscriptionInstance = SubscriptionInstance::create($subscriptionInstanceData);
            
            // Calculer expires_at si non fourni
            if (!$subscriptionInstance->expires_at) {
                $subscriptionInstance->calculateExpiresAt();
                $subscriptionInstance->save();
            }

            // Attacher les élèves à cette instance
            $subscriptionInstance->students()->attach($validated['student_ids']);

            Log::info('✅ [assignToStudent] Instance créée:', [
                'instance_id' => $subscriptionInstance->id,
                'lessons_used_saved' => $subscriptionInstance->lessons_used,
                'expected_lessons_used' => $lessonsUsed
            ]);

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
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated',
                    'error' => 'Missing token'
                ], 401);
            }
            
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
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated',
                    'error' => 'Missing token'
                ], 401);
            }
            
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
                'started_at' => 'nullable|date',
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
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated',
                    'error' => 'Missing token'
                ], 401);
            }
            
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

    /**
     * Clôturer un abonnement (mettre le statut à 'completed' ou 'cancelled')
     */
    public function close(Request $request, $instanceId): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated',
                    'error' => 'Missing token'
                ], 401);
            }
            
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
                'status' => 'required|in:completed,cancelled',
                'reason' => 'nullable|string|max:500'
            ]);

            // Récupérer l'instance d'abonnement
            $instance = SubscriptionInstance::whereHas('subscription', function ($query) use ($club) {
                    if (Subscription::hasClubIdColumn()) {
                        $query->where('club_id', $club->id);
                    } else {
                        $query->whereHas('template', function ($q) use ($club) {
                            $q->where('club_id', $club->id);
                        });
                    }
                })
                ->with(['subscription.template', 'students.user'])
                ->findOrFail($instanceId);

            // Vérifier que l'abonnement est actif
            if ($instance->status !== 'active') {
                return response()->json([
                    'success' => false,
                    'message' => "Cet abonnement est déjà {$instance->status} et ne peut pas être clôturé."
                ], 422);
            }

            // Mettre à jour le statut
            $oldStatus = $instance->status;
            $instance->status = $validated['status'];
            $instance->save();

            Log::info("Abonnement clôturé par le club", [
                'instance_id' => $instance->id,
                'old_status' => $oldStatus,
                'new_status' => $validated['status'],
                'reason' => $validated['reason'] ?? null,
                'club_id' => $club->id,
                'user_id' => $user->id
            ]);

            // Recharger les relations
            $instance->load(['subscription.template.courseTypes', 'students.user']);

            return response()->json([
                'success' => true,
                'message' => "Abonnement clôturé avec succès (statut: {$validated['status']})",
                'data' => $instance
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Abonnement non trouvé'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la clôture de l\'abonnement: ' . $e->getMessage(), [
                'instance_id' => $instanceId,
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la clôture de l\'abonnement: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mettre à jour le statut DCL/NDCL d'une instance d'abonnement
     */
    public function updateEstLegacy(Request $request, $instanceId): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated',
                    'error' => 'Missing token'
                ], 401);
            }
            
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
                'est_legacy' => 'required|boolean'
            ]);

            // Récupérer l'instance d'abonnement
            $instance = SubscriptionInstance::whereHas('subscription', function ($query) use ($club) {
                    if (Subscription::hasClubIdColumn()) {
                        $query->where('club_id', $club->id);
                    } else {
                        $query->whereHas('template', function ($q) use ($club) {
                            $q->where('club_id', $club->id);
                        });
                    }
                })
                ->with(['subscription.template', 'students.user'])
                ->findOrFail($instanceId);

            $oldEstLegacy = $instance->est_legacy;
            $instance->est_legacy = $validated['est_legacy'];
            $instance->save();

            // Propager le changement aux cours associés
            $updatedLessonsCount = $instance->propagateEstLegacyToLessons();

            // Enregistrer dans l'historique si le statut a changé
            if ($oldEstLegacy !== $instance->est_legacy) {
                AuditLog::create([
                    'user_id' => $user->id,
                    'action' => 'subscription_instance_est_legacy_updated',
                    'model_type' => SubscriptionInstance::class,
                    'model_id' => $instance->id,
                    'data' => [
                        'old_est_legacy' => $oldEstLegacy,
                        'new_est_legacy' => $instance->est_legacy,
                        'old_status' => $oldEstLegacy ? 'NDCL' : 'DCL',
                        'new_status' => $instance->est_legacy ? 'NDCL' : 'DCL',
                        'updated_lessons_count' => $updatedLessonsCount,
                        'instance_id' => $instance->id,
                        'subscription_id' => $instance->subscription_id,
                    ],
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
            }

            Log::info("Statut DCL/NDCL mis à jour pour l'abonnement", [
                'instance_id' => $instance->id,
                'old_est_legacy' => $oldEstLegacy,
                'new_est_legacy' => $instance->est_legacy,
                'status' => $instance->est_legacy ? 'NDCL' : 'DCL',
                'updated_lessons_count' => $updatedLessonsCount,
                'club_id' => $club->id,
                'user_id' => $user->id
            ]);

            // Recharger les relations
            $instance->load([
                'subscription.template.courseTypes',
                'students' => function ($query) {
                    $query->with('user');
                },
                'lessons' => function ($q) {
                    $q->with(['teacher.user', 'courseType', 'location'])
                      ->orderBy('start_time', 'desc');
                }
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Statut DCL/NDCL mis à jour avec succès. ' . 
                           ($updatedLessonsCount > 0 ? "{$updatedLessonsCount} cours mis à jour." : ''),
                'data' => $instance
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Abonnement non trouvé'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la mise à jour du statut DCL/NDCL: ' . $e->getMessage(), [
                'instance_id' => $instanceId,
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du statut DCL/NDCL: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mettre à jour une instance d'abonnement
     */
    public function updateInstance(Request $request, $instanceId): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated',
                    'error' => 'Missing token'
                ], 401);
            }
            
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
                'started_at' => 'nullable|date',
                'expires_at' => 'nullable|date',
                'status' => 'required|in:active,completed,expired,cancelled',
                'lessons_used' => 'nullable|integer|min:0',
                'est_legacy' => 'nullable|boolean',
            ]);

            // Récupérer l'instance d'abonnement avec les cours pour calculer la valeur manuelle
            $instance = SubscriptionInstance::whereHas('subscription', function ($query) use ($club) {
                    if (Subscription::hasClubIdColumn()) {
                        $query->where('club_id', $club->id);
                    } else {
                        $query->whereHas('template', function ($q) use ($club) {
                            $q->where('club_id', $club->id);
                        });
                    }
                })
                ->with(['subscription.template', 'students.user', 'lessons'])
                ->findOrFail($instanceId);

            // Sauvegarder les anciennes valeurs pour l'historique
            $oldValues = [
                'started_at' => $instance->started_at,
                'expires_at' => $instance->expires_at,
                'status' => $instance->status,
                'lessons_used' => $instance->lessons_used,
                'est_legacy' => $instance->est_legacy,
            ];

            // Mettre à jour les valeurs
            $startedAtChanged = false;
            if (isset($validated['started_at'])) {
                $oldStartedAt = $instance->started_at;
                $instance->started_at = $validated['started_at'];
                $startedAtChanged = ($oldStartedAt != $instance->started_at);
            }
            
            // ⚠️ IMPORTANT : Si la date de début est modifiée, recalculer automatiquement la date d'expiration
            // sauf si une date d'expiration est explicitement fournie dans la requête
            // Si expires_at est null ou vide dans la requête, cela signifie qu'on doit recalculer
            if ($startedAtChanged) {
                if (!isset($validated['expires_at']) || $validated['expires_at'] === null || $validated['expires_at'] === '') {
                    // Pas de date d'expiration fournie → recalculer automatiquement
                    $instance->calculateExpiresAt();
                    Log::info("🔄 Date d'expiration recalculée automatiquement suite à la modification de la date de début", [
                        'instance_id' => $instance->id,
                        'new_started_at' => $instance->started_at,
                        'new_expires_at' => $instance->expires_at,
                        'old_expires_at' => $oldValues['expires_at']
                    ]);
                } else {
                    // Date d'expiration fournie → utiliser la valeur fournie (modification manuelle)
                    $instance->expires_at = $validated['expires_at'];
                    Log::info("📅 Date d'expiration modifiée manuellement", [
                        'instance_id' => $instance->id,
                        'new_started_at' => $instance->started_at,
                        'new_expires_at' => $instance->expires_at,
                        'old_expires_at' => $oldValues['expires_at']
                    ]);
                }
            } elseif (isset($validated['expires_at']) && $validated['expires_at'] !== null && $validated['expires_at'] !== '') {
                // Date d'expiration modifiée sans changement de date de début → utiliser la valeur fournie
                $instance->expires_at = $validated['expires_at'];
            }
            
            $instance->status = $validated['status'];
            if (isset($validated['lessons_used'])) {
                $instance->lessons_used = $validated['lessons_used'];
            }
            
            // ⚠️ IMPORTANT : Mettre à jour est_legacy si fourni
            $estLegacyChanged = false;
            if (isset($validated['est_legacy'])) {
                $oldEstLegacy = $instance->est_legacy;
                $instance->est_legacy = $validated['est_legacy'];
                $estLegacyChanged = ($oldEstLegacy !== $instance->est_legacy);
            }
            
            $instance->save();
            
            // ⚠️ IMPORTANT : Si est_legacy a changé, propager aux cours sauf ceux déjà payés
            if ($estLegacyChanged) {
                $lessons = $instance->lessons()->get();
                $updatedCount = 0;
                $skippedCount = 0;
                
                foreach ($lessons as $lesson) {
                    // Ne pas modifier les cours déjà payés
                    if ($lesson->payment_status === 'paid') {
                        $skippedCount++;
                        Log::info("⏭️ Cours {$lesson->id} ignoré (déjà payé) lors de la propagation DCL/NDCL", [
                            'lesson_id' => $lesson->id,
                            'payment_status' => $lesson->payment_status,
                            'instance_id' => $instance->id
                        ]);
                        continue;
                    }
                    
                    // Mettre à jour le statut DCL/NDCL du cours
                    if ($lesson->est_legacy !== $instance->est_legacy) {
                        $lesson->est_legacy = $instance->est_legacy;
                        $lesson->saveQuietly();
                        $updatedCount++;
                        
                        Log::info("🔄 Statut DCL/NDCL propagé au cours {$lesson->id}", [
                            'lesson_id' => $lesson->id,
                            'subscription_instance_id' => $instance->id,
                            'est_legacy' => $instance->est_legacy,
                            'status' => $instance->est_legacy ? 'NDCL' : 'DCL',
                            'payment_status' => $lesson->payment_status
                        ]);
                    }
                }
                
                Log::info("✅ Propagation DCL/NDCL terminée pour l'instance {$instance->id}", [
                    'instance_id' => $instance->id,
                    'updated_lessons' => $updatedCount,
                    'skipped_lessons' => $skippedCount,
                    'total_lessons' => $lessons->count(),
                    'new_est_legacy' => $instance->est_legacy
                ]);
            }

            // Enregistrer dans l'historique
            $changes = [];
            foreach ($oldValues as $key => $oldValue) {
                $newValue = $instance->getAttribute($key);
                if ($oldValue != $newValue) {
                    $changes[$key] = [
                        'old' => $oldValue,
                        'new' => $newValue
                    ];
                }
            }

            if (!empty($changes)) {
                AuditLog::create([
                    'user_id' => $user->id,
                    'action' => 'subscription_instance_updated',
                    'model_type' => SubscriptionInstance::class,
                    'model_id' => $instance->id,
                    'data' => [
                        'changes' => $changes,
                        'instance_id' => $instance->id,
                        'subscription_id' => $instance->subscription_id,
                    ],
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
            }

            Log::info("Instance d'abonnement modifiée", [
                'instance_id' => $instance->id,
                'changes' => $changes,
                'club_id' => $club->id,
                'user_id' => $user->id
            ]);

            // Recharger les relations
            $instance->load(['subscription.template.courseTypes', 'students.user']);

            return response()->json([
                'success' => true,
                'message' => 'Abonnement modifié avec succès',
                'data' => $instance
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Instance d\'abonnement non trouvée'
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la modification de l\'instance d\'abonnement: ' . $e->getMessage(), [
                'instance_id' => $instanceId,
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la modification: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupérer l'historique des actions pour une instance d'abonnement
     */
    public function getInstanceHistory($instanceId): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated',
                    'error' => 'Missing token'
                ], 401);
            }
            
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

            // Vérifier que l'instance appartient au club
            $instance = SubscriptionInstance::whereHas('subscription', function ($query) use ($club) {
                    if (Subscription::hasClubIdColumn()) {
                        $query->where('club_id', $club->id);
                    } else {
                        $query->whereHas('template', function ($q) use ($club) {
                            $q->where('club_id', $club->id);
                        });
                    }
                })
                ->findOrFail($instanceId);

            // Récupérer l'historique depuis audit_logs
            $logs = AuditLog::where('model_type', SubscriptionInstance::class)
                ->where('model_id', $instanceId)
                ->with('user')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($log) {
                    return [
                        'id' => $log->id,
                        'action' => $log->action,
                        'description' => $this->getActionDescription($log),
                        'icon' => $this->getActionIcon($log->action),
                        'user' => $log->user ? [
                            'id' => $log->user->id,
                            'name' => $log->user->name,
                            'email' => $log->user->email,
                        ] : null,
                        'data' => $log->data,
                        'created_at' => $log->created_at->toISOString(),
                    ];
                });

            // Ajouter aussi la création de l'instance comme première action
            $creationLog = [
                'id' => 'creation',
                'action' => 'subscription_instance_created',
                'description' => 'Abonnement créé',
                'icon' => '➕',
                'user' => null,
                'data' => [
                    'started_at' => $instance->started_at,
                    'expires_at' => $instance->expires_at,
                    'status' => $instance->status,
                ],
                'created_at' => $instance->created_at->toISOString(),
            ];

            $allLogs = collect([$creationLog])->merge($logs)->sortByDesc('created_at')->values();

            return response()->json([
                'success' => true,
                'data' => $allLogs
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Instance d\'abonnement non trouvée'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération de l\'historique: ' . $e->getMessage(), [
                'instance_id' => $instanceId,
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
     * Récupérer le nombre de cours futurs d'une instance d'abonnement
     */
    public function getFutureLessons($instanceId): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated',
                    'error' => 'Missing token'
                ], 401);
            }
            
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

            // Vérifier que l'instance appartient au club
            $instance = SubscriptionInstance::whereHas('subscription', function ($query) use ($club) {
                    if (Subscription::hasClubIdColumn()) {
                        $query->where('club_id', $club->id);
                    } else {
                        $query->whereHas('template', function ($q) use ($club) {
                            $q->where('club_id', $club->id);
                        });
                    }
                })
                ->findOrFail($instanceId);

            // Récupérer la date de référence depuis la requête
            $afterDate = request()->query('after_date');
            if (!$afterDate) {
                return response()->json([
                    'success' => false,
                    'message' => 'Le paramètre after_date est requis'
                ], 400);
            }

            $referenceDate = Carbon::parse($afterDate)->startOfDay();
            
            // Paramètre optionnel : inclure les cours annulés (utile si le cours actuel est annulé)
            $includeCancelled = request()->query('include_cancelled', 'false') === 'true';
            
            // Paramètres optionnels pour filtrer par créneau (même jour, même plage horaire, même élève, même club)
            $filterBySlot = request()->has('reference_lesson_time') && 
                           request()->has('reference_lesson_end_time') &&
                           request()->has('reference_student_id') &&
                           request()->has('reference_club_id');

            // Récupérer les cours futurs de cette instance d'abonnement
            $futureLessonsQuery = $instance->lessons()
                ->where('start_time', '>', $referenceDate)
                ->orderBy('start_time', 'asc');
            
            // 🔒 FILTRAGE PAR CRÉNEAU : Si les paramètres de référence sont fournis, filtrer strictement
            if ($filterBySlot) {
                $referenceLessonTime = request()->query('reference_lesson_time'); // Format "HH:MM:SS"
                $referenceLessonEndTime = request()->query('reference_lesson_end_time'); // Format "HH:MM:SS"
                $referenceStudentId = request()->query('reference_student_id');
                $referenceClubId = request()->query('reference_club_id');
                $referenceDayOfWeek = request()->query('reference_day_of_week'); // Valeur MySQL DAYOFWEEK (1-7)
                
                // Si reference_day_of_week n'est pas fourni, l'extraire de after_date
                if (!$referenceDayOfWeek) {
                    $refDateCarbon = Carbon::parse($afterDate);
                    $refDayCarbon = $refDateCarbon->dayOfWeek; // 0 (Dim) à 6 (Sam)
                    $referenceDayOfWeek = $refDayCarbon === 0 ? 1 : ($refDayCarbon + 1); // Conversion MySQL
                }
                
                // Filtrer par jour de semaine (MySQL DAYOFWEEK : 1=Dimanche, 7=Samedi)
                $futureLessonsQuery->whereRaw('DAYOFWEEK(lessons.start_time) = ?', [$referenceDayOfWeek])
                    // Filtrer par plage horaire (même heure de début et fin)
                    ->whereRaw('TIME(lessons.start_time) = ?', [$referenceLessonTime])
                    ->whereRaw('TIME(lessons.end_time) = ?', [$referenceLessonEndTime])
                    // Filtrer par élève
                    ->where('lessons.student_id', $referenceStudentId)
                    // Filtrer par club
                    ->where('lessons.club_id', $referenceClubId);
            }
            
            // Si include_cancelled est false, exclure les cours annulés
            // Sinon, inclure tous les cours (y compris annulés)
            if (!$includeCancelled) {
                $futureLessonsQuery->where('status', '!=', 'cancelled');
            }
            
            $futureLessons = $futureLessonsQuery->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'count' => $futureLessons->count(),
                    'lessons' => $futureLessons->map(function ($lesson) {
                        return [
                            'id' => $lesson->id,
                            'start_time' => $lesson->start_time->toISOString(),
                            'end_time' => $lesson->end_time ? $lesson->end_time->toISOString() : null,
                            'status' => $lesson->status,
                            'teacher_id' => $lesson->teacher_id,
                            'course_type_id' => $lesson->course_type_id
                        ];
                    })
                ]
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Instance d\'abonnement non trouvée'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des cours futurs: ' . $e->getMessage(), [
                'instance_id' => $instanceId,
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des cours futurs: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir la description d'une action pour l'affichage
     */
    private function getActionDescription(AuditLog $log): string
    {
        $data = $log->data ?? [];
        
        switch ($log->action) {
            case 'subscription_instance_updated':
                $changes = $data['changes'] ?? [];
                $descriptions = [];
                
                foreach ($changes as $field => $change) {
                    $fieldLabels = [
                        'started_at' => 'Date de début',
                        'expires_at' => 'Date d\'expiration',
                        'status' => 'Statut',
                        'lessons_used' => 'Nombre de cours utilisés',
                    ];
                    
                    $label = $fieldLabels[$field] ?? $field;
                    $oldValue = $change['old'];
                    $newValue = $change['new'];
                    
                    if ($field === 'status') {
                        $statusLabels = [
                            'active' => 'Actif',
                            'completed' => 'Terminé',
                            'expired' => 'Expiré',
                            'cancelled' => 'Annulé',
                        ];
                        $oldValue = $statusLabels[$oldValue] ?? $oldValue;
                        $newValue = $statusLabels[$newValue] ?? $newValue;
                    } elseif (in_array($field, ['started_at', 'expires_at'])) {
                        $oldValue = $oldValue ? Carbon::parse($oldValue)->format('d/m/Y') : 'Non défini';
                        $newValue = $newValue ? Carbon::parse($newValue)->format('d/m/Y') : 'Non défini';
                    }
                    
                    $descriptions[] = "{$label}: {$oldValue} → {$newValue}";
                }
                
                return 'Modification: ' . implode(', ', $descriptions);
                
            case 'subscription_instance_created':
                return 'Abonnement créé';
                
            default:
                return ucfirst(str_replace('_', ' ', $log->action));
        }
    }

    /**
     * Obtenir l'icône d'une action
     */
    private function getActionIcon(string $action): string
    {
        $icons = [
            'subscription_instance_created' => '➕',
            'subscription_instance_updated' => '✏️',
            'subscription_instance_est_legacy_updated' => '🏷️',
            'subscription_instance_status_changed' => '🔄',
            'subscription_instance_closed' => '✅',
        ];
        
        return $icons[$action] ?? '📝';
    }

}

