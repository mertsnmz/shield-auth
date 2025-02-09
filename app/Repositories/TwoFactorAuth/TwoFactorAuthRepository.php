<?php

namespace App\Repositories\TwoFactorAuth;

use App\Models\User;
use App\Interfaces\TwoFactorAuth\ITwoFactorAuthRepository;

class TwoFactorAuthRepository implements ITwoFactorAuthRepository
{
    public function updateTwoFactorSecret(User $user, string $secret): void
    {
        $user->two_factor_secret = $secret;
        $user->two_factor_enabled = true;
        $user->save();
    }

    public function updateTwoFactorConfirmation(User $user): void
    {
        $user->two_factor_confirmed_at = now();
        $user->save();
    }

    public function updateRecoveryCodes(User $user, array $codes): void
    {
        $user->two_factor_recovery_codes = json_encode($codes);
        $user->save();
    }

    public function disableTwoFactor(User $user): void
    {
        $user->two_factor_secret = null;
        $user->two_factor_recovery_codes = null;
        $user->two_factor_confirmed_at = null;
        $user->two_factor_enabled = false;
        $user->save();
    }

    public function getRecoveryCodes(User $user): ?array
    {
        return $user->two_factor_recovery_codes ? json_decode($user->two_factor_recovery_codes, true) : null;
    }
}
