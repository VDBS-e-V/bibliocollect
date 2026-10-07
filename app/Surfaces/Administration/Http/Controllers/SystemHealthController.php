<?php

declare(strict_types=1);

namespace App\Surfaces\Administration\Http\Controllers;

use App\Foundation\Models\SystemErrorEvent;
use App\Foundation\Support\AlertService;
use App\Foundation\Support\SystemHealth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

/** Seite „Systemzustand“: Cron, Warteschlange, Sicherung und die letzten Fehler auf einen Blick. */
final class SystemHealthController
{
    public function index(SystemHealth $health): Response
    {
        $alert = config('hosting.alert_email');

        return response()
            ->view('pages.surfaces.administration.system.index', [
                'snapshot' => $health->snapshot(),
                'events' => SystemErrorEvent::query()->orderByDesc('last_seen_at')->limit(25)->get(),
                'alertAddress' => is_string($alert) && trim($alert) !== '' ? trim($alert) : null,
                'statusUrl' => config('hosting.cron_token') || config('hosting.status_token') ? url('/_status') : null,
            ])
            ->header('Cache-Control', 'private, no-store');
    }

    /** Schickt eine Testmeldung, damit man sieht, ob ALERT_EMAIL und der Mailversand funktionieren. */
    public function testAlert(AlertService $alerts): RedirectResponse
    {
        $sent = $alerts->notify('test-alert-'.now()->timestamp, 'Testmeldung', ['Das ist eine Testmeldung der Betriebsüberwachung.', 'Zeit: '.now()->toDateTimeString()]);

        return redirect()->route('administration.system.index')->with($sent ? 'system_success' : 'system_error', $sent
            ? 'Die Testmeldung wurde verschickt.'
            : 'Es konnte keine Testmeldung verschickt werden. Prüfe ALERT_EMAIL und den Mailversand (siehe Log).');
    }
}
