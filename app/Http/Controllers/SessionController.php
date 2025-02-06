<?php

namespace App\Http\Controllers;

use App\Models\Session;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * @group Session Management
 *
 * APIs for managing user sessions
 */
class SessionController extends Controller
{
    /**
     * List Sessions
     * 
     * Get all active sessions for the authenticated user.
     *
     * @authenticated
     *
     * @response 200 [
     *   {
     *     "id": "abc123",
     *     "ip_address": "192.168.1.1",
     *     "user_agent": "Mozilla/5.0...",
     *     "last_activity": "2024-03-20 10:00:00",
     *     "is_current_device": true
     *   }
     * ]
     */
    public function index(): JsonResponse
    {
        $userId = Auth::id();
        
        // Debug logları
        \Log::info('Session List Debug:', [
            'user_id' => $userId,
            'total_sessions' => Session::count(),
            'user_sessions' => Session::where('user_id', $userId)->count(),
            'current_session_id' => request()->cookie('session_id'),
            'all_user_sessions' => Session::where('user_id', $userId)
                ->get()
                ->map(function($s) {
                    return [
                        'id' => $s->id,
                        'last_activity' => date('Y-m-d H:i:s', $s->last_activity),
                        'payload' => $s->payload
                    ];
                })
        ]);
        
        $sessions = Session::where('user_id', $userId)
            ->get()
            ->map(function ($session) {
                $payload = json_decode($session->payload);
                return [
                    'id' => $session->id,
                    'ip_address' => $session->ip_address,
                    'user_agent' => $session->user_agent,
                    'last_activity' => date('Y-m-d H:i:s', $session->last_activity),
                    'is_current_device' => $session->id === request()->cookie('session_id'),
                    'remember_me' => $payload->remember_me ?? false,
                    'created_at' => $payload->created_at
                ];
            });

        return response()->json($sessions);
    }

    /**
     * Delete Session
     * 
     * Terminate a specific session.
     *
     * @authenticated
     * 
     * @urlParam id string required The ID of the session. Example: abc123
     *
     * @response 200 {
     *   "message": "Session terminated successfully"
     * }
     * @response 400 {
     *   "message": "Cannot delete current session"
     * }
     * @response 404 {
     *   "message": "Session not found"
     * }
     */
    public function destroy(string $id): JsonResponse
    {
        $session = Session::where('id', $id)
            ->where('user_id', Auth::id())
            ->first();

        if (!$session) {
            return response()->json(['message' => 'Session not found'], 404);
        }

        // Prevent deleting current session
        if ($session->id === request()->cookie('session_id')) {
            return response()->json(['message' => 'Cannot delete current session'], 400);
        }

        $session->delete();

        return response()->json(['message' => 'Session terminated successfully']);
    }
} 