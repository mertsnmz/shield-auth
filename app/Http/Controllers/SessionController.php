<?php

namespace App\Http\Controllers;

use App\Http\Requests\Session\ListSessionsRequest;
use App\Http\Requests\Session\DeleteSessionRequest;
use App\Services\Session\SessionService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * @group Session Management
 *
 * APIs for managing user sessions
 */
class SessionController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly SessionService $sessionService
    ) {}

    /**
     * List Sessions
     * 
     * Get all active sessions for the authenticated user.
     *
     * @param ListSessionsRequest $request
     * @return JsonResponse
     */
    public function index(ListSessionsRequest $request): JsonResponse
    {
        try {
            $sessions = $this->sessionService->getUserSessions(Auth::id());
            return $this->successResponse($sessions);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Delete Session
     * 
     * Terminate a specific session.
     *
     * @param DeleteSessionRequest $request
     * @param string $id
     * @return JsonResponse
     */
    public function destroy(DeleteSessionRequest $request, string $id): JsonResponse
    {
        try {
            $this->sessionService->terminateSession(Auth::id(), $id);
            return $this->successResponse(message: 'Session terminated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode());
        }
    }
} 