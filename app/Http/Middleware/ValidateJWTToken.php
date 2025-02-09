<?php

namespace App\Http\Middleware;

use App\Models\OAuthAccessToken;
use App\Services\JWT\JWTService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateJWTToken
{
    public function __construct(
        private readonly JWTService $jwtService
    ) {
    }

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @param string ...$scopes
     * @return Response
     */
    public function handle(Request $request, Closure $next, ...$scopes): Response
    {
        $bearerToken = $request->bearerToken();

        if (!$bearerToken) {
            return response()->json([
                'error' => 'invalid_token',
                'error_description' => 'The access token is missing'
            ], 401);
        }

        // JWT token'ı doğrula
        $token = $this->jwtService->validateToken($bearerToken);

        if (!$token) {
            return response()->json([
                'error' => 'invalid_token',
                'error_description' => 'The access token is invalid'
            ], 401);
        }

        // Token'ın veritabanı kaydını kontrol et
        $jti = $this->jwtService->getJtiFromToken($token);
        $accessToken = OAuthAccessToken::where('access_token', $jti)
            ->where('revoked', false)
            ->where('expires_at', '>', now())
            ->first();

        if (!$accessToken) {
            return response()->json([
                'error' => 'invalid_token',
                'error_description' => 'The access token has been revoked or expired'
            ], 401);
        }

        // Scope kontrolü
        if (!empty($scopes)) {
            $tokenScopes = explode(' ', $accessToken->scope ?? '');
            $hasValidScope = !empty(array_intersect($scopes, $tokenScopes));

            if (!$hasValidScope) {
                return response()->json([
                    'error' => 'insufficient_scope',
                    'error_description' => 'The access token does not have the required scope'
                ], 403);
            }
        }

        // Token bilgilerini request'e ekle
        $request->merge([
            'oauth_access_token_id' => $accessToken->id,
            'oauth_client_id' => $accessToken->client_id,
            'oauth_user_id' => $accessToken->user_id,
            'oauth_scopes' => $accessToken->scope ? explode(' ', $accessToken->scope) : [],
        ]);

        return $next($request);
    }
} 