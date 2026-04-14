<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\SubscriptionInstance;
use App\Models\Student;
use App\Models\CourseType;
use App\Services\StripeService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StudentSubscriptionController extends Controller
{
    private StripeService $stripeService;

    public function __construct(StripeService $stripeService)
    {
        $this->stripeService = $stripeService;
    }
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

            $mainStudent = $user->student;
            if (!$mainStudent) {
                return response()->json([
                    'success' => false,
                    'message' => 'Profil élève non trouvé'
                ], 404);
            }

            // Élève cible : paramètre active_student_id (compte famille) ou élève principal
            $student = $mainStudent;
            $activeStudentId = $request->query('active_student_id') ?? $request->input('active_student_id');
            if ($activeStudentId !== null && $activeStudentId !== '') {
                $linkedIds = $user->getHouseholdStudentIds();
                if (in_array((int) $activeStudentId, $linkedIds, true)) {
                    $student = Student::findOrFail((int) $activeStudentId);
                }
            }

            $activeClubs = $student->clubs()
                ->wherePivot('is_active', true)
                ->select(
                    'clubs.id',
                    'clubs.name',
                    'club_students.is_blocked',
                    'club_students.subscription_creation_blocked'
                )
                ->get();

            // Clubs où l'élève est inscrit et non bloqué
            $clubIds = $activeClubs
                ->filter(fn ($club) => !($club->pivot->is_blocked ?? false))
                ->pluck('id');

            // Récupérer les modèles d'abonnements actifs de ces clubs
            $templates = \App\Models\SubscriptionTemplate::whereIn('club_id', $clubIds)
                ->where('is_active', true)
                ->where(function ($query) {
                    $query->where('stripe_enabled', true)
                        ->orWhereNotNull('stripe_price_id');
                })
                ->with(['club:id,name', 'courseTypes:id,name,description'])
                ->orderBy('price', 'asc')
                ->get();

            $activeTemplatesWithoutStripe = \App\Models\SubscriptionTemplate::whereIn('club_id', $clubIds)
                ->where('is_active', true)
                ->where(function ($query) {
                    $query->where('stripe_enabled', false)
                        ->whereNull('stripe_price_id');
                })
                ->count();

            $message = null;
            if ($templates->isEmpty()) {
                if ($activeClubs->isEmpty()) {
                    $message = 'Aucun club actif n’est associé a votre compte élève.';
                } elseif ($activeClubs->every(fn ($club) => (bool) ($club->pivot->is_blocked ?? false))) {
                    $message = 'Votre compte élève est bloque pour votre club. Contactez le club pour activer la souscription.';
                } elseif ($activeClubs->every(fn ($club) => (bool) ($club->pivot->subscription_creation_blocked ?? false))) {
                    $message = 'La souscription en ligne est desactivee pour votre compte. Contactez votre club.';
                } elseif ($activeTemplatesWithoutStripe > 0) {
                    $message = 'Des abonnements existent pour vos clubs, mais ils ne sont pas encore activés pour le paiement Stripe. Contactez votre club.';
                } else {
                    $message = 'Aucun abonnement actif n’est actuellement propose par vos clubs.';
                }
            }

            return response()->json([
                'success' => true,
                'data' => $templates,
                'message' => $message
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

            $param = $request->query('active_student_id') ?? $request->input('active_student_id');
            $linkedIds = $user->getHouseholdStudentIds();

            $studentIds = $linkedIds;
            if ($param !== 'all' && $param !== null && $param !== '') {
                $id = (int) $param;
                if (in_array($id, $linkedIds, true)) {
                    $studentIds = [$id];
                }
            }

            if (empty($studentIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Profil élève non trouvé'
                ], 404);
            }

            // Récupérer les instances d'abonnements pour le(s) élève(s)
            $subscriptionInstances = SubscriptionInstance::whereHas('students', function ($query) use ($studentIds) {
                    $query->whereIn('students.id', $studentIds);
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
     * Créer une session Stripe Checkout pour souscrire à un abonnement
     */
    public function createCheckoutSession(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if ($user->role !== 'student') {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès réservé aux élèves'
                ], 403);
            }

            $mainStudent = $user->student;
            if (!$mainStudent) {
                return response()->json([
                    'success' => false,
                    'message' => 'Profil élève non trouvé'
                ], 404);
            }

            $validated = $request->validate([
                'subscription_template_id' => 'required|exists:subscription_templates,id',
                'active_student_id' => 'nullable|integer|exists:students,id',
            ]);

            // Utiliser l'élève actif (compte famille) si fourni et autorisé, sinon l'élève principal
            $student = $mainStudent;
            if (!empty($validated['active_student_id'])) {
                $linkedIds = $user->getHouseholdStudentIds();
                if (in_array((int) $validated['active_student_id'], $linkedIds, true)) {
                    $student = Student::findOrFail($validated['active_student_id']);
                }
            }

            // Récupérer le modèle d'abonnement
            $template = \App\Models\SubscriptionTemplate::with('club')->findOrFail($validated['subscription_template_id']);

            // Vérifier que l'élève est inscrit dans le club qui propose ce modèle
            $clubPivot = $student->clubs()
                ->where('clubs.id', $template->club_id)
                ->first();
            if (!$clubPivot || !$clubPivot->pivot->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous devez être inscrit dans ce club pour souscrire à cet abonnement'
                ], 403);
            }
            if ($clubPivot->pivot->is_blocked ?? true) {
                return response()->json([
                    'success' => false,
                    'message' => 'Votre compte est bloqué pour ce club. Contactez le club.'
                ], 403);
            }
            if ($clubPivot->pivot->subscription_creation_blocked ?? true) {
                return response()->json([
                    'success' => false,
                    'message' => 'La création d\'abonnement par l\'élève est désactivée pour ce club.'
                ], 403);
            }

            // Vérifier que le modèle est actif
            if (!$template->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce modèle d\'abonnement n\'est plus disponible'
                ], 422);
            }

            if (!$template->stripe_enabled && empty($template->stripe_price_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce modèle n’est pas disponible en paiement Stripe'
                ], 422);
            }

            // URLs de redirection
            $baseUrl = config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:3000'));
            $successUrl = $baseUrl . '/student/subscriptions?session_id={CHECKOUT_SESSION_ID}';
            $cancelUrl = $baseUrl . '/student/subscriptions/subscribe';

            // Créer la session Stripe Checkout
            $session = $this->stripeService->createCheckoutSession(
                $user,
                $template,
                $successUrl,
                $cancelUrl,
                [
                    'student_id' => $student->id,
                ]
            );

            if (!$session) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de la création de la session de paiement'
                ], 500);
            }

            return response()->json([
                'success' => true,
                'checkout_url' => $session->url,
                'session_id' => $session->id
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la création de la session Checkout: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'template_id' => $request->input('subscription_template_id'),
            ]);
            $message = 'Erreur lors de la création de la session de paiement';
            if (config('app.debug')) {
                $message .= ': ' . $e->getMessage();
            }
            return response()->json([
                'success' => false,
                'message' => $message
            ], 500);
        }
    }

    /**
     * Confirmer le paiement Stripe au retour du Checkout et créer l'abonnement si nécessaire.
     */
    public function confirmCheckoutSession(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            if ($user->role !== 'student') {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès réservé aux élèves'
                ], 403);
            }

            $validated = $request->validate([
                'session_id' => 'required|string',
            ]);

            $session = $this->stripeService->retrieveCheckoutSession($validated['session_id']);
            if (!$session) {
                return response()->json([
                    'success' => false,
                    'message' => 'Session Stripe introuvable'
                ], 404);
            }

            $sessionData = $session->toArray();
            $metadata = $sessionData['metadata'] ?? [];

            if (($metadata['type'] ?? '') !== 'subscription') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette session Stripe n’est pas liée à un abonnement'
                ], 422);
            }

            if (($metadata['user_id'] ?? null) != $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette session Stripe ne vous appartient pas'
                ], 403);
            }

            if (($sessionData['payment_status'] ?? null) !== 'paid') {
                return response()->json([
                    'success' => false,
                    'message' => 'Le paiement Stripe n’est pas encore confirmé'
                ], 202);
            }

            $instance = $this->stripeService->ensureSubscriptionCreatedFromCheckoutSession($sessionData);

            if (!$instance) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de finaliser la création de l’abonnement'
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'Paiement confirmé et abonnement activé',
                'data' => $instance
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la confirmation de la session Stripe: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la confirmation du paiement'
            ], 500);
        }
    }

    /**
     * Souscrire à un abonnement (uniquement personnel, pas familial)
     * Cette méthode est maintenant utilisée uniquement pour créer l'abonnement après paiement Stripe
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
            $clubPivot = $student->clubs()
                ->where('clubs.id', $template->club_id)
                ->first();
            if (!$clubPivot || !$clubPivot->pivot->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous devez être inscrit dans ce club pour souscrire à cet abonnement'
                ], 403);
            }
            if ($clubPivot->pivot->is_blocked ?? true) {
                return response()->json([
                    'success' => false,
                    'message' => 'Votre compte est bloqué pour ce club. Contactez le club.'
                ], 403);
            }
            if ($clubPivot->pivot->subscription_creation_blocked ?? true) {
                return response()->json([
                    'success' => false,
                    'message' => 'La création d\'abonnement par l\'élève est désactivée pour ce club.'
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
            $subscription = Subscription::createSafe([
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
            $newSubscription = Subscription::createSafe([
                'club_id' => $existingInstance->subscription->club_id ?? null,
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

