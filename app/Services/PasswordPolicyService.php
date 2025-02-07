<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\DB;

class PasswordPolicyService
{
    // Password policy constants
    public const MIN_LENGTH = 8;
    public const REQUIRE_UPPERCASE = true;
    public const REQUIRE_NUMERIC = true;
    public const REQUIRE_SPECIAL_CHAR = true;
    public const HISTORY_COUNT = 3;
    public const MAX_FAILED_ATTEMPTS = 5;
    public const PASSWORD_EXPIRY_DAYS = 90;
    public const PASSWORD_EXPIRY_WARNING_DAYS = 15;

    private const MAX_PASSWORD_HISTORY = 5;

    public function getValidationRules(): Password
    {
        $rules = Password::min(self::MIN_LENGTH);

        if (self::REQUIRE_UPPERCASE) {
            $rules->mixedCase();
        }

        if (self::REQUIRE_NUMERIC) {
            $rules->numbers();
        }

        if (self::REQUIRE_SPECIAL_CHAR) {
            $rules->symbols();
        }

        return $rules->uncompromised();
    }

    public function wasUsedBefore(User $user, string $newPassword): bool
    {
        $passwordHistory = json_decode($user->password_history ?? '[]', true);

        foreach ($passwordHistory as $oldPassword) {
            if (Hash::check($newPassword, $oldPassword)) {
                return true;
            }
        }

        return false;
    }

    public function recordPassword(User $user, string $hashedPassword): void
    {
        $passwordHistory = json_decode($user->password_history ?? '[]', true);
        array_unshift($passwordHistory, $hashedPassword);
        
        // Keep only the last MAX_PASSWORD_HISTORY passwords
        $passwordHistory = array_slice($passwordHistory, 0, self::MAX_PASSWORD_HISTORY);

        $user->update([
            'password_history' => json_encode($passwordHistory)
        ]);
    }

    public function handleFailedLogin(User $user): bool
    {
        $user->incrementLoginAttempts();
        return $user->failed_login_attempts >= self::MAX_FAILED_ATTEMPTS;
    }

    public function resetFailedAttempts(User $user): void
    {
        $user->resetLoginAttempts();
        $user->updateLastLogin();
    }

    public function isAccountLocked(User $user): bool
    {
        return $user->failed_login_attempts >= self::MAX_FAILED_ATTEMPTS;
    }

    public function checkPasswordStatus(User $user): array
    {
        if (!$user->password_changed_at) {
            return [
                'expired' => true,
                'days_left' => 0,
                'status' => 'expired'
            ];
        }

        $expiryDate = $user->password_changed_at->addDays(self::PASSWORD_EXPIRY_DAYS);
        $daysLeft = now()->diffInDays($expiryDate, false);
        
        if ($daysLeft <= 0) {
            return [
                'expired' => true,
                'days_left' => abs($daysLeft),
                'status' => 'expired'
            ];
        }

        if ($daysLeft <= self::PASSWORD_EXPIRY_WARNING_DAYS) {
            return [
                'expired' => false,
                'days_left' => $daysLeft,
                'status' => 'warning'
            ];
        }

        return [
            'expired' => false,
            'days_left' => $daysLeft,
            'status' => 'valid'
        ];
    }

    public function isPasswordChangeRequired(User $user): bool
    {
        $status = $this->checkPasswordStatus($user);
        return $status['expired'];
    }
} 