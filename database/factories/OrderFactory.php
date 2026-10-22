<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'status' => OrderStatus::Pending,
            'total_amount' => fake()->randomFloat(2, 1, 500),
            'currency' => config('payment.currency', 'KWD'),
            'paid_at' => null,
        ];
    }

    /**
     * An order is confirmed by a successful payment, so confirmed means paid.
     */
    public function confirmed(): static
    {
        return $this->state(fn () => ['status' => OrderStatus::Confirmed, 'paid_at' => now()]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => ['status' => OrderStatus::Cancelled]);
    }

    /**
     * Attach items and recompute total_amount from them.
     */
    public function withItems(int $count = 2): static
    {
        return $this->afterCreating(function (Order $order) use ($count): void {
            $items = OrderItem::factory()->count($count)->for($order)->create();

            $cents = $items->sum(fn (OrderItem $item) => (int) round((float) $item->price * 100) * $item->quantity);

            $order->update(['total_amount' => number_format($cents / 100, 2, '.', '')]);
        });
    }
}
