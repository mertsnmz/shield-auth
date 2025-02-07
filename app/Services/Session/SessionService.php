<?php

namespace App\Services\Session;

use App\Repositories\Session\SessionRepository;
use Illuminate\Support\Collection;
use Exception;

class SessionService
{
    public function __construct(
        private readonly SessionRepository $sessionRepository
    ) {
    }

    public function getUserSessions(int $userId): Collection
    {
        $sessions = $this->sessionRepository->getUserSessions($userId);

        return $sessions->map(function ($session) {
            $payload = json_decode($session->payload);
            return [
                'id' => $session->id,
                'ip_address' => $session->ip_address,
                'user_agent' => $session->user_agent,
                'last_activity' => date('Y-m-d H:i:s', $session->last_activity),
                'is_current_device' => $session->id === request()->cookie('session_id'),
                'remember_me' => $payload->remember_me ?? false,
                'created_at' => $payload->created_at,
            ];
        });
    }

    public function terminateSession(int $userId, string $sessionId): void
    {
        if (!$sessionId) {
            throw new Exception('Session ID is required', 400);
        }

        $session = $this->sessionRepository->findSessionByIdAndUser($sessionId, $userId);

        if (!$session) {
            throw new Exception('Session not found', 404);
        }

        if ($session->id === request()->cookie('session_id')) {
            throw new Exception('Cannot terminate current session', 400);
        }

        $this->sessionRepository->deleteSession($session);
    }
}
