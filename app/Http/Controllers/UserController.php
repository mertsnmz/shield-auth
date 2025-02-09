<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\UpdateProfileRequest;
use App\Interfaces\User\IUserService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * @group User Management
 *
 * APIs for managing user profile
 */
class UserController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly IUserService $userService
    ) {
    }

    /**
     * Get Current User.
     *
     * Get the authenticated user's profile information.
     *
     * @authenticated
     *
     * @response {
     *   "status": "success",
     *   "message": null,
     *   "data": {
     *     "user": {
     *       "id": 1,
     *       "email": "user@example.com",
     *       "last_login_at": "2024-03-20T10:00:00Z",
     *       "two_factor_enabled": false,
     *       "password_status": {
     *         "expired": false,
     *         "days_left": 45,
     *         "status": "valid"
     *       }
     *     }
     *   }
     * }
     */
    public function me(): JsonResponse
    {
        return $this->successResponse([
            'user' => $this->userService->getProfileWithStatus(request()->user())
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
     * @response {
     *   "status": "success",
     *   "message": "Profile updated successfully",
     *   "data": {
     *     "user": {
     *       "id": 1,
     *       "email": "newuser@example.com"
     *     }
     *   }
     * }
     * @response 422 {
     *   "status": "error",
     *   "message": "The given data was invalid.",
     *   "errors": {
     *     "email": ["The email has already been taken."]
     *   }
     * }
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        try {
            $this->userService->updateEmail(
                $request->user(),
                $request->input('email')
            );

            return $this->successResponse(
                [
                    'user' => [
                        'id' => $request->user()->id,
                        'email' => $request->user()->email,
                    ]
                ],
                'Profile updated successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode());
        }
    }
}
