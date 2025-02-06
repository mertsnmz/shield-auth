<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Session;
use App\Services\PasswordPolicyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Http\JsonResponse;

/**
 * @group Authentication
 *
 * APIs for managing authentication
 */
class AuthController extends Controller
{
    private const MAX_ACTIVE_SESSIONS = 4;

    public function __construct(
        private readonly PasswordPolicyService $passwordPolicy
    ) {}

    /**
     * Login
     * 
     * Authenticate a user and create a new session.
     *
     * @bodyParam email string required The email of the user. Example: user@example.com
     * @bodyParam password string required The password of the user. Example: password123
     * @bodyParam remember_me boolean optional Remember me option. Example: true
     *
     * @response 200 {
     *   "message": "Logged in successfully",
     *   "session_id": "abc123",
     *   "password_status": {
     *     "expired": false,
     *     "days_left": 45,
     *     "status": "valid"
     *   }
     * }
     * @response 401 {
     *   "message": "Invalid credentials"
     * }
     * @response 401 {
     *   "message": "Account is locked due to too many failed attempts"
     * }
     * @response 403 {
     *   "message": "Password change required",
     *   "password_expired": true
     * }
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
            'remember_me' => ['sometimes', 'boolean']
        ]);

        // remember_me'yi credentials'dan çıkar
        $remember_me = $request->boolean('remember_me', false);
        unset($credentials['remember_me']);

        $user = User::where('email', $credentials['email'])->first();

        if (!$user) {
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        // Check if account is locked
        if ($this->passwordPolicy->isAccountLocked($user)) {
            return response()->json([
                'message' => 'Account is locked due to too many failed attempts'
            ], 401);
        }

        if (!Auth::attempt($credentials)) {
            $this->passwordPolicy->handleFailedLogin($user);
            
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        // Reset failed attempts and update last login
        $this->passwordPolicy->resetFailedAttempts($user);

        // Check if password change is required
        if ($this->passwordPolicy->isPasswordChangeRequired($user)) {
            return response()->json([
                'message' => 'Password change required',
                'password_expired' => true
            ], 403);
        }

        // Check for maximum active sessions
        $activeSessions = Session::where('user_id', $user->id)->count();
        
        // Debug logları
        \Log::info('Login Session Debug:', [
            'user_id' => $user->id,
            'active_sessions_before' => $activeSessions,
            'max_sessions' => self::MAX_ACTIVE_SESSIONS,
            'current_session_id' => $request->cookie('session_id'),
            'remember_me' => $remember_me
        ]);

        // Session Fixation Protection: Delete session from current device
        $currentDeviceSession = Session::where('user_id', $user->id)
            ->where('ip_address', $request->ip())
            ->where('user_agent', $request->userAgent())
            ->first();

        if ($currentDeviceSession) {
            \Log::info('Session Fixation Protection: Deleting session from current device', [
                'session_id' => $currentDeviceSession->id,
                'ip_address' => $currentDeviceSession->ip_address,
                'user_agent' => $currentDeviceSession->user_agent
            ]);
            $currentDeviceSession->delete();
            $activeSessions--; // Decrease count as we deleted one
        }

        // Delete oldest session if maximum is reached
        if ($activeSessions >= self::MAX_ACTIVE_SESSIONS) {
            $oldestSession = Session::where('user_id', $user->id)
                ->orderBy('last_activity', 'asc')
                ->first();
                
            \Log::info('Deleting oldest session due to max limit:', [
                'session_id' => $oldestSession->id,
                'last_activity' => date('Y-m-d H:i:s', $oldestSession->last_activity)
            ]);
            
            $oldestSession->delete();
        }

        // Calculate cookie lifetime based on remember_me
        $cookieLifetime = $remember_me ? 30 * 24 * 60 : 24 * 60; // 30 days or 24 hours

        // Create new session with regenerated ID for Session Fixation Protection
        $session = Session::create([
            'id' => Str::random(40), // Her zaman yeni bir random ID
            'user_id' => $user->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'payload' => json_encode([
                'user_id' => $user->id,
                'created_at' => now(),
                'remember_me' => $remember_me,
                'device_fingerprint' => hash('sha256', $request->ip() . $request->userAgent()) // Ek güvenlik
            ]),
            'last_activity' => time()
        ]);

        return response()->json([
            'message' => 'Logged in successfully',
            'session_id' => $session->id,
            'password_status' => $this->passwordPolicy->checkPasswordStatus($user)
        ])->withCookie(
            cookie('session_id', $session->id, $cookieLifetime)
        );
    }

    /**
     * Register
     * 
     * Register a new user account.
     *
     * @bodyParam email string required The email address. Example: user@example.com
     * @bodyParam password string required The password (must meet password policy requirements). Example: StrongPass123!
     * @bodyParam password_confirmation string required The password confirmation. Example: StrongPass123!
     *
     * @response 201 {
     *   "message": "User registered successfully"
     * }
     * @response 422 {
     *   "message": "The given data was invalid.",
     *   "errors": {
     *     "email": ["The email has already been taken."],
     *     "password": ["The password must be at least 8 characters."]
     *   }
     * }
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => array_merge(
                ['required', 'confirmed'],
                [$this->passwordPolicy->getValidationRules()]
            ),
        ]);

        $user = User::create([
            'email' => $validated['email'],
            'password_hash' => Hash::make($validated['password']),
            'password_changed_at' => now(),
        ]);

        // Record password in history
        $this->passwordPolicy->recordPassword($user, $user->password_hash);

        return response()->json([
            'message' => 'User registered successfully'
        ], 201);
    }

    /**
     * Logout
     * 
     * Invalidate the current session.
     *
     * @authenticated
     *
     * @response 200 {
     *   "message": "Logged out successfully"
     * }
     */
    public function logout(Request $request): JsonResponse
    {
        $sessionId = $request->cookie('session_id');
        
        Session::where('id', $sessionId)->delete();
        
        return response()->json([
            'message' => 'Logged out successfully'
        ])->withoutCookie('session_id');
    }
} 