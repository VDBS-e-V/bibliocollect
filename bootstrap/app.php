<?php

use App\Foundation\Http\Middleware\RequirePermission;
use App\Foundation\Http\Middleware\SecurityHeaders;
use App\Foundation\Support\SystemErrorLog;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(SecurityHeaders::class);

        $middleware->alias([
            'permission' => RequirePermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Unerwartete Fehler festhalten und der Administration melden (siehe SystemErrorLog).
        $exceptions->report(function (Throwable $exception): void {
            app(SystemErrorLog::class)->record($exception, app()->runningInConsole() && ! app()->runningUnitTests() ? null : request());
        });

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
