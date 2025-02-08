<?php

namespace App\Http\Controllers\OAuth;

use App\Http\Controllers\Controller;
use App\Models\OAuthClient;
use App\Models\OAuthAccessToken;
use App\Models\OAuthAuthCode;
use App\Models\OAuthRefreshToken;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

/**
 * @group OAuth2 Authentication
 *
 * APIs for OAuth2 authentication flows
 */
class OAuthController extends Controller
{
    private const ACCESS_TOKEN_LIFETIME = 3600; // 1 hour
    private const REFRESH_TOKEN_LIFETIME = 1209600; // 14 days
    private const AUTH_CODE_LIFETIME = 600; // 10 minutes

    /**
     * Issue Token.
     *
     * Issue an access token using one of the supported grant types:
     * - authorization_code
     * - client_credentials
     * - refresh_token
     *
     * @bodyParam grant_type string required The grant type. Example: authorization_code
     * @bodyParam client_id string required The client ID. Example: test-client
     * @bodyParam client_secret string required The client secret. Example: client-secret
     * @bodyParam code string required for authorization_code The authorization code. Example: def50200...
     * @bodyParam redirect_uri string required for authorization_code The redirect URI. Example: http://localhost:3000/callback
     * @bodyParam scope string The requested scope. Example: profile email
     *
     * @response 200 scenario="Authorization Code Grant" {
     *   "token_type": "Bearer",
     *   "expires_in": 3600,
     *   "access_token": "eyJ0eXAiOiJKV1QiLCJhbG...",
     *   "refresh_token": "def50200841d3e9ad...",
     *   "scope": "profile email"
     * }
     * 
     * @response 200 scenario="Client Credentials Grant" {
     *   "token_type": "Bearer",
     *   "expires_in": 3600,
     *   "access_token": "eyJ0eXAiOiJKV1QiLCJhbG...",
     *   "scope": "api.read"
     * }
     * 
     * @response 400 scenario="Invalid Request" {
     *   "error": "invalid_request",
     *   "error_description": "The request is missing a required parameter"
     * }
     * 
     * @response 401 scenario="Invalid Client" {
     *   "error": "invalid_client",
     *   "error_description": "Client authentication failed"
     * }
     * 
     * @response 400 scenario="Invalid Grant" {
     *   "error": "invalid_grant",
     *   "error_description": "The authorization code is invalid"
     * }
     */
    public function issueToken(Request $request): JsonResponse
    {
        // Validate basic requirements
        $validator = Validator::make($request->all(), [
            'grant_type' => ['required', 'string', 'in:authorization_code,client_credentials,refresh_token'],
            'client_id' => ['required', 'string'],
            'client_secret' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'invalid_request',
                'error_description' => 'The request is missing a required parameter',
            ], 400);
        }

        // Validate client credentials
        $client = OAuthClient::where('client_id', $request->client_id)
            ->where('client_secret', hash('sha256', $request->client_secret))
            ->first();

        if (!$client) {
            return response()->json([
                'error' => 'invalid_client',
                'error_description' => 'Client authentication failed',
            ], 401);
        }

