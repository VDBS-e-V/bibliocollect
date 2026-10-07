<?php

declare(strict_types=1);

namespace App\Surfaces\Administration\Http\Controllers;

use App\Foundation\Models\SystemErrorEvent;
use App\Foundation\Support\AlertService;
use App\Foundation\Support\ScheduledJobs;
use App\Foundation\Support\SystemHealth;
use App\Modules\Audit\Services\AuditRecorder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Artisan;
use Throwable;

/** Seite „Systemzustand“: Cron, Warteschlange, Sicherung und die letzten Fehler auf einen Blick. */
final class SystemHealthController
{
    public function index(SystemHealth $health, ScheduledJobs $jobs): Response
    {
        $alert = config('hosting.alert_email');

        return response()
            ->view('pages.surfaces.administration.system.index', [
                'snapshot' => $health->snapshot(),
                'jobs' => $jobs->all(),
                'events' => SystemErrorEvent::query()->orderByDesc('last_seen_at')->limit(25)->get(),
                'alertAddress' => is_string($alert) && trim($alert) !== '' ? trim($alert) : null,
                'statusUrl' => config('hosting.cron_token') || config('hosting.status_token') ? url('/_status') : null,
            ])
            ->header('Cache-Control', 'private, no-store');
    }

    /** Führt eine einzelne Zeitplan-Aufgabe jetzt einmal aus. */
    public function runJob(string $job, ScheduledJobs $jobs, AuditRecorder $audit): RedirectResponse
    {
        abort_unless($jobs->has($job), 404);

        $result = $jobs->run($job);

        $audit->record('system.job.run', 'Zeitplan-Aufgabe von Hand ausgeführt: '.$job.($result['ok'] ? '.' : ' (mit Fehler).'), null, ['job' => $job, 'ok' => $result['ok'], 'seconds' => $result['seconds']]);

        return redirect()->route('administration.system.index')->with($result['ok'] ? 'system_success' : 'system_error', '„'.$job.'“: '.$result['message'].' ('.$result['seconds'].' s)');
    }

    /** Ein Cron-Lauf wie vom Cronjob: fällige Aufgaben und danach ein Stück der Warteschlange (kurz, damit die Seite nicht hängt). */
    public function runCron(AuditRecorder $audit): RedirectResponse
    {
        try {
            Artisan::call('app:cron', ['--queue-seconds' => 10]);
            $output = trim(Artisan::output());
        } catch (Throwable $exception) {
            report($exception);

            return redirect()->route('administration.system.index')->with('system_error', 'Der Cron-Lauf ist mit einem Fehler beendet worden: '.$exception->getMessage());
        }

        $audit->record('system.cron.run', 'Cron-Lauf von Hand ausgelöst.');

        return redirect()->route('administration.system.index')->with('system_success', 'Cron-Lauf ausgeführt. '.$output);
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
