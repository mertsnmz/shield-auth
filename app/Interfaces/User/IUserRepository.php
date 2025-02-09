<?php

namespace App\Interfaces\User;

use App\Models\User;

interface IUserRepository
{
    public function updateEmail(User $user, string $email): void;
} 