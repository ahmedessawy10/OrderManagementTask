<?php

declare(strict_types=1);

namespace Tests\Feature\Payment;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Body: {reference, status, amount, currency, gateway_reference?}.
 */
final class WebhookTest extends TestCase
{
    use RefreshDatabase;

    private function send(array $payload, string $gateway = 'credit_card')
    {
        return $this->postJson("/api/v1/webhooks/{$gateway}", $payload);
    }

    private function payload(Payment $payment, string $status = 'successful'): array
    {
        return [
            'reference' => $payment->reference,
            'status' => $status,
            'amount' => (string) $payment->amount,
            'currency' => $payment->currency,
            'gateway_reference' => 'credit_card_provider_1',
        ];
    }

    public function test_it_marks_a_pending_payment_successful_and_the_order_paid(): void
    {
        $payment = Payment::factory()->pending()->create();

        $this->send($this->payload($payment))
            ->assertOk()
            ->assertJsonPath('data.status', 'successful');

        $payment->refresh();
        $this->assertSame(PaymentStatus::Successful, $payment->status);
        $this->assertSame('credit_card_provider_1', $payment->gateway_reference);
        $this->assertNotNull($payment->order->paid_at);
        $this->assertSame('confirmed', $payment->order->status->value);
    }

    public function test_it_marks_a_pending_payment_failed(): void
    {
        $payment = Payment::factory()->pending()->create();

        $this->send($this->payload($payment, 'failed'))->assertOk();

        $this->assertSame(PaymentStatus::Failed, $payment->fresh()->status);
        $this->assertNull($payment->order->fresh()->paid_at);
    }

    public function test_a_duplicate_webhook_is_a_no_op(): void
    {
        $payment = Payment::factory()->pending()->create();

        $this->send($this->payload($payment))->assertOk();
        $paidAt = $payment->order->fresh()->paid_at;
        $this->travel(5)->minutes();

        $this->send($this->payload($payment))->assertOk()->assertJsonPath('data.status', 'successful');

        $this->assertEquals($paidAt, $payment->order->fresh()->paid_at);
    }

    public function test_a_late_failed_webhook_does_not_undo_a_successful_payment(): void
    {
        $payment = Payment::factory()->successful()->create();

        $this->send($this->payload($payment, 'failed'))
            ->assertOk()
            ->assertJsonPath('data.status', 'successful');

        $this->assertSame(PaymentStatus::Successful, $payment->fresh()->status);
    }

    public function test_it_returns_404_for_an_unknown_reference_so_the_gateway_retries(): void
    {
        $payment = Payment::factory()->pending()->make(['reference' => '01JUNKNOWNREFERENCE0000000']);

        $this->send($this->payload($payment))
            ->assertNotFound()
            ->assertJsonPath('message', __('messages.webhook.payment_not_found'));
    }

    public function test_it_rejects_an_amount_or_currency_mismatch(): void
    {
        $payment = Payment::factory()->pending()->create();

        $this->send(['amount' => '0.01'] + $this->payload($payment))
            ->assertUnprocessable()
            ->assertJsonPath('message', __('messages.webhook.amount_mismatch'));

        $this->send(['currency' => 'USD'] + $this->payload($payment))->assertUnprocessable();

        $this->assertSame(PaymentStatus::Pending, $payment->fresh()->status);
    }

    public function test_it_rejects_an_invalid_payload(): void
    {
        $payment = Payment::factory()->pending()->create();

        $this->send(['status' => 'pending'] + $this->payload($payment))
            ->assertUnprocessable()
            ->assertJsonPath('errors.status.0', __('messages.webhook.invalid_status'));
    }

    public function test_it_returns_404_for_an_unknown_gateway(): void
    {
        $this->send(['reference' => 'x'], 'bitcoin')
            ->assertNotFound()
            ->assertJsonPath('message', __('messages.webhook.gateway_not_found'));
    }
}
