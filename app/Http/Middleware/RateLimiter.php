<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter as LaravelRateLimiter;
use Illuminate\Support\Facades\Log;
use Illuminate\Cache\RateLimiting\Limit;
use Symfony\Component\HttpFoundation\Response;

class RateLimiter
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $limiterName = 'api'): Response
    {
        $key = $this->resolveRequestSignature($request, $limiterName);
        $limits = $this->getRateLimits($limiterName);

        // Debug log
        Log::debug('Rate Limit Check', [
            'key' => $key,
            'limiter' => $limiterName,
            'max_attempts' => $limits['maxAttempts'],
            'decay_minutes' => $limits['decayMinutes'],
            'remaining' => LaravelRateLimiter::remaining($key, $limits['maxAttempts']),
            'ip' => $request->ip(),
            'path' => $request->path(),
            'user_id' => $request->user()?->id,
        ]);

        if (LaravelRateLimiter::tooManyAttempts($key, $limits['maxAttempts'])) {
            $seconds = LaravelRateLimiter::availableIn($key);

            Log::warning('Rate Limit Exceeded', [
                'key' => $key,
                'limiter' => $limiterName,
                'retry_after' => $seconds,
                'ip' => $request->ip(),
                'path' => $request->path(),
            ]);

            return response()->json([
                'error' => 'Too Many Requests',
                'message' => 'Please wait before retrying',
                'retry_after' => $seconds,
            ], 429)->withHeaders([
                'Retry-After' => $seconds,
                'X-RateLimit-Limit' => $limits['maxAttempts'],
                'X-RateLimit-Remaining' => 0,
            ]);
        }

        LaravelRateLimiter::hit($key, $limits['decayMinutes'] * 60);

        $response = $next($request);

        return $this->addRateLimitHeaders(
            $response,
            LaravelRateLimiter::remaining($key, $limits['maxAttempts']),
            $limits['maxAttempts']
        );
    }

    /**
     * Resolve request signature.
     */
    protected function resolveRequestSignature(Request $request, string $limiterName): string
    {
        return sha1(implode('|', [
            $limiterName,
            $request->ip(),
            $request->userAgent(),
            $request->user()?->id ?? 'guest',
        ]));
    }

    /**
     * Get rate limits based on limiter name.
     */
    protected function getRateLimits(string $limiterName): array
    {
        return match($limiterName) {
            // Strict limit for Login and Register (Brute force prevention)
            'login' => [
                'maxAttempts' => 5,      // 5 attempts allowed
                'decayMinutes' => 1,      // 1 minute wait
            ],
            // Strict limit for 2FA
            '2fa' => [
                'maxAttempts' => 3,      // 3 attempts allowed
                'decayMinutes' => 5,      // 5 minutes wait
            ],
            // Extended limit for password reset
            'password-reset' => [
                'maxAttempts' => 3,      // 3 attempts allowed
                'decayMinutes' => 60,     // 1 hour wait
            ],
            // Medium level limit for OAuth token operations
            'oauth-token' => [
                'maxAttempts' => 10,     // 10 requests allowed
                'decayMinutes' => 1,      // 1 minute wait
            ],
            // Standard limit for general API requests
            default => [
                'maxAttempts' => 60,     // 60 requests per minute
                'decayMinutes' => 1,      // 1 minute wait
            ]
        };
    }

    /**
     * Add rate limit headers to response.
     */
    protected function addRateLimitHeaders(Response $response, int $remaining, int $limit): Response
    {
        return $response->withHeaders([
            'X-RateLimit-Limit' => $limit,
            'X-RateLimit-Remaining' => $remaining,
        ]);
    }
}
