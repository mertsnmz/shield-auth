<?php

namespace App\Repositories\User;

use App\Models\User;
use App\Interfaces\User\IUserRepository;

class UserRepository implements IUserRepository
{
    public function updateEmail(User $user, string $email): void
    {
        $user->update(['email' => $email]);
    }
}
