<?php

namespace Tests\Feature\OAuth;

use Tests\TestCase;
use App\Models\User;
use App\Models\Session;
use App\Models\OAuthClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Crypt;
use App\Http\Middleware\AuthenticateSession;

class OAuthFlowTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected OAuthClient $client;
    protected string $clientSecret;
    protected Session $session;
    private const TEST_PASSWORD = 'ShieldAuth@2024';

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'password_hash' => bcrypt(self::TEST_PASSWORD)
        ]);

        $sessionId = Str::random(40);
        $this->session = Session::create([
            'id' => $sessionId,
            'user_id' => $this->user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'payload' => json_encode([
                'created_at' => now(),
                'user_id' => $this->user->id,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'PHPUnit'
            ]),
            'last_activity' => time()
        ]);

        $this->clientSecret = Str::random(40);
        $this->client = OAuthClient::create([
            'name' => 'Test Client',
            'client_id' => 'test-client-' . Str::random(32),
            'client_secret' => hash('sha256', $this->clientSecret),
            'redirect_uri' => 'https://client.example.com/callback',
            'user_id' => $this->user->id,
            'scopes' => ['read', 'write']
        ]);

        $encryptedSessionId = Crypt::encryptString($sessionId);
        $this->withCookie('session_id', $encryptedSessionId);
    }

    public function test_authorization_code_flow()
    {
        $this->withoutMiddleware([AuthenticateSession::class]);
        $this->be($this->user);

        $response = $this->get('/api/oauth/authorize?' . http_build_query([
            'client_id' => $this->client->client_id,
            'redirect_uri' => $this->client->redirect_uri,
            'response_type' => 'code',
            'scope' => 'read write',
            'state' => 'xyz123'
        ]));

        $response->assertOk();

        $response = $this->post('/api/oauth/authorize', [
            'client_id' => $this->client->client_id,
            'redirect_uri' => $this->client->redirect_uri,
            'response_type' => 'code',
            'scope' => 'read write',
            'state' => 'xyz123',
            'approve' => true
        ]);

        $response->assertFound();
        $redirectUrl = $response->headers->get('Location');
        $this->assertStringContainsString('code=', $redirectUrl);
        $this->assertStringContainsString('state=xyz123', $redirectUrl);

        parse_str(parse_url($redirectUrl, PHP_URL_QUERY), $query);
        $code = $query['code'];

        $response = $this->post('/api/oauth/token', [
            'grant_type' => 'authorization_code',
            'client_id' => $this->client->client_id,
            'client_secret' => hash('sha256', $this->clientSecret),
            'redirect_uri' => $this->client->redirect_uri,
            'code' => $code
        ]);

        $response->assertOk();
        $responseData = $response->json();

        $this->assertArrayHasKey('access_token', $responseData);
        $this->assertArrayHasKey('refresh_token', $responseData);
        $this->assertArrayHasKey('expires_in', $responseData);
        $this->assertEquals('Bearer', $responseData['token_type']);

        return $responseData;
    }

    public function test_refresh_token_flow()
    {
        $this->withoutMiddleware([AuthenticateSession::class]);
        $this->be($this->user);

        $tokenResponse = $this->test_authorization_code_flow();
        $refreshToken = $tokenResponse['refresh_token'];

        $response = $this->post('/api/oauth/token', [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
            'client_id' => $this->client->client_id,
            'client_secret' => hash('sha256', $this->clientSecret),
            'scope' => 'read write'
        ]);

        $response->assertOk();
        $responseData = $response->json();

        $this->assertArrayHasKey('access_token', $responseData);
        $this->assertArrayHasKey('refresh_token', $responseData);
        $this->assertNotEquals($refreshToken, $responseData['refresh_token']);
    }
}