        // Handle different grant types
        return match($request->grant_type) {
            'authorization_code' => $this->handleAuthorizationCode($request, $client),
            'client_credentials' => $this->handleClientCredentials($request, $client),
            'refresh_token' => $this->handleRefreshToken($request, $client),
            default => response()->json([
                'error' => 'unsupported_grant_type',
                'error_description' => 'The authorization grant type is not supported',
            ], 400)
        };
    }

    /**
     * Authorize.
     *
     * First step of Authorization Code flow. Shows authorization form to the user.
     *
     * @queryParam client_id string required The client ID. Example: test-client
     * @queryParam redirect_uri string required The redirect URI. Example: http://localhost:3000/callback
     * @queryParam response_type string required Must be "code". Example: code
     * @queryParam scope string The requested scope. Example: profile email
     * @queryParam state string A random string to prevent CSRF. Example: xyz123
     *
     * @response 200 {
     *   "client": {
     *     "name": "Test Client",
     *     "redirect_uri": "http://localhost:3000/callback"
     *   },
     *   "scopes": [
     *     {
     *       "name": "profile",
     *       "description": "Access user profile information"
     *     }
     *   ]
     * }
     * 
     * @response 400 scenario="Invalid Request" {
     *   "error": "invalid_request",
     *   "error_description": "The request is missing a required parameter"
     * }
     * 
     * @response 400 scenario="Invalid Client" {
     *   "error": "invalid_client",
     *   "error_description": "Client not found or redirect URI mismatch"
     * }
     * 
     * @response 401 scenario="Unauthenticated" {
     *   "message": "Unauthenticated"
     * }
     * 
     * @response 200 scenario="Example Request" {
     *   "client_id": "test-client",
     *   "redirect_uri": "http://localhost:3000/callback",
     *   "scope": "profile email",
     *   "state": "xyz123"
     * }
     */
    public function authorize(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'client_id' => ['required', 'string'],
            'redirect_uri' => ['required', 'string', 'url'],
            'response_type' => ['required', 'string', 'in:code'],
            'scope' => ['sometimes', 'string'],
            'state' => ['sometimes', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'invalid_request',
                'error_description' => 'The request is missing a required parameter',
            ], 400);
        }

        // Get client
        $client = OAuthClient::where('client_id', $request->client_id)
            ->where('redirect_uri', $request->redirect_uri)
            ->first();

        if (!$client) {
            return response()->json([
                'error' => 'invalid_client',
                'error_description' => 'Client not found or redirect URI mismatch',
            ], 400);
        }

        // Get available scopes for this client
        $scopes = $client->scopes()
            ->where('grant_type', 'authorization_code')
            ->orWhereNull('grant_type')
            ->get();

        return response()->json([
            'client' => [
                'name' => $client->name,
                'redirect_uri' => $client->redirect_uri,
            ],
            'scopes' => $scopes->map(fn ($scope) => [
                'name' => $scope->name,
                'description' => $scope->description,
            ]),
        ]);
    }

    /**
     * Approve Authorization.
     *
     * Second step of Authorization Code flow. Creates authorization code after user approval.
     *
     * @bodyParam client_id string required The client ID. Example: test-client
     * @bodyParam redirect_uri string required The redirect URI. Example: http://localhost:3000/callback
     * @bodyParam scope string The approved scope. Example: profile email
     * @bodyParam state string The state from the authorization request. Example: xyz123
     *
     * @response 302 Redirects to client's redirect_uri with authorization code
     */
    public function approveAuthorization(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'client_id' => ['required', 'string'],
            'redirect_uri' => ['required', 'string', 'url'],
            'scope' => ['sometimes', 'string'],
            'state' => ['sometimes', 'string'],
        ]);

        if ($validator->fails()) {
            return redirect($request->redirect_uri . '?' . http_build_query([
                'error' => 'invalid_request',
                'error_description' => 'The request is missing a required parameter',
            ]));
        }

        // Get client
        $client = OAuthClient::where('client_id', $request->client_id)
            ->where('redirect_uri', $request->redirect_uri)
            ->first();

        if (!$client) {
            return redirect($request->redirect_uri . '?' . http_build_query([
                'error' => 'invalid_client',
                'error_description' => 'Client not found or redirect URI mismatch',
            ]));
        }

        // Create authorization code
        $authCode = OAuthAuthCode::create([
            'id' => Str::random(40),
            'client_id' => $client->client_id,
            'user_id' => Auth::id(),
            'scopes' => $request->scope,
            'revoked' => false,
            'expires_at' => now()->addSeconds(self::AUTH_CODE_LIFETIME),
            'redirect_uri' => $request->redirect_uri,
        ]);

        return redirect($request->redirect_uri . '?' . http_build_query([
            'code' => $authCode->id,
            'state' => $request->state,
        ]));
    }

    private function handleAuthorizationCode(Request $request, OAuthClient $client): JsonResponse
    {
        // Validate additional parameters
        $validator = Validator::make($request->all(), [
            'code' => ['required', 'string'],
            'redirect_uri' => ['required', 'string', 'url'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'invalid_request',
                'error_description' => 'The request is missing a required parameter',
            ], 400);
        }

        // Get and validate authorization code
        $authCode = OAuthAuthCode::where('id', $request->code)
            ->where('client_id', $client->client_id)
            ->where('redirect_uri', $request->redirect_uri)
            ->where('revoked', false)
            ->where('expires_at', '>', now())
            ->first();

        if (!$authCode) {
            return response()->json([
                'error' => 'invalid_grant',
                'error_description' => 'The authorization code is invalid',
            ], 400);
        }

        // Create access token
        $accessToken = $this->createAccessToken($client, $authCode->user_id, $authCode->scopes);

        // Create refresh token
        $refreshToken = $this->createRefreshToken($accessToken);

        // Revoke used authorization code
        $authCode->update(['revoked' => true]);

        return response()->json([
            'token_type' => 'Bearer',
            'expires_in' => self::ACCESS_TOKEN_LIFETIME,
            'access_token' => $accessToken->access_token,
            'refresh_token' => $refreshToken->id,
            'scope' => $accessToken->scope,
        ]);
    }

    private function handleClientCredentials(Request $request, OAuthClient $client): JsonResponse
    {
        // Validate scope
        if ($request->has('scope')) {
            $requestedScopes = explode(' ', $request->scope);
            $allowedScopes = $client->scopes()
                ->where('grant_type', 'client_credentials')
                ->orWhereNull('grant_type')
                ->pluck('name');

            $invalidScopes = array_diff($requestedScopes, $allowedScopes->toArray());

            if (!empty($invalidScopes)) {
                return response()->json([
                    'error' => 'invalid_scope',
                    'error_description' => 'The requested scope is invalid',
                ], 400);
            }
        }

        // Create access token
        $accessToken = $this->createAccessToken($client, null, $request->scope);

        return response()->json([
            'token_type' => 'Bearer',
            'expires_in' => self::ACCESS_TOKEN_LIFETIME,
            'access_token' => $accessToken->access_token,
            'scope' => $accessToken->scope,
        ]);
    }

    private function handleRefreshToken(Request $request, OAuthClient $client): JsonResponse
    {
        // Validate additional parameters
        $validator = Validator::make($request->all(), [
            'refresh_token' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'invalid_request',
                'error_description' => 'The request is missing a required parameter',
            ], 400);
        }

        // Get and validate refresh token
        $refreshToken = OAuthRefreshToken::where('id', $request->refresh_token)
            ->where('revoked', false)
            ->where(function ($query) {
                $query->where('expires_at', '>', now())
                    ->orWhereNull('expires_at');
            })
            ->first();

        if (!$refreshToken) {
            return response()->json([
                'error' => 'invalid_grant',
                'error_description' => 'The refresh token is invalid',
            ], 400);
        }

        // Get old access token
        $oldAccessToken = OAuthAccessToken::where('access_token', $refreshToken->access_token_id)
            ->first();

        if (!$oldAccessToken) {
            return response()->json([
                'error' => 'invalid_grant',
                'error_description' => 'The refresh token is invalid',
            ], 400);
        }

        // Create new access token
        $accessToken = $this->createAccessToken(
            $client,
            $oldAccessToken->user_id,
            $request->scope ?? $oldAccessToken->scope
        );

        // Create new refresh token
        $newRefreshToken = $this->createRefreshToken($accessToken);

        // Revoke old tokens
        $refreshToken->update(['revoked' => true]);
        $oldAccessToken->update(['revoked' => true]);

        return response()->json([
            'token_type' => 'Bearer',
            'expires_in' => self::ACCESS_TOKEN_LIFETIME,
            'access_token' => $accessToken->access_token,
            'refresh_token' => $newRefreshToken->id,
            'scope' => $accessToken->scope,
        ]);
    }

    private function createAccessToken(OAuthClient $client, ?int $userId, ?string $scope): OAuthAccessToken
    {
        return OAuthAccessToken::create([
            'access_token' => Str::random(40),
            'client_id' => $client->client_id,
            'user_id' => $userId,
            'expires' => now()->addSeconds(self::ACCESS_TOKEN_LIFETIME),
            'scope' => $scope,
        ]);
    }

    private function createRefreshToken(OAuthAccessToken $accessToken): OAuthRefreshToken
    {
        return OAuthRefreshToken::create([
            'id' => Str::random(100),
            'access_token_id' => $accessToken->access_token,
            'revoked' => false,
            'expires_at' => now()->addSeconds(self::REFRESH_TOKEN_LIFETIME),
        ]);
    }

    /**
     * Revoke Token.
     *
     * Revoke an access token and its associated refresh token.
     *
     * @bodyParam token string required The access token to revoke. Example: eyJ0eXAiOiJKV1QiLCJhbG...
     * @bodyParam client_id string required The client ID. Example: test-client
     * @bodyParam client_secret string required The client secret (raw value, will be hashed internally). Example: client-secret
     *
     * @response 200 {
     *   "message": "Token revoked successfully"
     * }
     * @response 400 {
     *   "error": "invalid_request",
     *   "error_description": "The request is missing a required parameter"
     * }
     * @response 401 {
     *   "error": "invalid_client",
     *   "error_description": "Client authentication failed"
     * }
     * @response 404 {
     *   "error": "invalid_token",
     *   "error_description": "Token not found"
     * }
     */
    public function revokeToken(Request $request): JsonResponse
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'token' => ['required', 'string'],
            'client_id' => ['required', 'string'],
            'client_secret' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'invalid_request',
                'error_description' => 'The request is missing a required parameter',
            ], 400);
        }

        // Validate client credentials
        $client = OAuthClient::where('client_id', $request->client_id)
            ->where('client_secret', hash('sha256', $request->client_secret))
            ->first();

        if (!$client) {
            return response()->json([
                'error' => 'invalid_client',
                'error_description' => 'Client authentication failed',
            ], 401);
        }

        // Find access token
        $accessToken = OAuthAccessToken::where('access_token', $request->token)
            ->where('client_id', $client->client_id)
            ->first();

        if (!$accessToken) {
            return response()->json([
                'error' => 'invalid_token',
                'error_description' => 'Token not found',
            ], 404);
        }

        // Revoke access token
        $accessToken->update(['revoked' => true]);

        // Revoke associated refresh token if exists
        if ($refreshToken = $accessToken->refreshToken) {
            $refreshToken->update(['revoked' => true]);
        }

        return response()->json([
            'message' => 'Token revoked successfully',
        ]);
    }
}
