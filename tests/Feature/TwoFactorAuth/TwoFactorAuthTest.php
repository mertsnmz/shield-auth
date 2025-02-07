<?php

namespace Tests\Feature\TwoFactorAuth;

use Tests\TestCase;
use App\Models\User;
use App\Models\Session;
use PragmaRX\Google2FA\Google2FA;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use App\Http\Middleware\AuthenticateSession;

class TwoFactorAuthTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Session $session;
    private const TEST_PASSWORD = 'ShieldAuth@2024';
    private Google2FA $google2fa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'password_hash' => Hash::make(self::TEST_PASSWORD)
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

        $encryptedSessionId = Crypt::encryptString($sessionId);
        $this->withCookie('session_id', $encryptedSessionId);

        $this->google2fa = new Google2FA();
    }

    public function test_enable_2fa(): array
    {
        $this->withoutMiddleware([AuthenticateSession::class]);
        $this->actingAs($this->user);

        $response = $this->postJson('/api/auth/2fa/enable');

        $response->assertOk();
        $setupData = $response->json('data');

        $this->assertArrayHasKey('secret', $setupData);
        $this->assertArrayHasKey('qr_code', $setupData);
        $this->assertArrayHasKey('recovery_codes', $setupData);

        $this->user->refresh();
        $this->assertTrue($this->user->two_factor_enabled);
        $this->assertNull($this->user->two_factor_confirmed_at);

        return $setupData;
    }

    /**
     * @depends test_enable_2fa
     */
    public function test_verify_2fa(array $setupData): array
    {
        $this->withoutMiddleware([AuthenticateSession::class]);
        $this->actingAs($this->user);

        $this->user->two_factor_secret = $setupData['secret'];
        $this->user->two_factor_recovery_codes = json_encode($setupData['recovery_codes']);
        $this->user->save();

        $code = str_pad($this->google2fa->getCurrentOtp($setupData['secret']), 6, '0', STR_PAD_LEFT);
        $response = $this->postJson('/api/auth/2fa/verify', [
            'code' => $code
        ]);

        $response->assertOk();

        $this->user->refresh();
        $this->assertNotNull($this->user->two_factor_confirmed_at);

        return $setupData;
    }

    /**
     * @depends test_verify_2fa
     */
    public function test_backup_codes(array $setupData)
    {
        $this->withoutMiddleware([AuthenticateSession::class]);
        $this->actingAs($this->user);

        $this->user->two_factor_enabled = true;
        $this->user->two_factor_confirmed_at = now();
        $this->user->two_factor_secret = $setupData['secret'];
        $this->user->two_factor_recovery_codes = json_encode($setupData['recovery_codes']);
        $this->user->save();

        $response = $this->getJson('/api/auth/2fa/backup-codes');

        $response->assertOk();
        $responseData = $response->json();

        $this->assertArrayHasKey('data', $responseData);
        $this->assertArrayHasKey('recovery_codes', $responseData['data']);
        $this->assertCount(8, $responseData['data']['recovery_codes']);

        $response = $this->postJson('/api/auth/2fa/regenerate-backup-codes');

        $response->assertOk();
        $newResponseData = $response->json();

        $this->assertNotEquals(
            $responseData['data']['recovery_codes'],
            $newResponseData['data']['recovery_codes']
        );

        return $setupData;
    }

    /**
     * @depends test_backup_codes
     */
    public function test_disable_2fa(array $setupData)
    {
        $this->withoutMiddleware([AuthenticateSession::class]);
        $this->actingAs($this->user);

        $this->user->two_factor_enabled = true;
        $this->user->two_factor_confirmed_at = now();
        $this->user->two_factor_secret = $setupData['secret'];
        $this->user->two_factor_recovery_codes = json_encode($setupData['recovery_codes']);
        $this->user->save();

        $code = str_pad($this->google2fa->getCurrentOtp($setupData['secret']), 6, '0', STR_PAD_LEFT);

        $response = $this->postJson('/api/auth/2fa/disable', [
            'current_password' => self::TEST_PASSWORD,
            'code' => $code
        ]);

        $response->assertOk();

        $this->user->refresh();
        $this->assertFalse($this->user->two_factor_enabled);
        $this->assertNull($this->user->two_factor_secret);
        $this->assertNull($this->user->two_factor_recovery_codes);
        $this->assertNull($this->user->two_factor_confirmed_at);
    }
} 