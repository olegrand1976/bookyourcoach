<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\StripeService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * @OA\Tag(
 *     name="Stripe Webhooks",
 *     description="Gestion des webhooks Stripe"
 * )
 */
class StripeWebhookController extends Controller
{
    private StripeService $stripeService;

    public function __construct(StripeService $stripeService)
    {
        $this->stripeService = $stripeService;
    }

    /**
     * @OA\Post(
     *     path="/api/stripe/webhook",
     *     summary="Webhook Stripe",
     *     description="Endpoint pour recevoir les notifications de Stripe",
     *     operationId="stripeWebhook",
     *     tags={"Stripe Webhooks"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             description="Payload du webhook Stripe",
     *             type="object"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Webhook traité avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Webhook invalide"
     *     )
     * )
     */
    public function handleWebhook(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');

        // Valider la signature du webhook
        if (!$this->stripeService->validateWebhook($payload, $signature)) {
            Log::warning('Webhook Stripe avec signature invalide');
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        $event = json_decode($payload, true);

        if (!$event) {
            Log::warning('Webhook Stripe avec payload invalide');
            return response()->json(['error' => 'Invalid payload'], 400);
        }

        Log::info('Webhook Stripe reçu', [
            'type' => $event['type'],
            'id' => $event['id']
        ]);

        // Traiter l'événement
        $processed = $this->stripeService->handleWebhook($event);

        if ($processed) {
            return response()->json(['status' => 'success']);
        } else {
            return response()->json(['status' => 'error'], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/stripe/create-payment-intent",
     *     summary="Créer une intention de paiement",
     *     description="Crée une intention de paiement Stripe pour une leçon",
     *     operationId="createPaymentIntent",
     *     tags={"Payments"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"lesson_id"},
     *             @OA\Property(property="lesson_id", type="integer", description="ID de la leçon"),
     *             @OA\Property(property="payment_method_types", type="array", @OA\Items(type="string"), description="Types de paiement acceptés")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Intention de paiement créée",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="client_secret", type="string", description="Secret client pour le paiement"),
     *             @OA\Property(property="payment_intent_id", type="string", description="ID de l'intention de paiement")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation"
     *     )
     * )
     */
    public function createPaymentIntent(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'lesson_id' => 'required|exists:lessons,id',
            'payment_method_types' => 'array'
        ]);

        try {
            $lesson = \App\Models\Lesson::with(['student.user', 'courseType'])->findOrFail($validated['lesson_id']);
            $user = $request->user();

            // Vérifier que l'utilisateur peut payer pour cette leçon
            if ($user->role === 'student' && $lesson->student->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous ne pouvez pas payer pour cette leçon'
                ], 403);
            }

            // Créer ou récupérer le customer Stripe
            $customerId = $this->stripeService->createCustomer($user);

            $amount = $lesson->price ?? $lesson->courseType->price ?? 50.00;

            $paymentIntent = $this->stripeService->createPaymentIntent(
                $amount,
                'eur',
                $customerId,
                [
                    'lesson_id' => $lesson->id,
                    'user_id' => $user->id,
                    'teacher_id' => $lesson->teacher_id
                ]
            );

            if (!$paymentIntent) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de la création de l\'intention de paiement'
                ], 500);
            }

            // Créer l'enregistrement Payment
            $payment = \App\Models\Payment::create([
                'lesson_id' => $lesson->id,
                'amount' => $amount,
                'currency' => 'EUR',
                'payment_method' => 'card',
                'status' => 'pending',
                'stripe_payment_intent_id' => $paymentIntent->id
            ]);

            return response()->json([
                'success' => true,
                'client_secret' => $paymentIntent->client_secret,
                'payment_intent_id' => $paymentIntent->id,
                'payment_id' => $payment->id,
                'amount' => $amount
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur création PaymentIntent', [
                'user_id' => $request->user()->id,
                'lesson_id' => $validated['lesson_id'],
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du paiement',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
