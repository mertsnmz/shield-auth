<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('oauth_scopes', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();
            $table->string('description', 255);
            $table->string('grant_type')->nullable(); // Which grant type can use this scope
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        // Define scopes for each grant type
        $scopes = [
            // Authorization Code Grant Scopes (requires user interaction)
            ['profile', 'Access user profile information', 'authorization_code'],
            ['email', 'Access user email', 'authorization_code'],
            ['manage_account', 'Manage user account settings', 'authorization_code'],

            // Client Credentials Grant Scopes (service-to-service)
            ['api.read', 'Read API resources', 'client_credentials'],
            ['api.write', 'Write API resources', 'client_credentials'],
            ['service.integration', 'Service integration access', 'client_credentials'],

            // General Scopes (can be used with any grant type)
            ['offline_access', 'Get refresh token', null],
        ];

        foreach ($scopes as $scope) {
            DB::table('oauth_scopes')->insert([
                'name' => $scope[0],
                'description' => $scope[1],
                'grant_type' => $scope[2],
                'is_default' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('oauth_scopes');
    }
};
