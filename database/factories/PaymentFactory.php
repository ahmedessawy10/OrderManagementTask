<?php

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * The payment copies the amount and currency of its order.
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'reference' => fn () => (string) Str::ulid(),
            'amount' => fn (array $attributes) => Order::find($attributes['order_id'])->total_amount,
            'currency' => fn (array $attributes) => Order::find($attributes['order_id'])->currency,
            'method' => 'credit_card',
            'status' => PaymentStatus::Pending,
            'gateway_reference' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => ['status' => PaymentStatus::Pending]);
    }

    public function successful(): static
    {
        return $this->state(fn () => ['status' => PaymentStatus::Successful, 'gateway_reference' => 'credit_card_'.Str::random(20)]);
    }

    public function failed(): static
    {
        return $this->state(fn () => ['status' => PaymentStatus::Failed, 'gateway_reference' => 'credit_card_'.Str::random(20)]);
    }
}
