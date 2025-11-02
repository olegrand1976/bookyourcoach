<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\SubscriptionInstance;
use App\Models\Student;
use App\Models\CourseType;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StudentSubscriptionController extends Controller
{
    /**
     * Liste tous les abonnements disponibles pour l'élève (proposés par les clubs où il est inscrit)
     */
    public function availableSubscriptions(Request $request): JsonResponse
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

            // Récupérer les clubs où l'élève est inscrit
            $clubIds = $student->clubs()->wherePivot('is_active', true)->pluck('clubs.id');

            // Récupérer les modèles d'abonnements actifs de ces clubs
            $templates = \App\Models\SubscriptionTemplate::whereIn('club_id', $clubIds)
                ->where('is_active', true)
                ->with(['club:id,name', 'courseTypes:id,name,description'])
                ->orderBy('price', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $templates
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des abonnements disponibles: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des abonnements'
            ], 500);
        }
    }

    /**
     * Liste les abonnements actifs de l'élève
     */
    public function mySubscriptions(Request $request): JsonResponse
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

            // Récupérer les instances d'abonnements de cet élève
            $subscriptionInstances = SubscriptionInstance::whereHas('students', function ($query) use ($student) {
                    $query->where('students.id', $student->id);
                })
                ->with([
                    'subscription:id,club_id,subscription_template_id,subscription_number',
                    'subscription.club:id,name',
                    'subscription.template:id,model_number,total_lessons,free_lessons,price,validity_months',
                    'subscription.template.courseTypes:id,name,description',
                    'students.user:id,name,first_name,last_name'
                ])
                ->orderBy('status', 'asc')
                ->orderBy('created_at', 'desc')
                ->get();

            // Mettre à jour les statuts si nécessaire
            foreach ($subscriptionInstances as $instance) {
                $instance->checkAndUpdateStatus();
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

    /**
     * Souscrire à un abonnement (uniquement personnel, pas familial)
     */
    public function subscribe(Request $request): JsonResponse
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

            $validated = $request->validate([
                'subscription_template_id' => 'required|exists:subscription_templates,id',
                'started_at' => 'nullable|date|after_or_equal:today',
            ]);

            // Récupérer le modèle d'abonnement
            $template = \App\Models\SubscriptionTemplate::with('club')->findOrFail($validated['subscription_template_id']);

            // Vérifier que l'élève est inscrit dans le club qui propose ce modèle
            $isMember = $student->clubs()
                ->wherePivot('is_active', true)
                ->where('clubs.id', $template->club_id)
                ->exists();

            if (!$isMember) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous devez être inscrit dans ce club pour souscrire à cet abonnement'
                ], 403);
            }

            // Vérifier que le modèle est actif
            if (!$template->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce modèle d\'abonnement n\'est plus disponible'
                ], 422);
            }

            DB::beginTransaction();

            // Créer l'abonnement depuis le template
            $subscription = Subscription::create([
                'club_id' => $template->club_id,
                'subscription_template_id' => $template->id,
            ]);

            // Date de début (aujourd'hui par défaut)
            $startedAt = $validated['started_at'] ? Carbon::parse($validated['started_at']) : Carbon::now();

            // Créer l'instance d'abonnement (personnel uniquement)
            $subscriptionInstance = SubscriptionInstance::create([
                'subscription_id' => $subscription->id,
                'lessons_used' => 0,
                'started_at' => $startedAt,
                'expires_at' => null, // Sera calculé automatiquement
                'status' => 'active'
            ]);

            // Calculer la date d'expiration
            $subscriptionInstance->calculateExpiresAt();
            $subscriptionInstance->save();

            // Attacher uniquement cet élève (abonnement personnel)
            $subscriptionInstance->students()->attach($student->id);

            DB::commit();

            $subscriptionInstance->load([
                'subscription.club',
                'subscription.template.courseTypes',
                'students.user'
            ]);

            // TODO: Intégrer un système de paiement ici (Stripe, PayPal, etc.)

            return response()->json([
                'success' => true,
                'message' => 'Abonnement souscrit avec succès (Numéro: ' . $subscription->subscription_number . ')',
                'data' => $subscriptionInstance
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la souscription à l\'abonnement: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la souscription à l\'abonnement'
            ], 500);
        }
    }

    /**
     * Renouveler un abonnement existant
     */
    public function renew(Request $request, $instanceId): JsonResponse
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

            $validated = $request->validate([
                'started_at' => 'nullable|date|after_or_equal:today',
            ]);

            // Récupérer l'instance d'abonnement existante
            $existingInstance = SubscriptionInstance::whereHas('students', function ($query) use ($student) {
                    $query->where('students.id', $student->id);
                })
                ->with('subscription')
                ->findOrFail($instanceId);

            // Vérifier que l'instance appartient bien à cet élève
            if (!$existingInstance->students->contains($student->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cet abonnement ne vous appartient pas'
                ], 403);
            }

            // Vérifier que l'abonnement peut être renouvelé (actif ou expiré, pas annulé)
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
            $newSubscription = Subscription::create([
                'club_id' => $existingInstance->subscription->club_id,
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
                'students.user'
            ]);

            // TODO: Intégrer un système de paiement ici (Stripe, PayPal, etc.)

            return response()->json([
                'success' => true,
                'message' => 'Abonnement renouvelé avec succès (Numéro: ' . $newSubscription->subscription_number . ')',
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
            Log::error('Erreur lors du renouvellement de l\'abonnement: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du renouvellement de l\'abonnement'
            ], 500);
        }
    }
}

