<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\Auth\AuthService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Exception;

/**
 * @group Authentication
 *
 * APIs for managing authentication
 */
class AuthController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly AuthService $authService
    ) {
    }

    /**
     * Login.
     *
     * Authenticate a user and create a new session.
     *
     * @param LoginRequest $request
     *
     * @return JsonResponse
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->login(
                $request->validated(),
                $request->boolean('remember_me', false)
            );

            if (isset($result['requires_2fa']) && $result['requires_2fa']) {
                return $this->errorResponse($result['message'], 403);
            }

            return $this->successResponse(
                [
                    'session_id' => $result['session_id'],
                    'password_status' => $result['password_status'],
                    'requires_2fa' => false,
                ],
                $result['message']
            )->withCookie(
                cookie(
                    'session_id',
                    $result['session_id'],
                    $request->boolean('remember_me') ? 43200 : 1440
                )
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Register.
     *
     * Register a new user account.
     *
     * @param RegisterRequest $request
     *
     * @return JsonResponse
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->register($request->validated());

            return $this->successResponse(
                ['session_id' => $result['session_id']],
                $result['message'],
                201
            )->withCookie(
                cookie('session_id', $result['session_id'], 24 * 60)
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Logout.
     *
     * Invalidate the current session.
     *
     * @return JsonResponse
     */
    public function logout(): JsonResponse
    {
        try {
            $sessionId = request()->cookie('session_id');

            if (!$sessionId) {
                return $this->errorResponse('No active session', 400);
            }

            $this->authService->logout($sessionId);

            return $this->successResponse(
                message: 'Logged out successfully'
            )->withoutCookie('session_id');
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode());
        }
    }
}
