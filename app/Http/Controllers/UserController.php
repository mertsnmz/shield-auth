<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\PasswordPolicyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

/**
 * @group User Management
 *
 * APIs for managing user profile
 */
class UserController extends Controller
{
    public function __construct(
        private readonly PasswordPolicyService $passwordPolicy
    ) {
    }

    /**
     * Get Current User.
     *
     * Get the authenticated user's profile information.
     *
     * @authenticated
     *
     * @response 200 {
     *   "user": {
     *     "id": 1,
     *     "email": "user@example.com",
     *     "last_login_at": "2024-03-20T10:00:00Z",
     *     "two_factor_enabled": false,
     *     "password_status": {
     *       "expired": false,
     *       "days_left": 45,
     *       "status": "valid"
     *     }
     *   }
     * }
     */
    public function me(): JsonResponse
    {
        $user = Auth::user();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'last_login_at' => $user->last_login_at,
                'two_factor_enabled' => $user->two_factor_enabled,
                'password_status' => $this->passwordPolicy->checkPasswordStatus($user),
            ],
        ]);
    }

    /**
     * Update Profile.
     *
     * Update the authenticated user's profile information.
     *
     * @authenticated
     *
     * @bodyParam email string The new email address. Example: newuser@example.com
     *
     * @response 200 {
     *   "message": "Profile updated successfully",
     *   "user": {
     *     "id": 1,
     *     "email": "newuser@example.com"
     *   }
     * }
     * @response 422 {
     *   "message": "The given data was invalid.",
     *   "errors": {
     *     "email": ["The email has already been taken."]
     *   }
     * }
     */
    public function update(Request $request): JsonResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'email' => [
                'sometimes',
                'email',
                Rule::unique('users')->ignore($user->id),
            ],
        ]);

        $user->update($validated);

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
            ],
        ]);
    }
}
