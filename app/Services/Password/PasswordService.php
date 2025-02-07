<?php

namespace App\Services\Password;

use App\Models\User;
use App\Repositories\Password\PasswordRepository;
use App\Services\PasswordPolicyService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password as PasswordBroker;

class PasswordService
{
    public function __construct(
        private readonly PasswordRepository $repository,
        private readonly PasswordPolicyService $passwordPolicy
    ) {}

    public function sendResetLink(array $credentials): void
    {
        $status = $this->repository->sendResetLink($credentials);

        if ($status !== PasswordBroker::RESET_LINK_SENT) {
            throw new \Exception('Unable to send reset link', 400);
        }
    }

    public function reset(array $credentials): void
    {
        $user = $this->repository->findUserByEmail($credentials['email']);

        if (!$user) {
            throw new \Exception('User not found', 404);
        }

        if ($this->passwordPolicy->wasUsedBefore($user, $credentials['password'])) {
            throw new \Exception('Password was used before', 400);
        }

        $status = $this->repository->resetPassword(
            $credentials,
            function (User $user, string $password) {
                $hashedPassword = Hash::make($password);
                $this->repository->updatePassword($user, $hashedPassword);
                $this->passwordPolicy->recordPassword($user, $hashedPassword);
            }
        );

        if ($status !== PasswordBroker::PASSWORD_RESET) {
            throw new \Exception('Unable to reset password', 400);
        }
    }
} 