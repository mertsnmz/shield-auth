<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'auth.session' => App\Http\Middleware\AuthenticateSession::class,
            'throttle' => App\Http\Middleware\RateLimiter::class,
            'oauth.jwt' => App\Http\Middleware\ValidateJWTToken::class,
        ]);

        $middleware->use([
            App\Http\Middleware\ForceHttps::class,
            App\Http\Middleware\SecurityHeaders::class,
            App\Http\Middleware\SanitizeLogData::class,
        ]);

        $middleware->group('api', [
            Illuminate\Http\Middleware\HandleCors::class,
            Illuminate\Routing\Middleware\SubstituteBindings::class,
            Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
            App\Http\Middleware\ForceJsonResponse::class,
            App\Http\Middleware\RateLimiter::class,
        ]);
    })
    ->withSchedule(function (Schedule $schedule) {
        $schedule->command('security:audit')
            ->dailyAt('00:00')
            ->emailOutputTo('security@example.com');

        $schedule->command('oauth:clean-tokens')
            ->daily();

        $schedule->command('session:clean')
            ->weekly();
    })
    ->withExceptions(function (Exceptions $exceptions) {

    })->create();
