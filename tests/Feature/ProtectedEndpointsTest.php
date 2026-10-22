<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class ProtectedEndpointsTest extends TestCase
{
    use RefreshDatabase;

    public static function protectedEndpoints(): array
    {
        return [
            'list orders' => ['GET', '/api/v1/orders'],
            'create order' => ['POST', '/api/v1/orders'],
            'show order' => ['GET', '/api/v1/orders/1'],
            'update order' => ['PUT', '/api/v1/orders/1'],
            'delete order' => ['DELETE', '/api/v1/orders/1'],
            'pay order' => ['POST', '/api/v1/orders/1/payments'],
            'list payments' => ['GET', '/api/v1/payments'],
            'show payment' => ['GET', '/api/v1/payments/1'],
        ];
    }

    #[DataProvider('protectedEndpoints')]
    public function test_it_requires_a_token(string $method, string $uri): void
    {
        $this->json($method, $uri)
            ->assertUnauthorized()
            ->assertJsonPath('status', false)
            ->assertJsonPath('message', __('messages.errors.unauthenticated'));
    }
}
