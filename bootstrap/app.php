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
        // Hinter dem Proxy des Hosters kommen Adresse und https aus den X-Forwarded-Headern. Mit TRUSTED_PROXIES lässt sich das einschränken.
        $middleware->trustProxies(at: env('TRUSTED_PROXIES', '*') === '*' ? '*' : array_map('trim', explode(',', (string) env('TRUSTED_PROXIES'))));

        $middleware->append(SecurityHeaders::class);

        // Im Wartungsmodus bleiben der Abschluss eines Updates und der Cron erreichbar (der Cron schließt wartende Updates ab).
        $middleware->preventRequestsDuringMaintenance(except: ['_update/*', '_cron', '_cron/*', '_status', '_status/*']);

        // Die Einrichtungsseite braucht keine Sitzung (sie ist durch das Token geschützt); ein abgelaufenes Formular soll nicht stören.
        $middleware->validateCsrfTokens(except: ['_setup/*']);

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
