<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create test user
        User::create([
            'email' => 'test@example.com',
            'password_hash' => Hash::make('Test123!@#$%^&*'),
            'password_changed_at' => now()
        ]);

        $this->call([
            OAuthSeeder::class
        ]);
    }
}
