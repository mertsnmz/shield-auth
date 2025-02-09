<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Models\Session;
use App\Repositories\Auth\AuthRepository;
use App\Services\PasswordPolicyService;
use App\Services\TwoFactorAuthService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Auth\Events\Registered;
use Exception;

class AuthService
{
    private const MAX_ACTIVE_SESSIONS = 4;
    private const ABSOLUTE_TIMEOUT = 86400; // 24 saat (saniye cinsinden)
    private const SLIDING_TIMEOUT = 1800; // 30 dakika (saniye cinsinden)

    public function __construct(
        private readonly PasswordPolicyService $passwordPolicy,
        private readonly TwoFactorAuthService $twoFactorAuth,
        private readonly AuthRepository $authRepository
    ) {
    }

    private function isSessionValid(Session $session): bool
    {
        $now = time();
        $lastActivity = $session->last_activity;
        $createdAt = strtotime($session->created_at);

        // Absolute timeout kontrolü - session oluşturulduğundan beri geçen süre
        if (($now - $createdAt) > self::ABSOLUTE_TIMEOUT) {
            return false;
        }

        // Sliding timeout kontrolü - son aktiviteden beri geçen süre
        if (($now - $lastActivity) > self::SLIDING_TIMEOUT) {
            return false;
        }

        return true;
    }

    private function updateSessionActivity(Session $session): void
    {
        $session->last_activity = time();
        $session->save();
    }

    public function login(array $credentials, bool $remember = false): array
    {
        $user = $this->authRepository->findUserByEmail($credentials['email']);

        if (!$user) {
            throw new Exception('Invalid credentials', 401);
        }

        if ($this->passwordPolicy->isAccountLocked($user)) {
            throw new Exception('Account is locked due to too many failed attempts', 401);
        }

        if (!Auth::attempt(['email' => $credentials['email'], 'password' => $credentials['password']])) {
            $this->passwordPolicy->handleFailedLogin($user);
            throw new Exception('Invalid credentials', 401);
        }

        $this->passwordPolicy->resetFailedAttempts($user);

        if ($this->passwordPolicy->isPasswordChangeRequired($user)) {
            throw new Exception('Password change required', 403);
        }

        // 2FA Check
        if ($user->two_factor_enabled && $user->two_factor_confirmed_at) {
            // Check admin bypass for 2FA
            if (!$user->isAdmin()) {
                if (!isset($credentials['2fa_code'])) {
                    return [
                        'requires_2fa' => true,
                        'message' => '2FA code required',
                    ];
                }

                if (!$this->twoFactorAuth->verifyCode($user->two_factor_secret, $credentials['2fa_code'])) {
                    return [
                        'requires_2fa' => true,
                        'message' => 'Invalid 2FA code',
                    ];
                }
            }
        }

        // Handle session management
        $session = $this->createSession($user, $remember);

        // Update session activity
        $this->updateSessionActivity($session);

        return [
            'message' => 'Logged in successfully',
            'session_id' => $session->id,
            'password_status' => $this->passwordPolicy->checkPasswordStatus($user),
            'requires_2fa' => false,
        ];
    }

    public function register(array $data): array
    {
        $user = $this->authRepository->createUser([
            'email' => $data['email'],
            'password_hash' => Hash::make($data['password']),
            'password_changed_at' => now(),
        ]);

        $this->passwordPolicy->recordPassword($user, $user->password_hash);

        event(new Registered($user));

        // Create session for the new user
        $session = $this->createSession($user, false);

        return [
            'message' => 'User registered successfully',
            'session_id' => $session->id,
        ];
    }

    private function createSession(User $user, bool $remember): Session
    {
        $activeSessions = $this->authRepository->countActiveSessions($user->id);

        // Delete expired sessions
        $this->cleanExpiredSessions($user->id);

        // Delete current device session if exists
        $currentDeviceSession = $this->authRepository->findSessionByDeviceInfo(
            $user->id,
            request()->ip(),
            request()->userAgent()
        );

        if ($currentDeviceSession) {
            $this->authRepository->deleteSession($currentDeviceSession);
            $activeSessions--;
        }

        // Delete oldest session if maximum is reached
        if ($activeSessions >= self::MAX_ACTIVE_SESSIONS) {
            $oldestSession = $this->authRepository->getOldestSession($user->id);
            if ($oldestSession) {
                $this->authRepository->deleteSession($oldestSession);
            }
        }

        // Create new session
        return $this->authRepository->createSession([
            'id' => Str::random(40),
            'user_id' => $user->id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'payload' => json_encode([
                'user_id' => $user->id,
                'created_at' => now(),
                'remember_me' => $remember,
                'device_fingerprint' => hash('sha256', request()->ip() . request()->userAgent()),
            ]),
            'last_activity' => time(),
        ]);
    }

    private function cleanExpiredSessions(int $userId): void
    {
        $sessions = $this->authRepository->getAllSessions($userId);
        foreach ($sessions as $session) {
            if (!$this->isSessionValid($session)) {
                $this->authRepository->deleteSession($session);
            }
        }
    }

    public function logout(string $sessionId): void
    {
        $session = $this->authRepository->findSessionById($sessionId);

        if (!$session) {
            throw new Exception('Session not found', 404);
        }

        $this->authRepository->deleteSession($session);
    }
}
