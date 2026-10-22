<?php

declare(strict_types=1);

namespace Tests\Feature\Payment;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\Payment\PaymentGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class PaymentTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['phone_verified_at' => now(), 'preferred_language' => 'en']);
        $this->actingAs($this->user, 'api');
    }

    private function pendingOrder(): Order
    {
        return Order::factory()->withItems(2)->for($this->user)->create();
    }

    private function pay(Order $order, string $method = 'credit_card')
    {
        return $this->postJson("/api/v1/orders/{$order->id}/payments", ['payment_method' => $method]);
    }

    public function test_a_successful_payment_confirms_the_order(): void
    {
        $order = $this->pendingOrder();

        $this->pay($order)
            ->assertCreated()
            ->assertJsonPath('message', __('messages.payment.processed'))
            ->assertJsonPath('data.status', 'successful')
            ->assertJsonPath('data.payment_method', 'credit_card')
            ->assertJsonPath('data.amount', (string) $order->total_amount);

        $payment = Payment::query()->sole();
        $this->assertSame(26, strlen($payment->reference));
        $this->assertNotNull($payment->gateway_reference);
        $this->assertSame(OrderStatus::Confirmed, $order->fresh()->status);
        $this->assertNotNull($order->fresh()->paid_at);
    }

    public function test_it_records_a_failed_payment_and_leaves_the_order_unpaid(): void
    {
        config(['payment.gateways.paypal.simulate' => 'fail']);
        $order = $this->pendingOrder();

        $this->pay($order, 'paypal')
            ->assertCreated()
            ->assertJsonPath('message', __('messages.payment.failed'))
            ->assertJsonPath('data.status', 'failed');

        $this->assertSame(OrderStatus::Pending, $order->fresh()->status);
        $this->assertNull($order->fresh()->paid_at);
    }

    public function test_it_allows_a_new_payment_after_a_failed_one(): void
    {
        $order = $this->pendingOrder();
        Payment::factory()->failed()->for($order)->create();

        $this->pay($order)->assertCreated()->assertJsonPath('data.status', 'successful');
    }

    public function test_a_gateway_timeout_leaves_the_payment_pending_with_202(): void
    {
        config(['payment.gateways.credit_card.simulate' => 'timeout']);
        $order = $this->pendingOrder();

        $this->pay($order)
            ->assertStatus(202)
            ->assertJsonPath('message', __('messages.payment.processing'))
            ->assertJsonPath('data.status', 'pending');

        $this->assertNull($order->fresh()->paid_at);
    }

    public function test_it_does_not_pay_a_cancelled_order(): void
    {
        $order = Order::factory()->cancelled()->for($this->user)->create();

        $this->pay($order)
            ->assertUnprocessable()
            ->assertJsonPath('message', __('messages.payment.order_not_payable'));

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_it_does_not_pay_an_order_twice(): void
    {
        $order = Order::factory()->confirmed()->for($this->user)->create();

        $this->pay($order)
            ->assertStatus(409)
            ->assertJsonPath('message', __('messages.payment.order_already_paid'));
    }

    public static function activeStatuses(): array
    {
        return [
            'pending' => ['pending'],
        ];
    }

    #[DataProvider('activeStatuses')]
    public function test_it_blocks_a_new_payment_while_one_is_active(string $status): void
    {
        $order = $this->pendingOrder();
        Payment::factory()->for($order)->create(['status' => $status]);

        $this->pay($order)
            ->assertStatus(409)
            ->assertJsonPath('message', __('messages.payment.already_active'));

        $this->assertDatabaseCount('payments', 1);
    }

    public function test_it_rejects_an_unsupported_payment_method(): void
    {
        $order = $this->pendingOrder();

        $this->pay($order, 'bitcoin')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('payment_method');
    }

    public function test_it_returns_404_when_paying_another_user_order(): void
    {
        $order = Order::factory()->create();

        $this->pay($order)->assertNotFound();

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_a_gateway_added_in_config_works_end_to_end(): void
    {
        config(['payment.gateways.wallet' => ['driver' => WalletGateway::class]]);
        $order = $this->pendingOrder();

        $this->pay($order, 'wallet')
            ->assertCreated()
            ->assertJsonPath('data.payment_method', 'wallet')
            ->assertJsonPath('data.status', 'successful')
            ->assertJsonPath('data.gateway_reference', 'wallet_1');

        $this->assertNotNull($order->fresh()->paid_at);
    }

    public function test_it_lists_only_the_user_payments(): void
    {
        Payment::factory()->count(3)->failed()->for($this->pendingOrder())->create();
        Payment::factory()->failed()->create();

        $this->getJson('/api/v1/payments?per_page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('pagination.total', 3);
    }

    public function test_it_shows_the_order_with_its_payments(): void
    {
        $order = $this->pendingOrder();
        Payment::factory()->failed()->for($order)->create();
        Payment::factory()->failed()->for($this->pendingOrder())->create();

        $this->getJson("/api/v1/orders/{$order->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $order->id)
            ->assertJsonPath('data.user.id', $this->user->id)
            ->assertJsonCount(1, 'data.payments')
            ->assertJsonPath('data.payments.0.order_id', $order->id);
    }

    public function test_it_shows_only_the_user_payment(): void
    {
        $mine = Payment::factory()->failed()->for($this->pendingOrder())->create();
        $other = Payment::factory()->failed()->create();

        $this->getJson("/api/v1/payments/{$mine->id}")->assertOk()->assertJsonPath('data.id', $mine->id);
        $this->getJson("/api/v1/payments/{$other->id}")->assertNotFound();
        $this->getJson("/api/v1/orders/{$other->order_id}")->assertNotFound();
    }
}

final class WalletGateway implements PaymentGateway
{
    public function charge(Payment $payment): array
    {
        return ['status' => PaymentStatus::Successful, 'gateway_reference' => 'wallet_1'];
    }

    public function verify(Payment $payment): array
    {
        return ['status' => PaymentStatus::Successful, 'gateway_reference' => 'wallet_1'];
    }
}
