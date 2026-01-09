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
    public function handleCheckoutSessionCompleted(array $session): void
    {
        $metadata = $session['metadata'] ?? [];

        try {
            // Vérifier le type de paiement
            $type = $metadata['type'] ?? '';

            if ($type === 'lesson_payment') {
                $this->handleLessonPaymentSession($session);
                return;
            }

            // Pour compatibilité, 'subscription' est traité comme 'pack_purchase' ou 'recurring_subscription'
            if (!in_array($type, ['subscription', 'recurring_subscription', 'pack_purchase'])) {
                Log::info('Session Checkout ignorée (type non géré)', [
                    'session_id' => $session['id'],
                    'type' => $type
                ]);
                return;
            }

            $userId = $metadata['user_id'] ?? null;
            $templateId = $metadata['subscription_template_id'] ?? null;
            $studentId = $metadata['student_id'] ?? null;

            if (!$userId || !$templateId || !$studentId) {
                Log::error('Métadonnées manquantes dans la session Checkout', [
                    'session_id' => $session['id'],
                    'metadata' => $metadata
                ]);
                return;
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
                return;
            }

            // Créer l'abonnement
            \Illuminate\Support\Facades\DB::beginTransaction();

            // Créer l'abonnement depuis le template
            $subscription = \App\Models\Subscription::createSafe([
                'club_id' => $template->club_id,
                'subscription_template_id' => $template->id,
            ]);

            // Créer l'instance d'abonnement
            $subscriptionInstance = \App\Models\SubscriptionInstance::create([
                'subscription_id' => $subscription->id,
                'lessons_used' => 0,
                'started_at' => \Carbon\Carbon::now(),
                'expires_at' => null, // Sera calculé automatiquement
                'status' => 'active'
            ]);

            // Calculer la date d'expiration
            $subscriptionInstance->calculateExpiresAt();
            
            // Si c'est un abonnement récurrent, enregistrer le subscription ID Stripe
            if ($session['subscription'] ?? false) {
                $subscriptionInstance->stripe_subscription_id = $session['subscription'];
                $subscriptionInstance->auto_renew = true;
            }
            
            $subscriptionInstance->save();

            // Attacher l'élève
            $subscriptionInstance->students()->attach($student->id);

            \Illuminate\Support\Facades\DB::commit();

            Log::info('Abonnement/Pack créé avec succès via Stripe', [
                'session_id' => $session['id'],
                'subscription_id' => $subscription->id,
                'type' => $type
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            Log::error('Erreur lors de la création de l\'abonnement après paiement Stripe', [
                'session_id' => $session['id'] ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Gérer le paiement d'une leçon via Checkout
     */
    private function handleLessonPaymentSession(array $session): void
    {
        $metadata = $session['metadata'] ?? [];
        $lessonId = $metadata['lesson_id'] ?? null;

        if (!$lessonId) {
            Log::error('Lesson ID manquant dans session lesson_payment', ['session_id' => $session['id']]);
            return;
        }

        $lesson = \App\Models\Lesson::find($lessonId);
        if (!$lesson) {
            Log::error('Leçon non trouvée pour paiement session', ['lesson_id' => $lessonId]);
            return;
        }

        // Créer ou mettre à jour le paiement
        $payment = Payment::firstOrCreate(
            ['stripe_payment_intent_id' => $session['payment_intent'] ?? $session['id']], // Fallback ID si payment_intent null (mode setup)
            [
                'lesson_id' => $lesson->id,
                'student_id' => $metadata['student_id'] ?? null,
                'amount' => $session['amount_total'] / 100,
                'currency' => $session['currency'],
                'status' => Payment::STATUS_SUCCEEDED,
                'payment_method' => 'card',
                'processed_at' => now()
            ]
        );
        
        // Si le paiement existait déjà (ex: créé via intent), le mettre à jour
        if (!$payment->wasRecentlyCreated) {
            $payment->update([
                'status' => Payment::STATUS_SUCCEEDED,
                'processed_at' => now()
            ]);
        }

        // Mettre à jour la leçon
        $lesson->update([
            'payment_status' => 'paid',
            // Si la leçon était pending (nouvelle réservation), la confirmer automatiquement
            'status' => ($lesson->status === 'pending') ? 'confirmed' : $lesson->status
        ]);

        Log::info('Paiement leçon validé via Checkout Session', ['lesson_id' => $lesson->id]);
    }

    /**
     * Créer une session Checkout pour une leçon unique
     */
    public function createCheckoutSessionForLesson(
        User $user,
        \App\Models\Lesson $lesson,
        string $successUrl,
        string $cancelUrl
    ): ?Session {
        try {
            $customerId = $this->createCustomer($user);
            if (!$customerId) return null;

            $price = $lesson->price > 0 ? $lesson->price : 0;
            if ($price <= 0) return null; // Pas de paiement pour gratuit

            $session = Session::create([
                'customer' => $customerId,
                'payment_method_types' => ['card'],
                'mode' => 'payment',
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'eur',
                        'product_data' => [
                            'name' => 'Cours de ' . ($lesson->courseType->name ?? 'Sport'),
                            'description' => 'Le ' . \Carbon\Carbon::parse($lesson->start_time)->format('d/m/Y H:i'),
                        ],
                        'unit_amount' => (int) ($price * 100),
                    ],
                    'quantity' => 1,
                ]],
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
                'metadata' => [
                    'user_id' => $user->id,
                    'lesson_id' => $lesson->id,
                    'student_id' => $lesson->student_id,
                    'club_id' => $lesson->club_id,
                    'type' => 'lesson_payment' // Marqueur important
                ],
            ]);

            return $session;
        } catch (ApiErrorException $e) {
            Log::error('Erreur createCheckoutSessionForLesson', ['error' => $e->getMessage()]);
            return null;
        }
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
     * Créer une session Stripe Checkout pour un abonnement
     */
    public function createCheckoutSession(
        User $user,
        \App\Models\SubscriptionTemplate $template,
        string $successUrl,
        string $cancelUrl,
        array $metadata = []
    ): ?Session {
        try {
            // Créer ou récupérer le customer Stripe
            $customerId = $this->createCustomer($user);
            
            if (!$customerId) {
                Log::error('Impossible de créer le customer Stripe', [
                    'user_id' => $user->id
                ]);
                return null;
            }

            // Déterminer le mode (payment ou subscription)
            $mode = $template->is_recurring ? 'subscription' : 'payment';
            
            $priceData = [
                'currency' => 'eur',
                'product_data' => [
                    'name' => $template->model_number ?? 'Abonnement',
                    'description' => sprintf(
                        '%d cours%s%s - Validité: %d mois%s',
                        $template->total_lessons,
                        $template->free_lessons > 0 ? sprintf(' + %d gratuit%s', $template->free_lessons, $template->free_lessons > 1 ? 's' : '') : '',
                        $template->validity_months ? sprintf(' - Validité: %d mois', $template->validity_months) : '',
                        $template->is_recurring ? ' (Récurrent)' : ''
                    ),
                ],
                'unit_amount' => (int) ($template->price * 100), // Stripe utilise les centimes
            ];

            // Si le template a un ID de prix Stripe, l'utiliser directement (pour les abonnements récurrents c'est mieux)
            if ($template->stripe_price_id) {
                // Si on a un stripe_price_id, on ne met pas price_data mais directement price
                $lineItem = [
                    'price' => $template->stripe_price_id,
                    'quantity' => 1,
                ];
            } else {
                // Sinon on utilise price_data
                if ($mode === 'subscription') {
                    // Pour les abonnements, il faut définir une récurrence si on crée le prix à la volée
                    $priceData['recurring'] = [
                        'interval' => 'month', // Par défaut mensuel si pas spécifié
                        'interval_count' => 1,
                    ];
                }
                
                $lineItem = [
                    'price_data' => $priceData,
                    'quantity' => 1,
                ];
            }

            $sessionData = [
                'customer' => $customerId,
                'payment_method_types' => ['card'],
                'mode' => $mode,
                'line_items' => [$lineItem],
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
                'metadata' => array_merge([
                    'user_id' => $user->id,
                    'subscription_template_id' => $template->id,
                    'club_id' => $template->club_id,
                    'type' => $template->is_recurring ? 'recurring_subscription' : 'pack_purchase'
                ], $metadata),
            ];

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
