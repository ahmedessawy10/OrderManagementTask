<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Enums\PaymentStatus;
use App\Exceptions\GatewayTimeoutException;
use App\Models\Payment;
use Illuminate\Support\Str;

/**
 * Shared body of the simulated gateways. The using class only names its config key.
 */
trait SimulatesGateway
{
    /**
     * Key in config('payment.gateways'), e.g. "credit_card".
     */
    abstract protected function key(): string;

    public function charge(Payment $payment): array
    {
        // مفيش API حقيقي هنا: النتيجة بتيجي من الكونفيج (success | fail | timeout) عشان نقدر نجرب كل حالة
        return match ($this->setting('simulate')) {
            'fail' => ['status' => PaymentStatus::Failed, 'gateway_reference' => $this->newReference()],
            'timeout' => throw new GatewayTimeoutException,
            default => ['status' => PaymentStatus::Successful, 'gateway_reference' => $this->newReference()],
        };
    }

    public function verify(Payment $payment): array
    {
        // نفس فكرة charge: نتيجة الاستعلام من الكونفيج (success | fail | pending | timeout)
        return match ($this->setting('simulate_verify')) {
            'fail' => ['status' => PaymentStatus::Failed, 'gateway_reference' => $this->newReference()],
            'pending' => ['status' => PaymentStatus::Pending, 'gateway_reference' => null],
            'timeout' => throw new GatewayTimeoutException,
            default => ['status' => PaymentStatus::Successful, 'gateway_reference' => $this->newReference()],
        };
    }

    private function setting(string $name): mixed
    {
        return config("payment.gateways.{$this->key()}.{$name}");
    }

    private function newReference(): string
    {
        return $this->key().'_'.Str::lower(Str::random(20));
    }
}
