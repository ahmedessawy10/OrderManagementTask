<?php

declare(strict_types=1);

namespace Tests\Feature\Payment;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

final class CheckPendingPaymentsTest extends TestCase
{
    use RefreshDatabase;

    private function stalePayment(int $minutesAgo = 15): Payment
    {
        return Payment::factory()->pending()->create(['created_at' => now()->subMinutes($minutesAgo)]);
    }

    public function test_it_confirms_the_order_when_the_gateway_says_successful(): void
    {
        $payment = $this->stalePayment();

        $this->artisan('payments:check-pending')->assertSuccessful();

        $payment->refresh();
        $this->assertSame(PaymentStatus::Successful, $payment->status);
        $this->assertNotNull($payment->gateway_reference);
        $this->assertSame(OrderStatus::Confirmed, $payment->order->status);
        $this->assertNotNull($payment->order->paid_at);
    }

    public function test_it_marks_the_payment_failed_and_keeps_the_order_pending(): void
    {
        config(['payment.gateways.credit_card.simulate_verify' => 'fail']);
        $payment = $this->stalePayment();

        $this->artisan('payments:check-pending')->assertSuccessful();

        $this->assertSame(PaymentStatus::Failed, $payment->fresh()->status);
        $this->assertSame(OrderStatus::Pending, $payment->order->fresh()->status);
    }

    public function test_it_leaves_the_payment_pending_when_the_gateway_does_not_answer(): void
    {
        config(['payment.gateways.credit_card.simulate_verify' => 'timeout']);
        $payment = $this->stalePayment();

        $this->artisan('payments:check-pending')->assertSuccessful();

        $this->assertSame(PaymentStatus::Pending, $payment->fresh()->status);
    }

    public function test_it_gives_the_webhook_time_before_checking(): void
    {
        $payment = $this->stalePayment(minutesAgo: 2);

        $this->artisan('payments:check-pending')->assertSuccessful();

        $this->assertSame(PaymentStatus::Pending, $payment->fresh()->status);
    }

    public function test_it_does_not_touch_settled_payments(): void
    {
        config(['payment.gateways.credit_card.simulate_verify' => 'fail']);
        $payment = Payment::factory()->successful()->create(['created_at' => now()->subHour()]);

        $this->artisan('payments:check-pending')->assertSuccessful();

        $this->assertSame(PaymentStatus::Successful, $payment->fresh()->status);
    }

    public function test_it_keeps_a_very_old_payment_pending_and_logs_it_for_review(): void
    {
        config(['payment.gateways.credit_card.simulate_verify' => 'pending']);
        Log::spy();
        $payment = $this->stalePayment(minutesAgo: 25 * 60);

        $this->artisan('payments:check-pending')->assertSuccessful();

        $this->assertSame(PaymentStatus::Pending, $payment->fresh()->status);
        Log::shouldHaveReceived('warning')->with('Payment still pending, needs manual review.', ['reference' => $payment->reference]);
    }
}
