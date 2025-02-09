<?php

namespace App\Interfaces\User;

use App\Models\User;

interface IUserService
{
    public function getProfileWithStatus(User $user): array;
    public function updateEmail(User $user, string $email): void;
}
