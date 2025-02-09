<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\OAuth\OAuthController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\TwoFactorAuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/

// Public Authentication Routes
Route::prefix('auth')->middleware('throttle:login')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);
});

Route::post('auth/password/forgot', [PasswordController::class, 'forgot'])
    ->middleware('throttle:password-reset');

// Protected Authentication Routes
Route::prefix('auth')->middleware('auth.session')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('password/reset', [PasswordController::class, 'reset']);
});

/*
|--------------------------------------------------------------------------
| OAuth Routes
|--------------------------------------------------------------------------
*/

Route::prefix('oauth')->group(function () {
    // Public OAuth Routes
    Route::middleware('throttle:oauth-token')->group(function () {
        Route::post('token', [OAuthController::class, 'issueToken']);
        Route::post('token/revoke', [OAuthController::class, 'revokeToken']);
    });

    // Protected OAuth Routes
    Route::middleware('auth.session')->group(function () {
        Route::get('authorize', [OAuthController::class, 'authorize']);
        Route::post('authorize', [OAuthController::class, 'approveAuthorization']);
    });
});

/*
|--------------------------------------------------------------------------
| User Management Routes
|--------------------------------------------------------------------------
*/

Route::prefix('users/me')->middleware(['auth.session', 'throttle:api'])->group(function () {
    // Profile Management
    Route::get('/', [UserController::class, 'me']);
    Route::put('/', [UserController::class, 'update']);
    
    // Password Management
    Route::put('/password', [PasswordController::class, 'update']);
    
    // Session Management
    Route::get('/sessions', [SessionController::class, 'index']);
    Route::delete('/sessions/{id}', [SessionController::class, 'destroy']);
});

/*
|--------------------------------------------------------------------------
| Two-Factor Authentication Routes
|--------------------------------------------------------------------------
*/

Route::prefix('auth/2fa')
    ->middleware(['auth.session', 'throttle:2fa'])
    ->group(function () {
        Route::post('/enable', [TwoFactorAuthController::class, 'enable']);
        Route::post('/verify', [TwoFactorAuthController::class, 'verify']);
        Route::post('/disable', [TwoFactorAuthController::class, 'disable']);
        Route::get('/backup-codes', [TwoFactorAuthController::class, 'getBackupCodes']);
        Route::post('/regenerate-backup-codes', [TwoFactorAuthController::class, 'regenerateBackupCodes']);
    });
