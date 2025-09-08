<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @OA\Schema(
 *     schema="Payment",
 *     type="object",
 *     title="Payment",
 *     description="Modèle de paiement",
 *     @OA\Property(property="id", type="integer", description="ID du paiement"),
 *     @OA\Property(property="lesson_id", type="integer", description="ID de la leçon"),
 *     @OA\Property(property="amount", type="number", format="float", description="Montant du paiement"),
 *     @OA\Property(property="currency", type="string", description="Devise (EUR, USD, etc.)"),
 *     @OA\Property(property="payment_method", type="string", enum={"card", "bank_transfer", "cash", "paypal"}, description="Méthode de paiement"),
 *     @OA\Property(property="status", type="string", enum={"pending", "processing", "succeeded", "failed", "canceled"}, description="Statut du paiement"),
 *     @OA\Property(property="stripe_payment_intent_id", type="string", description="ID de l'intention de paiement Stripe"),
 *     @OA\Property(property="failure_reason", type="string", description="Raison de l'échec du paiement"),
 *     @OA\Property(property="notes", type="string", description="Notes sur le paiement"),
 *     @OA\Property(property="processed_at", type="string", format="date-time", description="Date de traitement du paiement"),
 *     @OA\Property(property="created_at", type="string", format="date-time", description="Date de création"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", description="Date de mise à jour"),
 *     @OA\Property(property="lesson", ref="#/components/schemas/Lesson", description="Leçon associée")
 * )
 */
class Payment extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'lesson_id',
        'student_id',
        'amount',
        'currency',
        'payment_method',
        'status',
        'stripe_payment_intent_id',
        'failure_reason',
        'notes',
        'processed_at'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'processed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Payment statuses
     */
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_SUCCEEDED = 'succeeded';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELED = 'canceled';

    /**
     * Payment methods
     */
    const METHOD_CARD = 'card';
    const METHOD_BANK_TRANSFER = 'bank_transfer';
    const METHOD_CASH = 'cash';
    const METHOD_PAYPAL = 'paypal';

    /**
     * Get the lesson that this payment is for.
     */
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    /**
     * Check if payment is successful
     */
    public function isSuccessful(): bool
    {
        return $this->status === self::STATUS_SUCCEEDED;
    }

    /**
     * Check if payment is pending
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if payment has failed
     */
    public function hasFailed(): bool
    {
        return in_array($this->status, [self::STATUS_FAILED, self::STATUS_CANCELED]);
    }

    /**
     * Get formatted amount with currency
     */
    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount, 2) . ' ' . $this->currency;
    }
}
