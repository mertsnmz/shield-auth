<?php

namespace App\Interfaces\Auth;

interface IAuthService
{
    public function login(array $credentials, bool $remember): array;
    public function register(array $data): array;
    public function logout(string $sessionId): void;
} 