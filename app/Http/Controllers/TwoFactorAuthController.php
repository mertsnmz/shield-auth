<?php

namespace App\Http\Controllers;

use App\Http\Requests\TwoFactorAuth\EnableRequest;
use App\Http\Requests\TwoFactorAuth\VerifyRequest;
use App\Http\Requests\TwoFactorAuth\DisableRequest;
use App\Interfaces\TwoFactorAuth\ITwoFactorAuthService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * @group Two-Factor Authentication
 *
 * APIs for managing 2FA
 */
class TwoFactorAuthController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly ITwoFactorAuthService $twoFactorAuth
    ) {
    }

    /**
     * Enable 2FA.
     *
     * Start the 2FA setup process. Returns a QR code and backup codes.
     *
     * @param EnableRequest $request
     *
     * @response {
     *   "status": "success",
     *   "message": null,
     *   "data": {
     *     "secret": "4MTR3GAUEBK2MHDN",
     *     "qr_code": "data:image/svg+xml;base64,...",
     *     "recovery_codes": [
     *       "8c32180b485b674cd980",
     *       "8cdb2b3d6912b995db41",
     *       ...
     *     ]
     *   }
     * }
     *
     * @return JsonResponse
     */
    public function enable(EnableRequest $request): JsonResponse
    {
        try {
            $result = $this->twoFactorAuth->enable($request->user());

            return $this->successResponse($result);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Verify 2FA.
     *
     * Verify and complete the 2FA setup by confirming the verification code.
     *
     * @param VerifyRequest $request
     *
     * @bodyParam code string required The 6-digit verification code from your authenticator app. Example: 123456
     *
     * @response {
     *   "status": "success",
     *   "message": "2FA enabled successfully",
     *   "data": null
     * }
     *
     * @return JsonResponse
     */
    public function verify(VerifyRequest $request): JsonResponse
    {
        try {
            $this->twoFactorAuth->verify(
                $request->user(),
                $request->input('code')
            );

            return $this->successResponse(message: '2FA enabled successfully');
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Get Backup Codes.
     *
     * Get the list of backup codes for the authenticated user.
     *
     * @response {
     *   "status": "success",
     *   "message": null,
     *   "data": {
     *     "recovery_codes": [
     *       "8c32180b485b674cd980",
     *       "8cdb2b3d6912b995db41",
     *       ...
     *     ]
     *   }
     * }
     *
     * @return JsonResponse
     */
    public function getBackupCodes(): JsonResponse
    {
        try {
            $codes = $this->twoFactorAuth->getBackupCodes(request()->user());

            return $this->successResponse(['recovery_codes' => $codes]);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Regenerate Backup Codes.
     *
     * Generate new backup codes for the authenticated user. Previous backup codes will be invalidated.
     *
     * @response {
     *   "status": "success",
     *   "message": null,
     *   "data": {
     *     "recovery_codes": [
     *       "8c32180b485b674cd980",
     *       "8cdb2b3d6912b995db41",
     *       ...
     *     ]
     *   }
     * }
     *
     * @return JsonResponse
     */
    public function regenerateBackupCodes(): JsonResponse
    {
        try {
            $codes = $this->twoFactorAuth->regenerateBackupCodes(request()->user());

            return $this->successResponse(['recovery_codes' => $codes]);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Disable 2FA.
     *
     * Disable two-factor authentication for the authenticated user.
     *
     * @param DisableRequest $request
     *
     * @bodyParam current_password string required The current password of the user. Example: Test123!@#$%^&*
     * @bodyParam code string required The 6-digit verification code from your authenticator app. Example: 123456
     *
     * @response {
     *   "status": "success",
     *   "message": "2FA disabled successfully",
     *   "data": null
     * }
     *
     * @return JsonResponse
     */
    public function disable(DisableRequest $request): JsonResponse
    {
        try {
            $this->twoFactorAuth->disable(
                $request->user(),
                $request->input('current_password'),
                $request->input('code')
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
