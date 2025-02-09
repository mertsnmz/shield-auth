<?php

namespace App\Interfaces\TwoFactorAuth;

use App\Models\User;

interface ITwoFactorAuthService
{
    public function enable(User $user): array;
    public function verify(User $user, string $code): void;
    public function disable(User $user, string $currentPassword, string $code): void;
    public function getBackupCodes(User $user): array;
    public function regenerateBackupCodes(User $user): array;
    public function verifyCode(string $secret, string $code): bool;
    public function verifyRecoveryCode(User $user, string $code): bool;
    public function isEnabled(User $user): bool;
} 