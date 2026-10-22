<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Client;

use App\Exceptions\IncompleteException;
use App\Exceptions\UserBlockedException;
use App\Exceptions\ValidationException;
use App\Facades\Client\AuthFacade;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Client\Auth\ChangeImageRequest;
use App\Http\Requests\Api\V1\Client\Auth\ChangePasswordRequest;
use App\Http\Requests\Api\V1\Client\Auth\ForgotPasswordRequest;
use App\Http\Requests\Api\V1\Client\Auth\LoginRequest;
use App\Http\Requests\Api\V1\Client\Auth\LogoutRequest;
use App\Http\Requests\Api\V1\Client\Auth\RegisterRequest;
use App\Http\Requests\Api\V1\Client\Auth\ResetPasswordRequest;
use App\Http\Requests\Api\V1\Client\Auth\SendOtpRequest;
use App\Http\Requests\Api\V1\Client\Auth\SetLanguageRequest;
use App\Http\Requests\Api\V1\Client\Auth\VerifyOtpRequest;
use App\Http\Resources\Api\V1\Client\UserResource;
use App\Http\Traits\ApiResponse;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AuthController extends Controller
{
    use ApiResponse;

    public function register(RegisterRequest $request)
    {

        try {
            $user = AuthFacade::register($request->validated());

            return $this->success(
                __('messages.auth.register_success'),
                UserResource::make($user),
                201
            );
        } catch (ValidationException $e) {
            return $this->fail($e->getMessage(), 422);
        }
    }

    public function verifyOtp(VerifyOtpRequest $request)
    {
        try {
            $result = AuthFacade::verifyOtp($request->validated());

            $tokens = isset($result['token']) ? [
                'token' => $result['token'],
                'token_type' => $result['token_type'] ?? 'bearer',
                'expires_in' => $result['expires_in'] ?? null,
                'expires_at' => $result['expires_at'] ?? null,
                'refresh_token' => $result['refresh_token'] ?? null,
                'refresh_expires_in' => $result['refresh_expires_in'] ?? null,
                'refresh_expires_at' => $result['refresh_expires_at'] ?? null,
            ] : [];

            return $this->success(
                __('messages.auth.otp_verified'),
                UserResource::make($result['user']),
                200,
                $tokens
            );
        } catch (ValidationException $e) {
            return $this->fail($e->getMessage(), 422);
        }
    }

    public function sendOtp(SendOtpRequest $request)
    {
        try {
            AuthFacade::sendOtp($request->validated());

            return $this->success(__('messages.auth.otp_sent'));
        } catch (ValidationException $e) {
            return $this->fail($e->getMessage(), 422);
        }
    }

    public function login(LoginRequest $request)
    {
        try {
            $result = AuthFacade::login($request->validated());

            return $this->success(
                __('messages.auth.login_success'),
                UserResource::make($result['user']),
                200,
                [
                    'token' => $result['token'],
                    'token_type' => $result['token_type'] ?? 'bearer',
                    'expires_in' => $result['expires_in'] ?? null,
                    'expires_at' => $result['expires_at'] ?? null,
                    'refresh_token' => $result['refresh_token'] ?? null,
                    'refresh_expires_in' => $result['refresh_expires_in'] ?? null,
                    'refresh_expires_at' => $result['refresh_expires_at'] ?? null,
                ]
            );
        } catch (UserBlockedException $e) {
            return $this->fail($e->getMessage(), 403);
        } catch (ValidationException $e) {
            return $this->fail($e->getMessage(), 422);
        } catch (IncompleteException $e) {
            return $this->fail($e->getMessage(), 409, ['user_id' => $e->getUserId()]);
        } catch (Exception $e) {
            return $this->fail($e->getMessage(), 403);
        }
    }

    public function forgotPassword(ForgotPasswordRequest $request)
    {
        try {
            AuthFacade::forgotPassword($request->validated());

            return $this->success(__('messages.auth.otp_sent'));
        } catch (ValidationException $e) {
            return $this->fail($e->getMessage(), 422);
        }
    }

    public function resetPassword(ResetPasswordRequest $request)
    {
        try {
            AuthFacade::resetPassword(
                $request->input('phone'),
                $request->input('code'),
                $request->input('password'),
                $request->input('application')
            );

            return $this->success(__('messages.auth.password_reset_success'));
        } catch (ValidationException $e) {
            return $this->fail($e->getMessage(), 422);
        }
    }

    public function logout(LogoutRequest $request)
    {

        try {
            AuthFacade::logout(auth('api')->user());

            return $this->success(__('messages.auth.logout_success'));
        } catch (ValidationException $e) {

            return $this->fail($e->getMessage(), JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function deleteAccount()
    {
        try {
            AuthFacade::deleteAccount(auth('api')->user());

            return $this->success(__('messages.auth.account_deleted'));
        } catch (ValidationException $e) {
            return $this->responseApi($e->getMessage(), null, 422);
        }
    }

    public function setLanguage(SetLanguageRequest $request)
    {
        try {
            AuthFacade::setLanguage($request->input('language'));

            return $this->success(__('messages.auth.language_updated'));
        } catch (ValidationException $e) {
            return $this->fail($e->getMessage(), 422);
        }
    }

    public function profile()
    {
        $user = AuthFacade::profile();

        return $this->success(
            __('messages.auth.profile_retrieved'),
            UserResource::make($user)
        );
    }

    public function changeProfileImage(ChangeImageRequest $request)
    {
        try {
            $user = AuthFacade::changeProfileImage($request->file('profile_image'));

            return $this->success(
                __('messages.auth.profile_image_updated'),
                null
            );
        } catch (ValidationException $e) {
            return $this->fail($e->getMessage(), 422);
        }
    }

    public function changePassword(ChangePasswordRequest $request)
    {
        try {
            AuthFacade::changePassword($request->validated());

            return $this->success(__('messages.auth.password_changed'));
        } catch (ValidationException $e) {
            return $this->fail($e->getMessage(), 422);
        }
    }

    public function refresh(Request $request)
    {
        try {
            $tokens = AuthFacade::refreshToken($request->input('refresh_token'));

            return $this->success(
                __('messages.auth.token_refreshed'),
                null,
                200,
                $tokens
            );
        } catch (Exception $e) {
            return $this->fail($e->getMessage(), 401);
        }
    }
}
