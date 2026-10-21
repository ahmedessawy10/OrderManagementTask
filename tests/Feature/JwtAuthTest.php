<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Services\Client\AuthService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class JwtAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_unauthenticated_request_to_protected_route_fails(): void
    {
        $response = $this->getJson('/api/v1/auth/profile');

        $response->assertUnauthorized();
    }

    public function test_authenticated_user_with_jwt_can_access_profile_and_returns_user_resource(): void
    {
        $user = User::factory()->create([
            'phone_verified_at' => now(),
        ]);

        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/auth/profile');

        $response->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.name', $user->name)
            ->assertJsonPath('data.phone', $user->phone);
    }

    public function test_user_can_store_device_token_using_jwt(): void
    {
        $user = User::factory()->create([
            'phone_verified_at' => now(),
        ]);

        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->withHeader('X-Device', 'ios')
            ->postJson('/api/v1/notifications/device-token', [
                'token' => 'sample_fcm_token_123',
                'device_id' => 'device_xyz',
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('device_tokens', [
            'tokenable_id' => $user->id,
            'device_token' => 'sample_fcm_token_123',
            'device_id' => 'device_xyz',
            'device' => 'ios',
        ]);
    }

    public function test_user_can_refresh_tokens_with_refresh_token_in_body(): void
    {
        $user = User::factory()->create([
            'phone_verified_at' => now(),
        ]);
        $user->assignRole('client');

        $authService = app(AuthService::class);
        $tokens = $authService->generateTokens($user);

        $response = $this->postJson('/api/v1/auth/refresh', [
            'refresh_token' => $tokens['refresh_token'],
        ]);

        $response->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonStructure([
                'status',
                'message',
                'data',
                'token',
                'token_type',
                'expires_in',
                'expires_at',
                'refresh_token',
                'refresh_expires_in',
                'refresh_expires_at',
            ]);
    }

    public function test_user_cannot_refresh_without_token(): void
    {
        $response = $this->postJson('/api/v1/auth/refresh');

        $response->assertStatus(401);
    }
}
