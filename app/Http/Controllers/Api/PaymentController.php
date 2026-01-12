<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Lesson;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * @OA\Tag(
 *     name="Payments",
 *     description="Gestion des paiements"
 * )
 */
class PaymentController extends Controller
{
    private \App\Services\StripeService $stripeService;

    public function __construct(\App\Services\StripeService $stripeService)
    {
        $this->stripeService = $stripeService;
    }

    /**
     * Créer une session Checkout pour payer une leçon
     * @OA\Post(
     *     path="/api/payments/create-lesson-checkout",
     *     summary="Créer une session de paiement pour une leçon",
     *     description="Crée une session Stripe Checkout pour payer une leçon spécifique",
     *     tags={"Payments"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"lesson_id"},
     *             @OA\Property(property="lesson_id", type="integer", description="ID de la leçon")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Session créée avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="checkout_url", type="string"),
     *             @OA\Property(property="session_id", type="string")
     *         )
     *     )
     * )
     */
    public function createLessonCheckoutSession(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            $validated = $request->validate([
                'lesson_id' => 'required|exists:lessons,id'
            ]);

            // Vérifier que la leçon existe
            $lesson = Lesson::with('courseType')->findOrFail($validated['lesson_id']);

            // Vérifications de base
            if ($user->role === 'student' && $lesson->student_id !== $user->student->id) {
                return response()->json(['success' => false, 'message' => 'Non autorisé'], 403);
            }

            if ($lesson->payment_status === 'paid') {
                return response()->json(['success' => false, 'message' => 'Cette leçon est déjà payée'], 422);
            }

            // URLs de redirection
            $baseUrl = config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:3000'));
            // Idéalement rediriger vers la page de détails de la leçon ou le planning
            $successUrl = $baseUrl . '/student/dashboard?payment=success&lesson_id=' . $lesson->id;
            $cancelUrl = $baseUrl . '/student/dashboard?payment=cancelled&lesson_id=' . $lesson->id;

            $session = $this->stripeService->createCheckoutSessionForLesson(
                $user,
                $lesson,
                $successUrl,
                $cancelUrl,
                null, // priceOverride
                false // isTrial
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
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Erreur createLessonCheckoutSession: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * @OA\Get(
     *     path="/api/payments",
     *     summary="Liste des paiements",
     *     description="Récupère la liste des paiements en fonction du rôle de l'utilisateur",
     *     operationId="getPayments",
     *     tags={"Payments"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Liste des paiements récupérée avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Payment")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non autorisé"
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $query = Payment::with(['lesson.student.user', 'lesson.teacher.user']);

            // Filtrage basé sur le rôle
            if ($user->role === 'student') {
                $query->whereHas('lesson.student', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
            } elseif ($user->role === 'teacher') {
                $query->whereHas('lesson.teacher', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
            }
            // Les admins voient tous les paiements

            $payments = $query->orderBy('created_at', 'desc')->get();

            return response()->json([
                'success' => true,
                'data' => $payments
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des paiements',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/payments/{id}",
     *     summary="Détails d'un paiement",
     *     description="Récupère les détails d'un paiement spécifique",
     *     operationId="getPayment",
     *     tags={"Payments"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID du paiement",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Détails du paiement",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/Payment")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Paiement non trouvé"
     *     )
     * )
     */
    public function show(Request $request, string $id): JsonResponse
    {
        try {
            $user = $request->user();
            $payment = Payment::with(['lesson.student.user', 'lesson.teacher.user'])->findOrFail($id);

            // Vérifier les autorisations
            if ($user->role === 'student') {
                if ($payment->lesson->student->user_id !== $user->id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Accès non autorisé à ce paiement'
                    ], 403);
                }
            } elseif ($user->role === 'teacher') {
                if ($payment->lesson->teacher->user_id !== $user->id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Accès non autorisé à ce paiement'
                    ], 403);
                }
            }

            return response()->json([
                'success' => true,
                'data' => $payment
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Paiement non trouvé'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du paiement',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/payments",
     *     summary="Créer un paiement",
     *     description="Crée un nouveau paiement pour une leçon",
     *     operationId="createPayment",
     *     tags={"Payments"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"lesson_id", "amount", "payment_method"},
     *             @OA\Property(property="lesson_id", type="integer", description="ID de la leçon"),
     *             @OA\Property(property="amount", type="number", format="float", description="Montant du paiement"),
     *             @OA\Property(property="payment_method", type="string", enum={"card", "bank_transfer", "cash", "paypal"}, description="Méthode de paiement"),
     *             @OA\Property(property="stripe_payment_intent_id", type="string", description="ID de l'intention de paiement Stripe"),
     *             @OA\Property(property="notes", type="string", description="Notes sur le paiement")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Paiement créé avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/Payment"),
     *             @OA\Property(property="message", type="string", example="Paiement créé avec succès")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation"
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'lesson_id' => 'required|exists:lessons,id',
                'amount' => 'required|numeric|min:0',
                'payment_method' => 'required|in:card,bank_transfer,cash,paypal',
                'stripe_payment_intent_id' => 'nullable|string',
                'notes' => 'nullable|string'
            ]);

            $lesson = Lesson::findOrFail($validated['lesson_id']);
            $user = $request->user();

            // Vérifier que l'utilisateur peut créer ce paiement
            if ($user->role === 'student') {
                if ($lesson->student->user_id !== $user->id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Vous ne pouvez pas créer un paiement pour cette leçon'
                    ], 403);
                }
            }

            // Vérifier qu'il n'y a pas déjà un paiement réussi pour cette leçon
            $existingPayment = Payment::where('lesson_id', $validated['lesson_id'])
                ->where('status', 'succeeded')
                ->first();

            if ($existingPayment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Un paiement a déjà été effectué pour cette leçon'
                ], 422);
            }

            $payment = Payment::create($validated + [
                'status' => 'pending',
                'currency' => 'EUR'
            ]);

            return response()->json([
                'success' => true,
                'data' => $payment->load(['lesson.student.user', 'lesson.teacher.user']),
                'message' => 'Paiement créé avec succès'
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du paiement',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/payments/{id}",
     *     summary="Mettre à jour un paiement",
     *     description="Met à jour le statut d'un paiement (réservé aux admins et processus automatiques)",
     *     operationId="updatePayment",
     *     tags={"Payments"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID du paiement",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", enum={"pending", "processing", "succeeded", "failed", "canceled"}, description="Statut du paiement"),
     *             @OA\Property(property="stripe_payment_intent_id", type="string", description="ID de l'intention de paiement Stripe"),
     *             @OA\Property(property="failure_reason", type="string", description="Raison de l'échec"),
     *             @OA\Property(property="notes", type="string", description="Notes sur le paiement")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Paiement mis à jour avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/Payment"),
     *             @OA\Property(property="message", type="string", example="Paiement mis à jour avec succès")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Accès non autorisé"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Paiement non trouvé"
     *     )
     * )
     */
    public function update(Request $request, string $id): JsonResponse
    {
        // Seuls les admins peuvent mettre à jour les paiements manuellement
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé. Seuls les administrateurs peuvent modifier les paiements.'
            ], 403);
        }

        try {
            $payment = Payment::findOrFail($id);

            $validated = $request->validate([
                'status' => 'sometimes|required|in:pending,processing,succeeded,failed,canceled',
                'stripe_payment_intent_id' => 'nullable|string',
                'failure_reason' => 'nullable|string',
                'notes' => 'nullable|string'
            ]);

            $payment->update($validated);

            // Si le paiement est marqué comme réussi, mettre à jour le statut de la leçon
            if (isset($validated['status']) && $validated['status'] === 'succeeded') {
                $payment->lesson->update(['payment_status' => 'paid']);
            }

            return response()->json([
                'success' => true,
                'data' => $payment->fresh(['lesson.student.user', 'lesson.teacher.user']),
                'message' => 'Paiement mis à jour avec succès'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Paiement non trouvé'
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du paiement',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/payments/{id}",
     *     summary="Supprimer un paiement",
     *     description="Supprime un paiement (réservé aux admins)",
     *     operationId="deletePayment",
     *     tags={"Payments"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID du paiement",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Paiement supprimé avec succès"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Accès non autorisé"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Paiement non trouvé"
     *     )
     * )
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        // Seuls les admins peuvent supprimer des paiements
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé. Seuls les administrateurs peuvent supprimer des paiements.'
            ], 403);
        }

        try {
            $payment = Payment::findOrFail($id);

            // Ne pas permettre la suppression de paiements réussis
            if ($payment->status === 'succeeded') {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de supprimer un paiement réussi'
                ], 422);
            }

            $payment->delete();

            return response()->json(null, 204);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Paiement non trouvé'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression du paiement',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
