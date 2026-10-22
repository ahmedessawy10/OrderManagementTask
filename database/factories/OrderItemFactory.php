<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $price = fake()->randomFloat(2, 1, 100);

        return [
            'order_id' => Order::factory(),
            'product_id' => fn () => Product::create(['price' => $price])->id,
            'quantity' => fake()->numberBetween(1, 5),
            'price' => $price,
        ];
    }
}
