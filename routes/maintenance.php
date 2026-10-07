<?php

declare(strict_types=1);

use App\Foundation\Http\Controllers\SystemStatusController;
use App\Foundation\Http\Controllers\WebCronController;
use App\Modules\Identity\Http\Controllers\SetupController;
use Illuminate\Support\Facades\Route;

// Hilfen für Hosting ohne SSH. Beide Routen gibt es nur, wenn ein ausreichend langes Token konfiguriert ist.
$usable = static fn (mixed $token): bool => is_string($token) && strlen($token) >= 24;

if ($usable(config('hosting.setup_token'))) {
    Route::middleware('throttle:10,1')->group(function (): void {
        Route::get('/_setup', [SetupController::class, 'show']);
        Route::post('/_setup/{action}', [SetupController::class, 'run'])->where('action', 'migrate|admin|doctor');
    });
}

if ($usable(config('hosting.cron_token'))) {
    // Der Key kommt im Header (X-Api-Key oder Authorization: Bearer) oder, wenn der Dienst keine Header kann, im Pfad.
    Route::match(['GET', 'POST'], '/_cron/{token?}', WebCronController::class)->middleware('throttle:30,1');
}

// Statusadresse für ein Monitoring (zum Beispiel UptimeRobot): JSON, bei Fehlern HTTP 503. Eigener STATUS_TOKEN oder der CRON_TOKEN.
$statusToken = $usable(config('hosting.status_token')) ? config('hosting.status_token') : config('hosting.cron_token');

if ($usable($statusToken)) {
    Route::get('/_status/{token?}', SystemStatusController::class)->middleware('throttle:60,1');
}
