<?php

namespace App\Services;

use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Customer;
use Stripe\Checkout\Session;
use Stripe\Exception\ApiErrorException;
use App\Models\User;
use App\Models\Payment;
use App\Jobs\GenerateInvoiceJob;
use App\Notifications\PaymentConfirmedNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class StripeService
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /**
     * Créer un customer Stripe
     */
    public function createCustomer(User $user): ?string
    {
        try {
            $customer = Customer::create([
                'email' => $user->email,
                'name' => $user->profile ? $user->profile->full_name : $user->email,
                'metadata' => [
                    'user_id' => $user->id,
                    'role' => $user->role
                ]
            ]);

            return $customer->id;
        } catch (ApiErrorException $e) {
            Log::error('Erreur création customer Stripe', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Créer une intention de paiement
     */
    public function createPaymentIntent(
        float $amount,
        string $currency = 'eur',
        ?string $customerId = null,
        array $metadata = []
    ): ?PaymentIntent {
        try {
            $paymentIntentData = [
                'amount' => (int) ($amount * 100), // Stripe utilise les centimes
                'currency' => $currency,
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
                'metadata' => $metadata
            ];

            if ($customerId) {
                $paymentIntentData['customer'] = $customerId;
            }

            $paymentIntent = PaymentIntent::create($paymentIntentData);

            return $paymentIntent;
        } catch (ApiErrorException $e) {
            Log::error('Erreur création PaymentIntent', [
                'amount' => $amount,
                'currency' => $currency,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Récupérer une intention de paiement
     */
    public function retrievePaymentIntent(string $paymentIntentId): ?PaymentIntent
    {
        try {
            return PaymentIntent::retrieve($paymentIntentId);
        } catch (ApiErrorException $e) {
            Log::error('Erreur récupération PaymentIntent', [
                'payment_intent_id' => $paymentIntentId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Confirmer un paiement
     */
    public function confirmPaymentIntent(string $paymentIntentId, string $paymentMethod): ?PaymentIntent
    {
        try {
            $paymentIntent = PaymentIntent::retrieve($paymentIntentId);

            return $paymentIntent->confirm([
                'payment_method' => $paymentMethod
            ]);
        } catch (ApiErrorException $e) {
            Log::error('Erreur confirmation PaymentIntent', [
                'payment_intent_id' => $paymentIntentId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Annuler une intention de paiement
     */
    public function cancelPaymentIntent(string $paymentIntentId): ?PaymentIntent
    {
        try {
            $paymentIntent = PaymentIntent::retrieve($paymentIntentId);

            if ($paymentIntent->status === 'requires_payment_method' || $paymentIntent->status === 'requires_confirmation') {
                return $paymentIntent->cancel();
            }

            return $paymentIntent;
        } catch (ApiErrorException $e) {
            Log::error('Erreur annulation PaymentIntent', [
                'payment_intent_id' => $paymentIntentId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Traiter le webhook Stripe
     */
    public function handleWebhook(array $event): bool
    {
        try {
            switch ($event['type']) {
                case 'payment_intent.succeeded':
                    $this->handlePaymentSucceeded($event['data']['object']);
                    break;
                case 'payment_intent.payment_failed':
                    $this->handlePaymentFailed($event['data']['object']);
                    break;
                case 'payment_intent.canceled':
                    $this->handlePaymentCanceled($event['data']['object']);
                    break;
                case 'checkout.session.completed':
                    $this->handleCheckoutSessionCompleted($event['data']['object']);
                    break;
                default:
                    Log::info('Événement Stripe non géré', ['type' => $event['type']]);
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Erreur traitement webhook Stripe', [
                'event_type' => $event['type'],
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Gérer une session Checkout complétée (paiement réussi)
     */
    private function handleCheckoutSessionCompleted(array $session): void
    {
        try {
            $subscriptionInstance = $this->ensureSubscriptionCreatedFromCheckoutSession($session);
            if (!$subscriptionInstance) {
                return;
            }
            Log::info('Abonnement créé automatiquement après paiement Stripe', [
                'session_id' => $session['id'],
                'subscription_id' => $subscriptionInstance->subscription_id,
                'subscription_instance_id' => $subscriptionInstance->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la création de l\'abonnement après paiement Stripe', [
                'session_id' => $session['id'] ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    public function retrieveCheckoutSession(string $sessionId): ?Session
    {
        try {
            return Session::retrieve($sessionId);
        } catch (ApiErrorException $e) {
            Log::error('Erreur récupération session Stripe Checkout', [
                'session_id' => $sessionId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    public function ensureSubscriptionCreatedFromCheckoutSession(array $session): ?\App\Models\SubscriptionInstance
    {
        $metadata = $session['metadata'] ?? [];
        $sessionId = $session['id'] ?? null;

        if (($metadata['type'] ?? '') !== 'subscription') {
            Log::info('Session Checkout non liée à un abonnement', [
                'session_id' => $sessionId
            ]);
            return null;
        }

        if (($session['payment_status'] ?? null) !== 'paid') {
            Log::warning('Paiement Stripe non confirmé pour la session Checkout', [
                'session_id' => $sessionId,
                'payment_status' => $session['payment_status'] ?? null,
                'status' => $session['status'] ?? null,
            ]);
            return null;
        }

        if ($sessionId) {
            $existingInstance = \App\Models\SubscriptionInstance::where('stripe_checkout_session_id', $sessionId)->first();
            if ($existingInstance) {
                return $existingInstance->load([
                    'subscription.club',
                    'subscription.template.courseTypes',
                    'students.user'
                ]);
            }
        }

        $userId = $metadata['user_id'] ?? null;
        $templateId = $metadata['subscription_template_id'] ?? null;
        $studentId = $metadata['student_id'] ?? null;

        if (!$userId || !$templateId || !$studentId) {
            Log::error('Métadonnées manquantes dans la session Checkout', [
                'session_id' => $sessionId,
                'metadata' => $metadata
            ]);
            return null;
        }

        $user = User::find($userId);
        $student = \App\Models\Student::find($studentId);
        $template = \App\Models\SubscriptionTemplate::find($templateId);

        if (!$user || !$student || !$template) {
            Log::error('Ressources non trouvées pour créer l\'abonnement', [
                'user_id' => $userId,
                'student_id' => $studentId,
                'template_id' => $templateId
            ]);
            return null;
        }

        return DB::transaction(function () use ($template, $student, $sessionId) {
            $subscription = \App\Models\Subscription::createSafe([
                'club_id' => $template->club_id,
                'subscription_template_id' => $template->id,
            ]);

            $subscriptionInstance = \App\Models\SubscriptionInstance::create([
                'subscription_id' => $subscription->id,
                'lessons_used' => 0,
                'started_at' => \Carbon\Carbon::now(),
                'expires_at' => null,
                'status' => 'active',
                'stripe_checkout_session_id' => $sessionId,
            ]);

            $subscriptionInstance->calculateExpiresAt();
            $subscriptionInstance->save();
            $subscriptionInstance->students()->attach($student->id);

            return $subscriptionInstance->load([
                'subscription.club',
                'subscription.template.courseTypes',
                'students.user'
            ]);
        });
    }

    /**
     * Gérer un paiement réussi
     */
    private function handlePaymentSucceeded(array $paymentIntent): void
    {
        $payment = Payment::where('stripe_payment_intent_id', $paymentIntent['id'])->first();

        if ($payment) {
            $payment->update([
                'status' => Payment::STATUS_SUCCEEDED,
                'processed_at' => now()
            ]);

            // Mettre à jour le statut de la leçon
            $payment->lesson->update([
                'payment_status' => 'paid'
            ]);

            // Envoyer une notification de confirmation de paiement
            if ($payment->lesson && $payment->lesson->student && $payment->lesson->student->user) {
                $payment->lesson->student->user->notify(new PaymentConfirmedNotification($payment));
            }

            // Programmer la génération de facture
            GenerateInvoiceJob::dispatch($payment)->delay(now()->addMinutes(5));

            Log::info('Paiement réussi traité', [
                'payment_id' => $payment->id,
                'lesson_id' => $payment->lesson_id
            ]);
        }
    }

    /**
     * Gérer un paiement échoué
     */
    private function handlePaymentFailed(array $paymentIntent): void
    {
        $payment = Payment::where('stripe_payment_intent_id', $paymentIntent['id'])->first();

        if ($payment) {
            $payment->update([
                'status' => Payment::STATUS_FAILED,
                'failure_reason' => $paymentIntent['last_payment_error']['message'] ?? 'Paiement échoué',
                'processed_at' => now()
            ]);

            Log::info('Paiement échoué traité', [
                'payment_id' => $payment->id,
                'error' => $paymentIntent['last_payment_error']['message'] ?? 'Inconnu'
            ]);
        }
    }

    /**
     * Gérer un paiement annulé
     */
    private function handlePaymentCanceled(array $paymentIntent): void
    {
        $payment = Payment::where('stripe_payment_intent_id', $paymentIntent['id'])->first();

        if ($payment) {
            $payment->update([
                'status' => Payment::STATUS_CANCELED,
                'processed_at' => now()
            ]);

            Log::info('Paiement annulé traité', [
                'payment_id' => $payment->id
            ]);
        }
    }

    /**
     * Obtenir les frais Stripe pour un montant
     */
    public function calculateStripeFees(float $amount): array
    {
        // Frais Stripe en Europe : 1.4% + 0.25€ pour les cartes européennes
        $percentage = 0.014;
        $fixed = 0.25;

        $fees = ($amount * $percentage) + $fixed;
        $netAmount = $amount - $fees;

        return [
            'gross_amount' => $amount,
            'fees' => round($fees, 2),
            'net_amount' => round($netAmount, 2)
        ];
    }

    /**
     * Valider un webhook Stripe
     */
    public function validateWebhook(string $payload, string $signature): bool
    {
        $webhookSecret = config('services.stripe.webhook.secret');

        if (!$webhookSecret) {
            return false;
        }

        try {
            \Stripe\Webhook::constructEvent(
                $payload,
                $signature,
                $webhookSecret
            );
            return true;
        } catch (\Exception $e) {
            Log::error('Validation webhook Stripe échouée', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Construit la description du produit pour Stripe Checkout (évite les erreurs sprintf avec null)
     */
    private function buildCheckoutDescription(\App\Models\SubscriptionTemplate $template): string
    {
        $total = (int) ($template->total_lessons ?? 0);
        $free = (int) ($template->free_lessons ?? 0);
        $months = (int) ($template->validity_months ?? 12);
        $parts = [$total . ' cours'];
        if ($free > 0) {
            $parts[] = sprintf('+ %d gratuit%s', $free, $free > 1 ? 's' : '');
        }
        $parts[] = sprintf('Validité: %d mois', $months);
        return implode(' - ', $parts);
    }

    /**
     * Créer une session Stripe Checkout pour un abonnement
     */
    public function createCheckoutSession(
        User $user,
        \App\Models\SubscriptionTemplate $template,
        string $successUrl,
        string $cancelUrl,
        array $metadata = []
    ): ?Session {
        $secret = config('services.stripe.secret');
        if (empty($secret)) {
            Log::error('Stripe: STRIPE_SECRET non configuré (vérifier .env en production)');
            return null;
        }

        try {
            // Créer ou récupérer le customer Stripe
            $customerId = $this->createCustomer($user);
            
            if (!$customerId) {
                Log::error('Impossible de créer le customer Stripe', [
                    'user_id' => $user->id
                ]);
                return null;
            }

            $sessionData = [
                'customer' => $customerId,
                'payment_method_types' => ['card'],
                'mode' => 'payment',
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
                'metadata' => array_merge([
                    'user_id' => $user->id,
                    'subscription_template_id' => $template->id,
                    'club_id' => $template->club_id,
                    'type' => 'subscription'
                ], $metadata),
            ];

            if (!empty($template->stripe_price_id)) {
                $sessionData['line_items'] = [[
                    'price' => $template->stripe_price_id,
                    'quantity' => 1,
                ]];
            } else {
                $sessionData['line_items'] = [[
                    'price_data' => [
                        'currency' => 'eur',
                        'unit_amount' => (int) round(($template->price ?? 0) * 100), // Stripe: centimes
                    ],
                    'quantity' => 1,
                ]];

                if (!empty($template->stripe_product_id)) {
                    $sessionData['line_items'][0]['price_data']['product'] = $template->stripe_product_id;
                } else {
                    $sessionData['line_items'][0]['price_data']['product_data'] = [
                        'name' => $template->model_number ?? 'Abonnement',
                        'description' => $this->buildCheckoutDescription($template),
                    ];
                }
            }

            $session = Session::create($sessionData);

            Log::info('Session Stripe Checkout créée', [
                'session_id' => $session->id,
                'user_id' => $user->id,
                'template_id' => $template->id
            ]);

            return $session;
        } catch (ApiErrorException $e) {
            Log::error('Erreur création session Stripe Checkout', [
                'user_id' => $user->id,
                'template_id' => $template->id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
}
