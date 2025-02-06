<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanOAuthTokens extends Command
{
    protected $signature = 'oauth:clean-tokens';
    protected $description = 'Clean expired OAuth tokens';

    public function handle()
    {
        $this->info('Cleaning expired OAuth tokens...');

        // Süresi dolmuş access token'ları temizle
        $expiredTokens = DB::table('oauth_access_tokens')
            ->where('expires', '<', now())
            ->update(['revoked' => true]);

        // Süresi dolmuş refresh token'ları temizle
        $expiredRefreshTokens = DB::table('oauth_refresh_tokens')
            ->where('expires_at', '<', now())
            ->update(['revoked' => true]);

        $this->info("Cleaned {$expiredTokens} expired access tokens");
        $this->info("Cleaned {$expiredRefreshTokens} expired refresh tokens");
    }
} 