<?php

namespace App\Http\Controllers;

use App\Services\TwoFactorAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/**
 * @group Two Factor Authentication
 *
 * APIs for managing 2FA
 */
class TwoFactorAuthController extends Controller
{
    public function __construct(
        private readonly TwoFactorAuthService $twoFactorAuth
    ) {}

    /**
     * Enable 2FA
     * 
     * Start the 2FA setup process.
     *
     * @authenticated
     *
     * @response 200 {
     *   "secret": "KRSXG5CTMVRXEZLU",
     *   "qr_code": "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgA...",
     *   "recovery_codes": ["code1", "code2", "code3", ...]
     * }
     */
    public function enable(): JsonResponse
    {
        $user = Auth::user();

        if ($this->twoFactorAuth->isEnabled($user)) {
            return response()->json([
                'message' => '2FA is already enabled'
            ], 400);
        }

        $result = $this->twoFactorAuth->enable2FA($user);

        return response()->json($result);
    }

    /**
     * Verify 2FA
     * 
     * Verify and complete the 2FA setup.
     *
     * @authenticated
     * 
     * @bodyParam code string required The verification code. Example: 123456
     *
     * @response 200 {
     *   "message": "2FA enabled successfully"
     * }
     * @response 400 {
     *   "message": "Invalid verification code"
     * }
     */
    public function verify(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'size:6']
        ]);

        $user = Auth::user();

        if (!$user->two_factor_secret) {
            return response()->json([
                'message' => '2FA is not enabled'
            ], 400);
        }

        if ($this->twoFactorAuth->verifyCode($user->two_factor_secret, $validated['code'])) {
            $user->two_factor_confirmed_at = now();
            $user->save();

            return response()->json([
                'message' => '2FA enabled successfully'
            ]);
        }

        return response()->json([
            'message' => 'Invalid verification code'
        ], 400);
    }

    /**
     * Get Backup Codes
     * 
     * Get the list of backup codes.
     *
     * @authenticated
     *
     * @response 200 {
     *   "recovery_codes": ["code1", "code2", "code3", ...]
     * }
     */
    public function getBackupCodes(): JsonResponse
    {
        $user = Auth::user();

        if (!$this->twoFactorAuth->isEnabled($user)) {
            return response()->json([
                'message' => '2FA is not enabled'
            ], 400);
        }

        return response()->json([
            'recovery_codes' => json_decode($user->two_factor_recovery_codes, true)
        ]);
    }

    /**
     * Regenerate Backup Codes
     * 
     * Generate new backup codes.
     *
     * @authenticated
     *
     * @response 200 {
     *   "recovery_codes": ["code1", "code2", "code3", ...]
     * }
     */
    public function regenerateBackupCodes(): JsonResponse
    {
        $user = Auth::user();

        if (!$this->twoFactorAuth->isEnabled($user)) {
            return response()->json([
                'message' => '2FA is not enabled'
            ], 400);
        }

        $recoveryCodes = $this->twoFactorAuth->generateRecoveryCodes();
        $user->two_factor_recovery_codes = json_encode($recoveryCodes);
        $user->save();

        return response()->json([
            'recovery_codes' => $recoveryCodes
        ]);
    }

    /**
     * Disable 2FA
     * 
     * Disable two-factor authentication for the user.
     * Requires current password and 2FA code for security.
     *
     * @authenticated
     * 
     * @bodyParam current_password string required The user's current password. Example: MyPassword123
     * @bodyParam code string required Current 2FA code. Example: 123456
     *
     * @response 200 {
     *   "message": "2FA disabled successfully"
     * }
     * @response 400 {
     *   "message": "2FA is not enabled"
     * }
     * @response 401 {
     *   "message": "Invalid password"
     * }
     * @response 401 {
     *   "message": "Invalid 2FA code"
     * }
     */
    public function disable(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'code' => ['required', 'string', 'size:6']
        ]);

        $user = Auth::user();

        if (!$this->twoFactorAuth->isEnabled($user)) {
            return response()->json([
                'message' => '2FA is not enabled'
            ], 400);
        }

        // Şifre kontrolü
        if (!Hash::check($validated['current_password'], $user->password_hash)) {
            return response()->json([
                'message' => 'Invalid password'
            ], 401);
        }

        // 2FA kodu kontrolü
        if (!$this->twoFactorAuth->verifyCode($user->two_factor_secret, $validated['code'])) {
            return response()->json([
                'message' => 'Invalid 2FA code'
            ], 401);
        }

        $this->twoFactorAuth->disable2FA($user);

        // Log the action for security audit
        Log::warning('2FA Disabled', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent()
        ]);

        return response()->json([
            'message' => '2FA disabled successfully'
        ]);
    }
}
