<?php

namespace App\Interfaces\TwoFactorAuth;

use App\Models\User;

interface ITwoFactorAuthRepository
{
    public function updateTwoFactorSecret(User $user, string $secret): void;
    public function updateRecoveryCodes(User $user, array $codes): void;
    public function updateTwoFactorConfirmation(User $user): void;
    public function disableTwoFactor(User $user): void;
    public function getRecoveryCodes(User $user): ?array;
} 