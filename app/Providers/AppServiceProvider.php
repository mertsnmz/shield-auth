<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Interfaces\Auth\IAuthService;
use App\Services\Auth\AuthService;
use App\Interfaces\Auth\IAuthRepository;
use App\Repositories\Auth\AuthRepository;
use App\Interfaces\TwoFactorAuth\ITwoFactorAuthService;
use App\Services\TwoFactorAuth\TwoFactorAuthService;
use App\Interfaces\TwoFactorAuth\ITwoFactorAuthRepository;
use App\Repositories\TwoFactorAuth\TwoFactorAuthRepository;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(IAuthService::class, AuthService::class);
        $this->app->bind(IAuthRepository::class, AuthRepository::class);
        $this->app->bind(ITwoFactorAuthService::class, TwoFactorAuthService::class);
        $this->app->bind(ITwoFactorAuthRepository::class, TwoFactorAuthRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
