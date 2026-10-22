<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\SmsProvider;
use App\Http\Requests\Api\V1\Client\Auth\RegisterRequest;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

final class RegisterValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_phone_validation_passes_with_kuwait_numbers(): void
    {
        $request = new RegisterRequest;
        $rules = $request->rules();

        $validNumbers = [
            '0096512345678',
            '+96512345678',
            '0096598765432',
        ];

        foreach ($validNumbers as $phone) {
            $validator = Validator::make([
                'name' => 'Test User',
                'phone' => $phone,
                'password' => 'secret123',
                'password_confirmation' => 'secret123',
            ], $rules);

            $this->assertFalse(
                $validator->errors()->has('phone'),
                "Phone {$phone} should be valid but failed validation with: ".implode(', ', $validator->errors()->get('phone'))
            );
        }
    }

    public function test_phone_validation_fails_with_invalid_numbers(): void
    {
        $request = new RegisterRequest;
        $rules = $request->rules();

        $invalidNumbers = [
            '12345678',          // Missing country code
            '009651234567',      // Only 7 digits after country code
            '00965123456789',    // 9 digits after country code
            '01012345678',       // Egyptian local format without prefix
            '+201012345678',     // Egyptian format (no longer valid)
            'invalid_phone',     // Non-numeric
        ];

        foreach ($invalidNumbers as $phone) {
            $validator = Validator::make([
                'name' => 'Test User',
                'phone' => $phone,
                'password' => 'secret123',
                'password_confirmation' => 'secret123',
            ], $rules);

            $this->assertTrue(
                $validator->errors()->has('phone'),
                "Phone {$phone} should be invalid but passed validation."
            );
        }
    }

    public function test_user_registration_creates_otp_with_default_sms_provider(): void
    {
        $this->seed(RoleSeeder::class);

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'John Kuwait',
            'phone' => '0096512345678',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertCreated()
            ->assertJsonPath('status', true);

        $this->assertDatabaseHas('otps', [
            'phone' => '0096512345678',
            'provider' => SmsProvider::SMS->value,
        ]);
    }
}
