<?php

namespace App\Repositories\Password;

use App\Models\User;
use Illuminate\Support\Facades\Password;
use Closure;

class PasswordRepository
{
    public function sendResetLink(array $credentials): string
    {
        return Password::sendResetLink($credentials);
    }

    public function findUserByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    public function updatePassword(User $user, string $password): void
    {
        $user->update([
            'password_hash' => $password,
            'password_changed_at' => now(),
        ]);
    }

    public function resetPassword(array $credentials, Closure $callback): string
    {
        return Password::reset($credentials, $callback);
    }
}
