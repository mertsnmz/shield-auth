<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class SecurityAudit extends Command
{
    protected $signature = 'security:audit';
    protected $description = 'Run security audit checks';

    public function handle()
    {
        $this->info('Starting Security Audit...');

        // 1. Environment Check
        $this->checkEnvironment();

        // 2. Database Security Check
        $this->checkDatabase();

        // 3. File Permissions Check
        $this->checkFilePermissions();

        // 4. Security Headers Check
        $this->checkSecurityHeaders();

        // 5. OAuth Configuration Check
        $this->checkOAuthConfig();

        $this->info('Security Audit Completed!');
    }

    private function checkEnvironment()
    {
        $this->info('Checking Environment Configuration...');

        $checks = [
            'APP_ENV' => env('APP_ENV') === 'production',
            'APP_DEBUG' => env('APP_DEBUG') === false,
            'SESSION_SECURE_COOKIE' => env('SESSION_SECURE_COOKIE', true),
            'SESSION_ENCRYPT' => env('SESSION_ENCRYPT', true),
        ];

        foreach ($checks as $key => $check) {
            if (!$check) {
                $this->error("❌ {$key} is not properly configured for production!");
            } else {
                $this->line("✅ {$key} is properly configured");
            }
        }
    }

    private function checkDatabase()
    {
        $this->info('Checking Database Security...');

        // Check for expired tokens
        $expiredTokens = DB::table('oauth_access_tokens')
            ->where('revoked', false)
            ->where('expires', '<', now())
            ->count();

        if ($expiredTokens > 0) {
            $this->warn("⚠️ Found {$expiredTokens} expired but not revoked tokens");
        }

        // Check for old sessions
        $oldSessions = DB::table('sessions')
            ->where('last_activity', '<', now()->subDays(30)->timestamp)
            ->count();

        if ($oldSessions > 0) {
            $this->warn("⚠️ Found {$oldSessions} sessions older than 30 days");
        }
    }

    private function checkFilePermissions()
    {
        $this->info('Checking File Permissions...');

        $paths = [
            storage_path(),
            base_path('.env'),
            base_path('composer.json'),
        ];

        foreach ($paths as $path) {
            if (File::exists($path)) {
                $perms = substr(sprintf('%o', fileperms($path)), -4);
                if ($perms > '0755') {
                    $this->error("❌ {$path} has unsafe permissions: {$perms}");
                } else {
                    $this->line("✅ {$path} has safe permissions");
                }
            }
        }
    }

    private function checkSecurityHeaders()
    {
        $this->info('Checking Security Headers Configuration...');

        $requiredHeaders = [
            'X-Content-Type-Options',
            'X-Frame-Options',
            'X-XSS-Protection',
            'Strict-Transport-Security',
            'Content-Security-Policy',
            'Referrer-Policy'
        ];

        $middleware = app(\App\Http\Middleware\SecurityHeaders::class);
        $headers = $middleware->headers ?? [];

        foreach ($requiredHeaders as $header) {
            if (!isset($headers[$header])) {
                $this->error("❌ Missing security header: {$header}");
            } else {
                $this->line("✅ {$header} is configured");
            }
        }
    }

    private function checkOAuthConfig()
    {
        $this->info('Checking OAuth Configuration...');

        // Token expiration checks
        $accessTokenLifetime = config('oauth.access_token_lifetime', 3600);
        if ($accessTokenLifetime > 3600) {
            $this->warn("⚠️ Access token lifetime ({$accessTokenLifetime}s) is longer than recommended");
        }

        // Client checks
        $clients = DB::table('oauth_clients')->get();
        foreach ($clients as $client) {
            if (empty($client->redirect_uri)) {
                $this->error("❌ Client {$client->name} has no redirect URI configured");
            }
            if (strlen($client->client_secret) < 32) {
                $this->warn("⚠️ Client {$client->name} has a weak client secret");
            }
        }
    }
} 