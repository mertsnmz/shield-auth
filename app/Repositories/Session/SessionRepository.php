<?php

namespace App\Repositories\Session;

use App\Models\Session;
use Illuminate\Support\Collection;

class SessionRepository
{
    public function getUserSessions(int $userId): Collection
    {
        return Session::where('user_id', $userId)->get();
    }

    public function findSessionByIdAndUser(string $sessionId, int $userId): ?Session
    {
        return Session::where('id', $sessionId)
            ->where('user_id', $userId)
            ->first();
    }

    public function deleteSession(Session $session): bool
    {
        return $session->delete();
    }
} 