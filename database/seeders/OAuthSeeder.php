<?php

namespace Database\Seeders;

use App\Models\OAuthClient;
use App\Models\OAuthScope;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OAuthSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear tables first
        DB::table('oauth_client_scopes')->delete();
        DB::table('oauth_scopes')->delete();
        DB::table('oauth_clients')->delete();

        // Create test client
        $client = OAuthClient::create([
            'client_id' => 'test-client',
            'client_secret' => 'test-secret',
            'name' => 'Test Client',
            'redirect_uri' => 'http://localhost:3000/callback',
            'grant_types' => 'authorization_code client_credentials refresh_token',
        ]);

        // Create scopes
        $scopes = [
            // Authorization Code Grant Scopes
            [
                'name' => 'profile',
                'description' => 'Access user profile information',
                'grant_type' => 'authorization_code',
                'is_default' => true,
            ],
            [
                'name' => 'email',
                'description' => 'Access user email',
                'grant_type' => 'authorization_code',
                'is_default' => true,
            ],
            [
                'name' => 'manage_account',
                'description' => 'Manage user account settings',
                'grant_type' => 'authorization_code',
                'is_default' => false,
            ],

            // Client Credentials Grant Scopes
            [
                'name' => 'api.read',
                'description' => 'Read API resources',
                'grant_type' => 'client_credentials',
                'is_default' => true,
            ],
            [
                'name' => 'api.write',
                'description' => 'Write API resources',
                'grant_type' => 'client_credentials',
                'is_default' => false,
            ],
            [
                'name' => 'service.integration',
                'description' => 'Service integration access',
                'grant_type' => 'client_credentials',
                'is_default' => false,
            ],

            // General Scopes
            [
                'name' => 'offline_access',
                'description' => 'Get refresh token',
                'grant_type' => null,
                'is_default' => false,
            ],
        ];

        foreach ($scopes as $scope) {
            OAuthScope::create($scope);
        }

        // Assign all scopes to test client
        $client->scopes()->attach(OAuthScope::all()->pluck('name'));
    }
}
