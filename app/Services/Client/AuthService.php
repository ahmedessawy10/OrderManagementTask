<?php

declare(strict_types=1);

namespace App\Services\Client;

use App\Enums\OtpType;
use App\Enums\SmsProvider;
use App\Enums\UserStatus;
use App\Events\SendCode;
use App\Events\UploadImage;
use App\Exceptions\UserBlockedException;
use App\Exceptions\ValidationException;
use App\Models\Otp;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Hash;

final class AuthService
{
    public function __construct() {}

    public function register(array $data): User
    {

        // Create user without verified phone
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'],
            'password' => $data['password'],
            'user_status' => UserStatus::ACTIVE,
            'phone_verified_at' => null,
        ]);

        $user->assignRole('client');

        event(new SendCode($user->phone, OtpType::REGISTRATION));

        return $user;
    }

    public function verifyOtp(array $data): array
    {
        $otp = Otp::where('phone', $data['phone'])
            ->where('code', $data['code'])
            ->where('type', $data['type'])
            ->where('expires_at', '>', now())
            ->whereNull('verified_at')
            ->latest()
            ->first();

        if (! $otp) {
            throw new ValidationException(__('messages.auth.invalid_otp'));
        }

        $type = OtpType::from($data['type']);

        // Handle different OTP types
        switch ($type) {
            case OtpType::REGISTRATION:
            case OtpType::LOGIN:
                // Get user and mark phone as verified
                $user = User::where('phone', $data['phone'])->firstOrFail();
                $user->update(['phone_verified_at' => now()]);

                $otp->delete();
                $tokens = $this->generateTokens($user);

                return [
                    'user' => $user->fresh(),
                    ...$tokens,
                ];

            case OtpType::PASSWORD_RESET:
                // Just verify OTP, actual password reset happens in resetPassword method
                $user = User::where('phone', $data['phone'])->firstOrFail();
                $otp->update(['verified_at' => now()]);

                return [
                    'user' => $user,
                    'token' => null,
                ];

            case OtpType::CHANGE_PHONE:
                // Check if new phone is already taken
                $existingUser = User::where('phone', $data['phone'])
                    ->where('id', '!=', $data['user_id'])
                    ->first();

                if ($existingUser) {
                    throw new ValidationException(__('messages.auth.phone_already_taken'));
                }

                $user = User::findOrFail($data['user_id']);
                $user->update([
                    'phone' => $data['phone'],
                    'phone_verified_at' => now(),
                ]);

                $otp->delete();
                $tokens = $this->generateTokens($user);

                return [
                    'user' => $user->fresh(),
                    ...$tokens,
                ];

            default:
                throw new ValidationException(__('messages.auth.invalid_otp_type'));
        }
    }

    public function sendOtp(array $data): void
    {
        $phone = $data['phone'];
        $type = OtpType::from($data['type']);
        $provider = isset($data['provider']) ? SmsProvider::from($data['provider']) : SmsProvider::SMS;
        // Validate based on OTP type
        $user = User::where('phone', $phone)->first();

        switch ($type) {
            case OtpType::REGISTRATION:
                if (! $user) {
                    throw new ValidationException(__('messages.auth.phone_not_found'));
                }

                if ($user->phone_verified_at) {
                    throw new ValidationException(__('messages.auth.phone_already_registered'));
                }
                break;

            case OtpType::LOGIN:
            case OtpType::PASSWORD_RESET:
                // User must exist
                if (! $user) {
                    throw new ValidationException(__('messages.auth.phone_not_found'));
                }

                break;

            case OtpType::CHANGE_PHONE:
                // For change phone, validate new phone is not taken
                if ($user) {
                    throw new ValidationException(__('messages.auth.phone_already_taken'));
                }
                break;
        }

        event(new SendCode($phone, $type, $provider));
    }

    public function login(array $data): array
    {

        $phone = $data['phone'];
        $password = $data['password'];

        $user = User::where('phone', $phone)->whereNull('deleted_at')->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            throw new ValidationException(__('messages.auth.invalid_credentials'));
        }
        if (! $user->hasRole('client')) {
            throw new ValidationException(__('messages.auth.invalid_credentials'));
        }

        // Check if account is blocked
        if ($user->user_status === UserStatus::BLOCKED) {
            throw new UserBlockedException(__('messages.auth.account_blocked', ['reason' => $user->block_reason]));
        }

        // Check if phone is verified
        if (! $user->phone_verified_at) {
            event(new SendCode($user->phone, OtpType::LOGIN));
            throw new Exception(__('messages.auth.phone_not_verified'));
        }
        // Generate tokens
        $tokens = $this->generateTokens($user);

        return [
            'user' => $user,
            ...$tokens,
        ];
    }

    public function forgotPassword(array $data): void
    {
        $phone = $data['phone'];
        $user = User::where('phone', $phone)->where('deleted_at', null)->first();

        if (! $user) {
            throw new ValidationException(__('messages.auth.phone_not_found'));
        }

        // Generate OTP for password reset
        event(new SendCode($phone, OtpType::PASSWORD_RESET));
    }

    public function resetPassword(string $phone, string $code, string $password, ?string $application = null): void
    {
        $otp = Otp::where('phone', $phone)
            ->where('code', $code)
            ->where('type', OtpType::PASSWORD_RESET)
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (! $otp) {
            throw new ValidationException(__('messages.auth.invalid_otp'));
        }

        // Update password
        $user = User::where('phone', $phone)->firstOrFail();

        $user->update(['password' => $password]);

        // Delete used OTP
        $otp->delete();
    }

    public function logout(User $user): bool
    {
        auth('api')->logout();

        $device = request()->header('X-Device');
        $deviceId = request()->input('device_id');

        if (method_exists($user, 'deviceTokens')) {
            $user->deviceTokens()
                ->when($device, fn ($q) => $q->where('device', $device))
                ->when($deviceId, fn ($q) => $q->where('device_id', $deviceId))
                ->delete();
        }

        return true;
    }

    public function deleteAccount(User $user): void
    {
        try {
            auth('api')->logout();
        } catch (\Throwable) {
            // Token may already be invalidated
        }

        if (method_exists($user, 'deviceTokens')) {
            $user->deviceTokens()->delete();
        }

        // Soft delete user
        $user->delete();
    }

    public function setLanguage(string $language): bool
    {
        $user = auth('api')->user();
        $user->update(['preferred_language' => $language]);

        return true;
    }

    public function profile(): User
    {
        return auth('api')->user();
    }

    public function changeProfileImage($image): User
    {
        $user = auth('api')->user();

        event(new UploadImage($user, 'profile_image'));

        return $user->fresh();
    }

    public function changePassword(array $data): void
    {
        $user = auth('api')->user();

        if (! Hash::check($data['current_password'], $user->password)) {
            throw new ValidationException(__('messages.auth.current_password_incorrect'));
        }

        if (Hash::check($data['password'], $user->password)) {
            throw new ValidationException(__('messages.auth.same_password'));
        }

        $user->update(['password' => $data['password']]);
    }

    public function generateTokens(User $user): array
    {
        $accessTtl = (int) config('jwt.ttl', 60);
        $refreshTtl = (int) config('jwt.refresh_ttl', 20160);

        $accessToken = auth('api')->claims(['token_type' => 'access'])->setTTL($accessTtl)->fromUser($user);
        $refreshToken = auth('api')->claims(['token_type' => 'refresh'])->setTTL($refreshTtl)->fromUser($user);

        // Restore default TTL on the factory
        auth('api')->factory()->setTTL($accessTtl);

        return [
            'token' => $accessToken,
            'token_type' => 'bearer',
            'expires_in' => $accessTtl * 60,
            'expires_at' => now()->addMinutes($accessTtl)->format('Y-m-d H:i:s'),
            'refresh_token' => $refreshToken,
            'refresh_expires_in' => $refreshTtl * 60,
            'refresh_expires_at' => now()->addMinutes($refreshTtl)->format('Y-m-d H:i:s'),
        ];
    }

    public function refreshToken(?string $refreshToken = null): array
    {
        if ($refreshToken) {
            try {
                $payload = auth('api')->setToken($refreshToken)->getPayload();
            } catch (Exception) {
                throw new ValidationException(__('messages.auth.token_expired_or_invalid'));
            }

            if ($payload->get('token_type') !== 'refresh') {
                throw new ValidationException(__('messages.auth.invalid_token_type'));
            }

            $user = auth('api')->setToken($refreshToken)->user();
            if (! $user) {
                throw new ValidationException(__('messages.auth.user_not_found'));
            }

            return $this->generateTokens($user);
        }

        try {
            $user = auth('api')->user();
        } catch (Exception) {
            $user = null;
        }

        if (! $user) {
            try {
                $newAccessToken = auth('api')->refresh();
                $user = auth('api')->setToken($newAccessToken)->user();
            } catch (Exception) {
                throw new ValidationException(__('messages.auth.token_expired_or_invalid'));
            }
        }

        if (! $user) {
            throw new ValidationException(__('messages.auth.unauthenticated'));
        }

        return $this->generateTokens($user);
    }
}
