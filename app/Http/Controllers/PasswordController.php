<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\PasswordPolicyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
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
    ) {}

    /**
     * Forgot Password
     * 
     * Send a password reset link to the given email.
     *
     * @bodyParam email string required The email address. Example: user@example.com
     *
     * @response 200 {
     *   "message": "Password reset link sent"
     * }
     * @response 400 {
     *   "message": "Unable to send reset link"
     * }
     */
    public function forgot(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json([
                'message' => 'Password reset link sent'
            ]);
        }

        return response()->json([
            'message' => 'Unable to send reset link'
        ], 400);
    }

    /**
     * Reset Password
     * 
     * Reset the password using the reset token.
     *
     * @bodyParam token string required The reset token. Example: 1234567890
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
     * @response 400 {
     *   "message": "Unable to reset password"
     * }
     * @response 404 {
     *   "message": "User not found"
     * }
     */
    public function reset(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => array_merge(
                ['required', 'confirmed'],
                [$this->passwordPolicy->getValidationRules()]
            ),
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'message' => 'User not found'
            ], 404);
        }

        if ($this->passwordPolicy->wasUsedBefore($user, $request->password)) {
            return response()->json([
                'message' => 'Password was used before'
            ], 400);
        }

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->password_hash = Hash::make($password);
                $user->password_changed_at = now();
                $user->save();

                $this->passwordPolicy->recordPassword($user, $user->password_hash);
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'message' => 'Password has been reset'
            ]);
        }

        return response()->json([
            'message' => 'Unable to reset password'
        ], 400);
    }
} 