<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\Lesson;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition()
    {
        return [
            'lesson_id' => Lesson::factory(),
            'amount' => $this->faker->randomFloat(2, 25, 100),
            'currency' => 'EUR',
            'payment_method' => $this->faker->randomElement(['card', 'bank_transfer', 'cash', 'paypal']),
            'status' => $this->faker->randomElement(['pending', 'processing', 'succeeded', 'failed', 'canceled']),
            'stripe_payment_intent_id' => $this->faker->optional(0.7)->regexify('pi_[A-Za-z0-9]{24}'),
            'failure_reason' => $this->faker->optional(0.1)->randomElement([
                'insufficient_funds',
                'card_declined',
                'expired_card',
                'incorrect_cvc'
            ]),
            'notes' => $this->faker->optional(0.3)->sentence(),
            'processed_at' => $this->faker->optional(0.8)->dateTimeBetween('-30 days', 'now'),
        ];
    }

    /**
     * Payment with succeeded status
     */
    public function succeeded()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => Payment::STATUS_SUCCEEDED,
                'processed_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
                'failure_reason' => null,
            ];
        });
    }

    /**
     * Payment with pending status
     */
    public function pending()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => Payment::STATUS_PENDING,
                'processed_at' => null,
                'failure_reason' => null,
            ];
        });
    }

    /**
     * Payment with failed status
     */
    public function failed()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => Payment::STATUS_FAILED,
                'processed_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
                'failure_reason' => $this->faker->randomElement([
                    'insufficient_funds',
                    'card_declined',
                    'expired_card',
                    'incorrect_cvc'
                ]),
            ];
        });
    }

    /**
     * Payment by card
     */
    public function byCard()
    {
        return $this->state(function (array $attributes) {
            return [
                'payment_method' => Payment::METHOD_CARD,
                'stripe_payment_intent_id' => 'pi_' . $this->faker->regexify('[A-Za-z0-9]{24}'),
            ];
        });
    }

    /**
     * Payment by cash
     */
    public function byCash()
    {
        return $this->state(function (array $attributes) {
            return [
                'payment_method' => Payment::METHOD_CASH,
                'stripe_payment_intent_id' => null,
            ];
        });
    }

    /**
     * Payment by bank transfer
     */
    public function byBankTransfer()
    {
        return $this->state(function (array $attributes) {
            return [
                'payment_method' => Payment::METHOD_BANK_TRANSFER,
                'stripe_payment_intent_id' => null,
            ];
        });
    }

    /**
     * Payment with specific amount
     */
    public function withAmount($amount)
    {
        return $this->state(function (array $attributes) use ($amount) {
            return [
                'amount' => $amount,
            ];
        });
    }

    /**
     * Payment for specific lesson
     */
    public function forLesson($lesson)
    {
        return $this->state(function (array $attributes) use ($lesson) {
            return [
                'lesson_id' => $lesson->id ?? $lesson,
                'amount' => $lesson->price ?? $this->faker->randomFloat(2, 25, 100),
            ];
        });
    }

    /**
     * Payment with notes
     */
    public function withNotes($notes = null)
    {
        return $this->state(function (array $attributes) use ($notes) {
            return [
                'notes' => $notes ?? $this->faker->paragraph(),
            ];
        });
    }

    /**
     * Recent payment
     */
    public function recent()
    {
        return $this->state(function (array $attributes) {
            return [
                'created_at' => $this->faker->dateTimeBetween('-7 days', 'now'),
                'processed_at' => $this->faker->dateTimeBetween('-7 days', 'now'),
            ];
        });
    }
}
