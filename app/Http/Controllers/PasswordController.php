<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\PasswordPolicyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\JsonResponse;

/**
 * @group Password Management
 *
 * APIs for managing passwords
 */
class PasswordController extends Controller
{
    public function __construct(
        private readonly PasswordPolicyService $passwordPolicy
    ) {
    }

    /**
     * Update Password.
     *
     * Update the authenticated user's password.
     *
     * @authenticated
     *
     * @bodyParam current_password string required The current password. Example: CurrentPass123!
     * @bodyParam password string required The new password. Example: NewStrongPass123!
     * @bodyParam password_confirmation string required The new password confirmation. Example: NewStrongPass123!
     *
     * @response 200 {
     *   "message": "Password updated successfully"
     * }
     * @response 400 {
     *   "message": "Current password is incorrect"
     * }
     * @response 400 {
     *   "message": "Password was used before"
     * }
     * @response 400 {
     *   "message": "Password has expired"
     * }
     */
    public function update(Request $request): JsonResponse
    {
        $user = auth()->user();

        // Check if password change is required due to expiry
        if ($this->passwordPolicy->isPasswordChangeRequired($user)) {
            return response()->json([
                'message' => 'Password has expired',
                'status' => $this->passwordPolicy->checkPasswordStatus($user),
            ], 400);
        }

        $validated = $request->validate([
            'current_password' => ['required'],
            'password' => array_merge(
                ['required', 'confirmed'],
                [$this->passwordPolicy->getValidationRules()]
            ),
        ]);

        if (!Hash::check($validated['current_password'], $user->password_hash)) {
            return response()->json([
                'message' => 'Current password is incorrect',
            ], 400);
        }

        if ($this->passwordPolicy->wasUsedBefore($user, $validated['password'])) {
            return response()->json([
                'message' => 'Password was used before',
            ], 400);
        }

        $newPasswordHash = Hash::make($validated['password']);

        $user->password_hash = $newPasswordHash;
        $user->password_changed_at = now();
        $user->save();

        // Record the new password in history
        $this->passwordPolicy->recordPassword($user, $newPasswordHash);

        return response()->json([
            'message' => 'Password updated successfully',
            'status' => $this->passwordPolicy->checkPasswordStatus($user),
        ]);
    }

    /**
     * Forgot Password.
     *
     * Request a password reset for a user.
     *
     * @bodyParam email string required The email address. Example: user@example.com
     *
     * @response 200 {
     *   "message": "If the email exists in our system, a password reset link will be sent"
     * }
     */
    public function forgot(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        // For security reasons, we always return the same response
        // regardless of whether the email exists or not
        return response()->json([
            'message' => 'If the email exists in our system, a password reset link will be sent',
        ]);
    }

    /**
     * Reset Password.
     *
     * Reset a user's password.
     *
     * @bodyParam email string required The email address. Example: user@example.com
     * @bodyParam password string required The new password. Example: NewStrongPass123!
     * @bodyParam password_confirmation string required The password confirmation. Example: NewStrongPass123!
     *
     * @response 200 {
     *   "message": "Password has been reset"
     * }
     * @response 400 {
     *   "message": "Password was used before"
     * }
     * @response 404 {
     *   "message": "User not found"
     * }
     */
    public function reset(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => array_merge(
                ['required', 'confirmed'],
                [$this->passwordPolicy->getValidationRules()]
            ),
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'message' => 'User not found',
            ], 404);
        }

        if ($this->passwordPolicy->wasUsedBefore($user, $request->password)) {
            return response()->json([
                'message' => 'Password was used before',
            ], 400);
        }

        $newPasswordHash = Hash::make($request->password);

        $user->password_hash = $newPasswordHash;
        $user->password_changed_at = now();
        // Reset failed login attempts when password is reset
        $user->failed_login_attempts = 0;
        $user->save();

        // Record the new password in history
        $this->passwordPolicy->recordPassword($user, $newPasswordHash);

        return response()->json([
            'message' => 'Password has been reset',
            'status' => $this->passwordPolicy->checkPasswordStatus($user),
        ]);
    }
}
