<?php

namespace App\Services\User;

use App\Models\User;
use App\Interfaces\User\IUserService;
use App\Interfaces\User\IUserRepository;
use App\Services\PasswordPolicyService;

class UserService implements IUserService
{
    public function __construct(
        private readonly IUserRepository $repository,
        private readonly PasswordPolicyService $passwordPolicy
    ) {
    }

    public function getProfileWithStatus(User $user): array
    {
        return [
            'id' => $user->id,
            'email' => $user->email,
            'last_login_at' => $user->last_login_at,
            'two_factor_enabled' => $user->two_factor_enabled,
            'password_status' => $this->passwordPolicy->checkPasswordStatus($user),
        ];
    }

    public function updateEmail(User $user, string $email): void
    {
        $this->repository->updateEmail($user, $email);
    }
} 