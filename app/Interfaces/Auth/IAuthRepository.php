<?php

namespace App\Interfaces\Auth;

use App\Models\User;
use App\Models\Session;

interface IAuthRepository
{
    public function findUserByEmail(string $email): ?User;
    public function createUser(array $data): User;
    public function findSessionById(string $id): ?Session;
    public function deleteSession(Session $session): bool;
    public function countActiveSessions(int $userId): int;
    public function findSessionByDeviceInfo(int $userId, string $ipAddress, string $userAgent): ?Session;
    public function getOldestSession(int $userId): ?Session;
    public function createSession(array $data): Session;
}
