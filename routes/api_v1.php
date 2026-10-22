<?php

use App\Http\Controllers\Api\V1\Client\AuthController;
use App\Http\Controllers\Api\V1\Client\NotificationController;
use App\Http\Controllers\Api\V1\Client\ProductController;
use Illuminate\Support\Facades\Route;

Route::get('products', [ProductController::class, 'index'])->name('products.index');

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('send-otp', [AuthController::class, 'sendOtp']);
    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);
    Route::post('refresh', [AuthController::class, 'refresh']);

    Route::middleware('auth:api')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('profile', [AuthController::class, 'profile']);
        Route::post('change-profile-image', [AuthController::class, 'changeProfileImage']);
        Route::post('change-password', [AuthController::class, 'changePassword']);
        Route::post('set-language', [AuthController::class, 'setLanguage']);
        Route::delete('delete-account', [AuthController::class, 'deleteAccount']);
    });
});

Route::prefix('notifications')->middleware('auth:api')->group(function () {
    Route::get('/', [NotificationController::class, 'index']);
    Route::get('unread-count', [NotificationController::class, 'unreadCount']);
    Route::post('device-token', [NotificationController::class, 'storeDeviceToken']);
    Route::delete('device-token', [NotificationController::class, 'removeDeviceToken']);
    Route::post('toggle', [NotificationController::class, 'toggleNotification']);
    Route::post('read-all', [NotificationController::class, 'markAllAsRead']);
    Route::post('{id}/read', [NotificationController::class, 'markAsRead']);
    Route::delete('{id}', [NotificationController::class, 'destroy']);
});
