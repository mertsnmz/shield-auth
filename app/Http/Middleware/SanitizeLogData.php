<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class SanitizeLogData
{
    private $sensitiveFields = [
        'password',
        'password_confirmation',
        'current_password',
        'token',
        'access_token',
        'refresh_token',
        'client_secret',
        'two_factor_secret',
        'credit_card',
        'card_number',
        'cvv',
        'social_security',
        'authorization'
    ];

    public function handle(Request $request, Closure $next): Response
    {
        // Log'lar için temizlenmiş veriyi hazırla
        $sanitizedData = $this->sanitize($request->all());
        
        // Orijinal request verilerini değiştirmeden, log context'i için temizlenmiş veriyi ayarla
        Log::shareContext([
            'request' => $sanitizedData,
            'headers' => collect($request->headers->all())
                ->map(function($value, $key) {
                    return in_array(strtolower($key), $this->sensitiveFields)
                        ? '[REDACTED]'
                        : $value;
                })
                ->toArray()
        ]);

        return $next($request);
    }

    private function sanitize(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->sanitize($value);
            } else if (is_string($key) && in_array(strtolower($key), $this->sensitiveFields)) {
                $data[$key] = '[REDACTED]';
            }
        }

        return $data;
    }
} 