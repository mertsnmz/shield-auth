<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Session;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateSession
{
    // Session timeout constants
    private const IDLE_TIMEOUT = 24 * 60 * 60; // 24 hours in seconds
    private const ABSOLUTE_TIMEOUT = 7 * 24 * 60 * 60; // 7 days in seconds

    public function handle(Request $request, Closure $next): Response
    {
        $sessionId = $request->cookie('session_id');

        if (!$sessionId) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $session = Session::where('id', $sessionId)->first();

        if (!$session) {
            return response()->json(['message' => 'Session not found'], 401);
        }

        // Check idle timeout
        if (time() - $session->last_activity > self::IDLE_TIMEOUT) {
            $session->delete();
            return response()->json(['message' => 'Session expired due to inactivity'], 401);
        }

        // Check absolute timeout
        $createdAt = json_decode($session->payload)->created_at;
        if (now()->diffInSeconds($createdAt) > self::ABSOLUTE_TIMEOUT) {
            $session->delete();
            return response()->json(['message' => 'Session expired. Please login again'], 401);
        }

        // Get user and login
        $user = User::find($session->user_id);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 401);
        }

        Auth::login($user);

        // Update last activity
        $session->update([
            'last_activity' => time(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return $next($request);
    }
}
