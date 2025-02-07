<?php

namespace Database\Factories;

use App\Models\OAuthClient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class OAuthClientFactory extends Factory
{
    protected $model = OAuthClient::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'client_id' => 'client-' . Str::random(32),
            'client_secret' => hash('sha256', Str::random(40)),
            'redirect_uri' => 'https://example.com/callback',
            'user_id' => User::factory(),
            'scopes' => ['read', 'write'],
            'revoked' => false,
        ];
    }

    public function revoked(): static
    {
        return $this->state(fn (array $attributes) => [
            'revoked' => true,
        ]);
    }
}
