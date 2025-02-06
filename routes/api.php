<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\OAuth\OAuthController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);
    Route::post('password/forgot', [PasswordController::class, 'forgot']);
    Route::post('password/reset', [PasswordController::class, 'reset']);

    // Protected routes
    Route::middleware('auth.session')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
    });
});

// OAuth routes
Route::prefix('oauth')->group(function () {
    Route::post('token', [OAuthController::class, 'issueToken']);
    Route::post('token/revoke', [OAuthController::class, 'revokeToken']);
    
    Route::middleware('auth.session')->group(function () {
        Route::get('authorize', [OAuthController::class, 'authorize']);
        Route::post('authorize', [OAuthController::class, 'approveAuthorization']);
    });
});

// Protected routes
Route::middleware('auth.session')->group(function () {
    Route::prefix('users/me')->group(function () {
        Route::get('/', [UserController::class, 'me']);
        Route::put('/', [UserController::class, 'update']);
        Route::put('/password', [UserController::class, 'updatePassword']);
        Route::get('/sessions', [SessionController::class, 'index']);
        Route::delete('/sessions/{id}', [SessionController::class, 'destroy']);
    });
});
