<?php

namespace App\Providers;

use App\Services\JWT\JWTService;
use Illuminate\Support\ServiceProvider;

class JWTServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(JWTService::class, function ($app) {
            return new JWTService();
        });
    }
} 