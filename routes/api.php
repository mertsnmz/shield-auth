<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\OAuth\OAuthController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\TwoFactorAuthController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login'])
        ->middleware('throttle:login');

    Route::post('register', [AuthController::class, 'register'])
        ->middleware('throttle:login');

    Route::middleware('throttle:password-reset')->group(function () {
        Route::post('password/forgot', [PasswordController::class, 'forgot']);
    });

    // Protected routes
    Route::middleware('auth.session')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('password/reset', [PasswordController::class, 'reset']);
    });
});

// OAuth routes
Route::prefix('oauth')->group(function () {
    Route::middleware('throttle:oauth-token')->group(function () {
        Route::post('token', [OAuthController::class, 'issueToken']);
        Route::post('token/revoke', [OAuthController::class, 'revokeToken']);
    });

    Route::middleware('auth.session')->group(function () {
        Route::get('authorize', [OAuthController::class, 'authorize']);
        Route::post('authorize', [OAuthController::class, 'approveAuthorization']);
    });
});

// Protected routes
Route::middleware(['auth.session', 'throttle:api'])->group(function () {
    Route::prefix('users/me')->group(function () {
        Route::get('/', [UserController::class, 'me']);
        Route::put('/', [UserController::class, 'update']);
        Route::put('/password', [PasswordController::class, 'update']);
        Route::get('/sessions', [SessionController::class, 'index']);
        Route::delete('/sessions/{id}', [SessionController::class, 'destroy']);
    });

    // 2FA routes
    Route::prefix('auth/2fa')->middleware('throttle:2fa')->group(function () {
        Route::post('/enable', [TwoFactorAuthController::class, 'enable']);
        Route::post('/verify', [TwoFactorAuthController::class, 'verify']);
        Route::post('/disable', [TwoFactorAuthController::class, 'disable']);
        Route::get('/backup-codes', [TwoFactorAuthController::class, 'getBackupCodes']);
        Route::post('/regenerate-backup-codes', [TwoFactorAuthController::class, 'regenerateBackupCodes']);
    });
});
