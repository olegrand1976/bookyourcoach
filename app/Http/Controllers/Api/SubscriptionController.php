<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\SubscriptionInstance;
use App\Models\SubscriptionStudent;
use App\Models\Discipline;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

            $subscriptions = Subscription::where('club_id', $club->id)
                ->with(['courseTypes', 'instances' => function ($query) {
                    $query->where('status', 'active')
                          ->with('students.user');
                }])
                ->orderBy('is_active', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();
            
            // Ajouter l'alias subscriptionStudents pour compatibilité frontend
            foreach ($subscriptions as $subscription) {
                $subscription->subscription_students = $subscription->instances;
            }

            return response()->json([
                'success' => true,
                'data' => $subscriptions
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des abonnements: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des abonnements'
            ], 500);
        }
    }

    /**
     * Créer un nouvel abonnement
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
                'name' => 'required|string|max:255',
                'total_lessons' => 'required|integer|min:1',
                'free_lessons' => 'nullable|integer|min:0',
                'price' => 'required|numeric|min:0',
                'description' => 'nullable|string',
                'is_active' => 'boolean',
                'course_type_ids' => 'required|array|min:1',
                'course_type_ids.*' => 'exists:disciplines,id'
            ]);

            DB::beginTransaction();

            $subscription = Subscription::create([
                'club_id' => $club->id,
                'name' => $validated['name'],
                'total_lessons' => $validated['total_lessons'],
                'free_lessons' => $validated['free_lessons'] ?? 0,
                'price' => $validated['price'],
                'description' => $validated['description'] ?? null,
                'is_active' => $validated['is_active'] ?? true,
            ]);

            // Attacher les types de cours
            $subscription->courseTypes()->sync($validated['course_type_ids']);

            DB::commit();

            // Recharger avec les relations
            $subscription->load('courseTypes');

            return response()->json([
                'success' => true,
                'message' => 'Abonnement créé avec succès',
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

            $subscription = Subscription::where('club_id', $club->id)
                ->with(['courseTypes', 'subscriptionStudents.student.user'])
                ->findOrFail($id);

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
     * Mettre à jour un abonnement
     */
    public function update(Request $request, $id): JsonResponse
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

            $subscription = Subscription::where('club_id', $club->id)->findOrFail($id);

            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'total_lessons' => 'sometimes|integer|min:1',
                'free_lessons' => 'sometimes|integer|min:0',
                'price' => 'sometimes|numeric|min:0',
                'description' => 'nullable|string',
                'is_active' => 'boolean',
                'course_type_ids' => 'sometimes|array|min:1',
                'course_type_ids.*' => 'exists:disciplines,id'
            ]);

            DB::beginTransaction();

            $subscription->update($validated);

            // Mettre à jour les types de cours si fournis
            if (isset($validated['course_type_ids'])) {
                $subscription->courseTypes()->sync($validated['course_type_ids']);
            }

            DB::commit();

            // Recharger avec les relations
            $subscription->load('courseTypes');

            return response()->json([
                'success' => true,
                'message' => 'Abonnement mis à jour avec succès',
                'data' => $subscription
            ]);

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
     * Supprimer un abonnement
     */
    public function destroy($id): JsonResponse
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

            $subscription = Subscription::where('club_id', $club->id)->findOrFail($id);

            // Vérifier s'il y a des abonnements actifs d'élèves
            $activeSubscriptions = $subscription->subscriptionStudents()
                ->where('status', 'active')
                ->count();

            if ($activeSubscriptions > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de supprimer cet abonnement car des élèves l\'utilisent actuellement'
                ], 422);
            }

            $subscription->delete();

            return response()->json([
                'success' => true,
                'message' => 'Abonnement supprimé avec succès'
            ]);

        } catch (\Exception $e) {
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
                'subscription_id' => 'required|exists:subscriptions,id',
                'student_ids' => 'required|array|min:1',
                'student_ids.*' => 'exists:students,id',
                'started_at' => 'required|date',
                'expires_at' => 'nullable|date|after:started_at'
            ]);

            // Vérifier que l'abonnement appartient au club
            $subscription = Subscription::where('club_id', $club->id)
                ->findOrFail($validated['subscription_id']);

            DB::beginTransaction();

            // Créer une instance d'abonnement
            $subscriptionInstance = SubscriptionInstance::create([
                'subscription_id' => $validated['subscription_id'],
                'lessons_used' => 0,
                'started_at' => $validated['started_at'],
                'expires_at' => $validated['expires_at'] ?? null,
                'status' => 'active'
            ]);

            // Attacher les élèves à cette instance
            $subscriptionInstance->students()->attach($validated['student_ids']);

            DB::commit();

            $subscriptionInstance->load(['subscription', 'students.user']);

            return response()->json([
                'success' => true,
                'message' => count($validated['student_ids']) > 1 
                    ? 'Abonnement partagé créé avec succès pour ' . count($validated['student_ids']) . ' élèves'
                    : 'Abonnement attribué avec succès',
                'data' => $subscriptionInstance
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de l\'attribution de l\'abonnement: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'attribution de l\'abonnement'
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
                    $query->where('club_id', $club->id);
                })
                ->with(['subscription.courseTypes', 'students.user'])
                ->orderBy('status', 'asc')
                ->orderBy('created_at', 'desc')
                ->get();

            // Mettre à jour les statuts si nécessaire
            foreach ($subscriptionInstances as $sub) {
                $sub->checkAndUpdateStatus();
            }

            return response()->json([
                'success' => true,
                'data' => $subscriptionInstances
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des abonnements de l\'élève: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des abonnements'
            ], 500);
        }
    }
}

