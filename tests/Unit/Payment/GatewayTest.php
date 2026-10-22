<?php

declare(strict_types=1);

namespace Tests\Unit\Payment;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Exceptions\GatewayTimeoutException;
use App\Models\Payment;
use App\Services\Payment\CreditCardGateway;
use App\Services\Payment\PaypalGateway;
use Tests\TestCase;

final class GatewayTest extends TestCase
{
    public function test_it_simulates_a_successful_charge(): void
    {
        $result = (new CreditCardGateway)->charge(new Payment);

        $this->assertSame(PaymentStatus::Successful, $result['status']);
        $this->assertStringStartsWith('credit_card_', $result['gateway_reference']);
    }

    public function test_it_simulates_a_failed_charge(): void
    {
        config(['payment.gateways.paypal.simulate' => 'fail']);

        $result = (new PaypalGateway)->charge(new Payment);

        $this->assertSame(PaymentStatus::Failed, $result['status']);
        $this->assertStringStartsWith('paypal_', $result['gateway_reference']);
    }

    public function test_it_simulates_a_timeout(): void
    {
        config(['payment.gateways.credit_card.simulate' => 'timeout']);

        $this->expectException(GatewayTimeoutException::class);

        (new CreditCardGateway)->charge(new Payment);
    }

    public function test_status_transitions_follow_the_state_machines(): void
    {
        $this->assertTrue(PaymentStatus::Pending->canTransitionTo(PaymentStatus::Successful));
        $this->assertTrue(PaymentStatus::Pending->canTransitionTo(PaymentStatus::Failed));
        $this->assertFalse(PaymentStatus::Pending->canTransitionTo(PaymentStatus::Pending));
        $this->assertFalse(PaymentStatus::Successful->canTransitionTo(PaymentStatus::Failed));
        $this->assertFalse(PaymentStatus::Failed->canTransitionTo(PaymentStatus::Successful));

        $this->assertTrue(OrderStatus::Pending->canTransitionTo(OrderStatus::Confirmed));
        $this->assertTrue(OrderStatus::Confirmed->canTransitionTo(OrderStatus::Cancelled));
        $this->assertFalse(OrderStatus::Cancelled->canTransitionTo(OrderStatus::Pending));
        $this->assertFalse(OrderStatus::Confirmed->canTransitionTo(OrderStatus::Pending));
    }
}
