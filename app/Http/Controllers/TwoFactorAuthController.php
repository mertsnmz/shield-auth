<?php

namespace App\Http\Controllers;

use App\Http\Requests\TwoFactorAuth\EnableRequest;
use App\Http\Requests\TwoFactorAuth\VerifyRequest;
use App\Http\Requests\TwoFactorAuth\DisableRequest;
use App\Services\TwoFactorAuth\TwoFactorAuthService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * @group Two Factor Authentication
 *
 * APIs for managing 2FA
 */
class TwoFactorAuthController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly TwoFactorAuthService $twoFactorAuth
    ) {
    }

    /**
     * Enable 2FA.
     *
     * Start the 2FA setup process.
     *
     * @param EnableRequest $request
     *
     * @return JsonResponse
     */
    public function enable(EnableRequest $request): JsonResponse
    {
        try {
            $result = $this->twoFactorAuth->enable(Auth::user());
            return $this->successResponse($result);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Verify 2FA.
     *
     * Verify and complete the 2FA setup.
     *
     * @param VerifyRequest $request
     *
     * @return JsonResponse
     */
    public function verify(VerifyRequest $request): JsonResponse
    {
        try {
            $this->twoFactorAuth->verify(Auth::user(), $request->code);
            return $this->successResponse(message: '2FA enabled successfully');
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Get Backup Codes.
     *
     * Get the list of backup codes.
     *
     * @return JsonResponse
     */
    public function getBackupCodes(): JsonResponse
    {
        try {
            $codes = $this->twoFactorAuth->getBackupCodes(Auth::user());
            return $this->successResponse(['recovery_codes' => $codes]);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Regenerate Backup Codes.
     *
     * Generate new backup codes.
     *
     * @return JsonResponse
     */
    public function regenerateBackupCodes(): JsonResponse
    {
        try {
            $codes = $this->twoFactorAuth->regenerateBackupCodes(Auth::user());
            return $this->successResponse(['recovery_codes' => $codes]);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Disable 2FA.
     *
     * Disable two-factor authentication for the user.
     *
     * @param DisableRequest $request
     *
     * @return JsonResponse
     */
    public function disable(DisableRequest $request): JsonResponse
    {
        try {
            $this->twoFactorAuth->disable(
                Auth::user(),
                $request->current_password,
                $request->code
            );

            Log::warning('2FA Disabled', [
                'user_id' => Auth::id(),
                'email' => Auth::user()->email,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return $this->successResponse(message: '2FA disabled successfully');
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode());
        }
    }
}
