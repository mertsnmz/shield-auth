<?php

namespace App\Repositories\TwoFactorAuth;

use App\Models\User;

class TwoFactorAuthRepository
{
    public function updateTwoFactorSecret(User $user, string $secret): void
    {
        $user->update([
            'two_factor_secret' => $secret,
            'two_factor_enabled' => true,
        ]);
    }

    public function updateTwoFactorConfirmation(User $user): void
    {
        $user->update([
            'two_factor_confirmed_at' => now(),
        ]);
    }

    public function updateRecoveryCodes(User $user, array $codes): void
    {
        $user->update([
            'two_factor_recovery_codes' => json_encode($codes),
        ]);
    }

    public function disableTwoFactor(User $user): void
    {
        $user->update([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_enabled' => false,
            'two_factor_confirmed_at' => null,
        ]);
    }

    public function getRecoveryCodes(User $user): ?array
    {
        return $user->two_factor_recovery_codes ?
            json_decode($user->two_factor_recovery_codes, true) :
            null;
    }
}
