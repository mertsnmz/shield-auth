<?php

namespace App\Repositories\Auth;

use App\Models\User;
use App\Models\Session;
use Illuminate\Support\Collection;

class AuthRepository
{
    public function findUserByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    public function createUser(array $data): User
    {
        return User::create($data);
    }

    public function getUserActiveSessions(int $userId): Collection
    {
        return Session::where('user_id', $userId)->get();
    }

    public function findSessionById(string $sessionId): ?Session
    {
        return Session::where('id', $sessionId)->first();
    }

    public function findSessionByDeviceInfo(int $userId, string $ipAddress, string $userAgent): ?Session
    {
        return Session::where('user_id', $userId)
            ->where('ip_address', $ipAddress)
            ->where('user_agent', $userAgent)
            ->first();
    }

    public function deleteSession(Session $session): bool
    {
        return $session->delete();
    }

    public function getOldestSession(int $userId): ?Session
    {
        return Session::where('user_id', $userId)
            ->orderBy('last_activity', 'asc')
            ->first();
    }

    public function createSession(array $data): Session
    {
        return Session::create($data);
    }

    public function countActiveSessions(int $userId): int
    {
        return Session::where('user_id', $userId)->count();
    }
} 