<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'email' => 'admin@example.com',
            'password_hash' => Hash::make('Admin123!@#$%^&*'),
            'is_admin' => true,
            'password_changed_at' => now(),
        ]);

        User::create([
            'email' => 'test@example.com',
            'password_hash' => Hash::make('Test123!@#$%^&*'),
            'is_admin' => false,
            'password_changed_at' => now(),
        ]);
    }
} 