<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);

        // Global middleware for production security
        $middleware->append([
            \App\Http\Middleware\SecurityHeadersMiddleware::class,
        ]);

        // API rate limiting and monitoring
        $middleware->group('api', [
            \App\Http\Middleware\ApiRateLimitMiddleware::class . ':api',
            \App\Http\Middleware\PerformanceMonitoringMiddleware::class,
        ]);

        // Register custom middleware aliases
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'permission' => \App\Http\Middleware\PermissionMiddleware::class,
            'teacher.language.access' => \App\Http\Middleware\ValidateTeacherLanguageAccess::class,
            'guest.access' => \App\Http\Middleware\GuestAccessMiddleware::class,
            'locale' => \App\Http\Middleware\LocaleMiddleware::class,
            'rate.limit' => \App\Http\Middleware\ApiRateLimitMiddleware::class,
            'security.headers' => \App\Http\Middleware\SecurityHeadersMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
