<?php

declare(strict_types=1);

namespace Tests\Feature\Order;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class OrderTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Product $mug;

    private Product $pen;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['phone_verified_at' => now(), 'preferred_language' => 'en']);
        $this->actingAs($this->user, 'api');

        $this->mug = $this->product('Mug', 'كوب', 19.99);
        $this->pen = $this->product('Pen', 'قلم', 0.01);
    }

    private function product(string $en, string $ar, float $price): Product
    {
        $product = Product::create(['price' => $price]);
        $product->translations()->createMany([
            ['locale' => 'en', 'name' => $en],
            ['locale' => 'ar', 'name' => $ar],
        ]);

        return $product;
    }

    private function items(): array
    {
        return [
            ['product_id' => $this->mug->id, 'quantity' => 3],
            ['product_id' => $this->pen->id, 'quantity' => 1],
        ];
    }

    public function test_it_creates_a_pending_order_with_prices_from_the_products(): void
    {
        $response = $this->postJson('/api/v1/orders', [
            'items' => $this->items(),
            'total_amount' => '1.00',
            'status' => 'confirmed',
        ]);

        $response->assertCreated()
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.total_amount', '59.98')
            ->assertJsonPath('data.currency', 'KWD')
            ->assertJsonPath('data.is_paid', false)
            ->assertJsonPath('data.user.id', $this->user->id)
            ->assertJsonPath('data.items.0.product_id', $this->mug->id)
            ->assertJsonPath('data.items.0.product_name', 'Mug')
            ->assertJsonPath('data.items.0.price', '19.99')
            ->assertJsonPath('data.items.0.subtotal', '59.97')
            ->assertJsonCount(2, 'data.items');

        $this->assertDatabaseHas('orders', ['user_id' => $this->user->id, 'total_amount' => '59.98']);
        $this->assertDatabaseCount('order_items', 2);
    }

    public function test_the_item_keeps_the_price_from_order_time(): void
    {
        $this->postJson('/api/v1/orders', ['items' => $this->items()])->assertCreated();

        $this->mug->update(['price' => 50]);

        $this->assertDatabaseHas('order_items', ['product_id' => $this->mug->id, 'price' => '19.99']);
    }

    public function test_the_item_name_follows_the_request_language(): void
    {
        $order = $this->postJson('/api/v1/orders', ['items' => $this->items()])->json('data.id');

        $this->user->update(['preferred_language' => 'ar']);

        $this->getJson("/api/v1/orders/{$order}")
            ->assertOk()
            ->assertJsonPath('data.items.0.product_name', 'كوب');
    }

    public function test_a_product_that_has_orders_cannot_be_deleted(): void
    {
        $this->postJson('/api/v1/orders', ['items' => $this->items()])->assertCreated();

        $this->expectException(QueryException::class);

        $this->mug->delete();
    }

    public static function invalidPayloads(): array
    {
        return [
            'no items' => [[]],
            'empty items' => [['items' => []]],
            'zero quantity' => [['items' => [['product_id' => 1, 'quantity' => 0]]]],
            'missing product' => [['items' => [['quantity' => 1]]]],
            'unknown product' => [['items' => [['product_id' => 999999, 'quantity' => 1]]]],
            'price is not accepted' => [['items' => [['product_id' => 1, 'quantity' => 1, 'price' => 0.01]]]],
        ];
    }

    public function test_it_rejects_the_same_product_twice(): void
    {
        $this->postJson('/api/v1/orders', ['items' => [
            ['product_id' => $this->mug->id, 'quantity' => 1],
            ['product_id' => $this->mug->id, 'quantity' => 2],
        ]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('items.1.product_id');
    }

    #[DataProvider('invalidPayloads')]
    public function test_it_validates_the_order_items(array $payload): void
    {
        $this->postJson('/api/v1/orders', $payload)
            ->assertUnprocessable()
            ->assertJsonPath('status', false);

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_it_rejects_a_total_bigger_than_the_column(): void
    {
        $gold = $this->product('Gold', 'ذهب', 99999999.99);

        $this->postJson('/api/v1/orders', ['items' => [['product_id' => $gold->id, 'quantity' => 10000]]])
            ->assertUnprocessable()
            ->assertJsonPath('status', false);
    }

    public function test_it_lists_only_the_user_orders_with_pagination(): void
    {
        Order::factory()->count(3)->for($this->user)->create();
        Order::factory()->count(2)->create();

        $this->getJson('/api/v1/orders?per_page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('pagination.total', 3)
            ->assertJsonPath('pagination.last_page', 2);
    }

    public function test_it_filters_orders_by_status(): void
    {
        Order::factory()->for($this->user)->create();
        Order::factory()->confirmed()->for($this->user)->create();

        $this->getJson('/api/v1/orders?filter[status]=confirmed')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'confirmed');
    }

    public function test_it_shows_an_order_with_its_items(): void
    {
        $order = Order::factory()->withItems(2)->for($this->user)->create();

        $this->getJson("/api/v1/orders/{$order->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $order->id)
            ->assertJsonCount(2, 'data.items');
    }

    public static function ownerOnlyRequests(): array
    {
        return [
            'show' => ['getJson', []],
            'update' => ['putJson', ['status' => 'cancelled']],
            'delete' => ['deleteJson', []],
        ];
    }

    #[DataProvider('ownerOnlyRequests')]
    public function test_it_returns_404_for_another_user_order(string $method, array $payload): void
    {
        $order = Order::factory()->create();

        $this->{$method}("/api/v1/orders/{$order->id}", $payload)
            ->assertNotFound()
            ->assertJsonPath('status', false);

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'pending']);
    }

    public function test_it_replaces_the_items_of_a_pending_order(): void
    {
        $order = Order::factory()->withItems(2)->for($this->user)->create();

        $cup = $this->product('Cup', 'فنجان', 5);

        $this->putJson("/api/v1/orders/{$order->id}", ['items' => [['product_id' => $cup->id, 'quantity' => 2]]])
            ->assertOk()
            ->assertJsonPath('data.total_amount', '10.00')
            ->assertJsonCount(1, 'data.items');

        $this->assertDatabaseCount('order_items', 1);
    }

    public function test_it_does_not_edit_the_items_of_a_confirmed_order(): void
    {
        $order = Order::factory()->confirmed()->for($this->user)->create();

        $this->putJson("/api/v1/orders/{$order->id}", ['items' => $this->items()])
            ->assertStatus(409)
            ->assertJsonPath('message', __('messages.order.not_editable'));
    }

    public function test_it_cancels_a_pending_order(): void
    {
        $order = Order::factory()->for($this->user)->create();

        $this->patchJson("/api/v1/orders/{$order->id}", ['status' => 'cancelled'])
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');
    }

    public function test_the_client_cannot_confirm_an_order(): void
    {
        $order = Order::factory()->for($this->user)->create();

        $this->patchJson("/api/v1/orders/{$order->id}", ['status' => 'confirmed'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');

        $this->assertSame(OrderStatus::Pending, $order->fresh()->status);
    }

    public function test_it_does_not_edit_the_items_of_a_cancelled_order(): void
    {
        $order = Order::factory()->cancelled()->for($this->user)->create();

        $this->putJson("/api/v1/orders/{$order->id}", ['items' => $this->items()])
            ->assertStatus(409)
            ->assertJsonPath('message', __('messages.order.not_editable'));
    }

    public function test_it_does_not_cancel_a_paid_order(): void
    {
        $order = Order::factory()->confirmed()->for($this->user)->create();

        $this->patchJson("/api/v1/orders/{$order->id}", ['status' => 'cancelled'])
            ->assertStatus(409);

        $this->assertSame(OrderStatus::Confirmed, $order->fresh()->status);
    }

    public function test_it_deletes_an_order_without_payments(): void
    {
        $order = Order::factory()->withItems(1)->for($this->user)->create();

        $this->deleteJson("/api/v1/orders/{$order->id}")->assertOk();

        $this->assertDatabaseMissing('orders', ['id' => $order->id]);
        $this->assertDatabaseCount('order_items', 0);
    }

    public function test_it_does_not_delete_an_order_that_has_payments(): void
    {
        $order = Order::factory()->for($this->user)->create();
        Payment::factory()->failed()->for($order)->create();

        $this->deleteJson("/api/v1/orders/{$order->id}")
            ->assertStatus(409)
            ->assertJsonPath('message', __('messages.order.has_payments'));

        $this->assertDatabaseHas('orders', ['id' => $order->id]);
    }
}
