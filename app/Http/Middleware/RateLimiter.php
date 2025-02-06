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
            'user_id' => $request->user()?->id
        ]);

        if (LaravelRateLimiter::tooManyAttempts($key, $limits['maxAttempts'])) {
            $seconds = LaravelRateLimiter::availableIn($key);
            
            Log::warning('Rate Limit Exceeded', [
                'key' => $key,
                'limiter' => $limiterName,
                'retry_after' => $seconds,
                'ip' => $request->ip(),
                'path' => $request->path()
            ]);

            return response()->json([
                'error' => 'Too Many Requests',
                'message' => 'Please wait before retrying',
                'retry_after' => $seconds
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
            $request->user()?->id ?? 'guest'
        ]));
    }

    /**
     * Get rate limits based on limiter name.
     */
    protected function getRateLimits(string $limiterName): array
    {
        return match($limiterName) {
            // Login ve Register için sıkı limit (Brute force önlemi)
            'login' => [
                'maxAttempts' => 5,      // 5 deneme hakkı
                'decayMinutes' => 1      // 1 dakika bekleme
            ],
            // 2FA için makul limit
            '2fa' => [
                'maxAttempts' => 5,      // 5 deneme hakkı
                'decayMinutes' => 5      // 5 dakika bekleme
            ],
            // Şifre sıfırlama için daha geniş limit
            'password-reset' => [
                'maxAttempts' => 3,      // 3 deneme hakkı
                'decayMinutes' => 60     // 1 saat bekleme
            ],
            // OAuth token işlemleri için orta seviye limit
            'oauth-token' => [
                'maxAttempts' => 10,     // 10 istek hakkı
                'decayMinutes' => 1      // 1 dakika bekleme
            ],
            // Genel API istekleri için standart limit
            default => [
                'maxAttempts' => 60,     // Dakikada 60 istek
                'decayMinutes' => 1      // 1 dakika bekleme
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