<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Password\ForgotPasswordRequest;
use App\Http\Requests\Password\ResetPasswordRequest;
use App\Services\Password\PasswordService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class PasswordController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly PasswordService $service
    ) {}

    public function forgot(ForgotPasswordRequest $request): JsonResponse
    {
        try {
            $this->service->sendResetLink($request->validated());
            return $this->success(['message' => 'Password reset link sent successfully']);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), $e->getCode());
        }
    }

    public function reset(ResetPasswordRequest $request): JsonResponse
    {
        try {
            $this->service->reset($request->validated());
            return $this->success(['message' => 'Password reset successfully']);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), $e->getCode());
        }
    }
} 