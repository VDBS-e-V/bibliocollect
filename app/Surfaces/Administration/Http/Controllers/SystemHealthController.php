<?php

declare(strict_types=1);

namespace App\Surfaces\Administration\Http\Controllers;

use App\Foundation\Models\SystemErrorEvent;
use App\Foundation\Support\AlertService;
use App\Foundation\Support\InstallationInfo;
use App\Foundation\Support\ScheduledJobs;
use App\Foundation\Support\SystemHealth;
use App\Modules\Audit\Services\AuditRecorder;
use App\Modules\Catalog\Models\Edition;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

/** Seite „Systemzustand“: Cron, Warteschlange, Sicherung und die letzten Fehler auf einen Blick. */
final class SystemHealthController
{
    public function index(SystemHealth $health, ScheduledJobs $jobs, InstallationInfo $installation): Response
    {
        $alert = config('hosting.alert_email');

        return response()
            ->view('pages.surfaces.administration.system.index', [
                'snapshot' => $health->snapshot(),
                'jobs' => $jobs->all(),
                'covers' => $this->coverStats(),
                'installation' => $installation->rows(),
                'backups' => $this->backups(),
                'events' => SystemErrorEvent::query()->orderByDesc('last_seen_at')->limit(25)->get(),
                'alertAddress' => is_string($alert) && trim($alert) !== '' ? trim($alert) : null,
                'statusUrl' => config('hosting.cron_token') || config('hosting.status_token') ? url('/_status') : null,
            ])
            ->header('Cache-Control', 'private, no-store');
    }

    /**
     * Zahlen zum Nachladen der Cover für die Seite „Systemzustand“.
     *
     * @return array<string, mixed>
     */
    private function coverStats(): array
    {
        $total = Edition::query()->count();
        $searchable = static fn () => Edition::query()->where(static function ($query): void {
            $query->whereNotNull('isbn')->orWhereNotNull('source_record_id');
        });
        $without = static fn ($query) => $query->whereNull('cover_path');

        $with = Edition::query()->whereNotNull('cover_path')->count();
        $open = $without($searchable())->where(static function ($query): void {
            $query->whereNull('cover_status')->orWhereNotIn('cover_status', ['missing', 'error']);
        })->count();
        $missing = $without($searchable())->where('cover_status', 'missing')->count();
        $failed = $without($searchable())->where('cover_status', 'error')->count();
        $noIdentifier = $total - $searchable()->count();
        $perNight = max(1, min(1000, (int) config('catalog.covers.daily_limit', 200)));

        $queued = (int) DB::table('jobs')->where('payload', 'like', '%RefreshEditionCoverJob%')->count();
        $failedJobs = (int) DB::table('failed_jobs')->where('payload', 'like', '%RefreshEditionCoverJob%')->count();

        return [
            'total' => $total,
            'with' => $with,
            'percent' => $total > 0 ? (int) floor($with / $total * 100) : 0,
            'open' => $open,
            'missing' => $missing,
            'failed' => $failed,
            'noIdentifier' => $noIdentifier,
            'queued' => $queued,
            'failedJobs' => $failedJobs,
            'perNight' => $perNight,
            'nights' => $open > 0 ? (int) ceil(max(0, $open - $queued) / $perNight) : 0,
            'openLibrary' => (bool) config('catalog.covers.open_library.enabled', true),
            'google' => is_string(config('catalog.covers.google_books.key')) && trim((string) config('catalog.covers.google_books.key')) !== '',
            'recent' => Edition::query()->with('title')->whereNotNull('cover_path')->orderByDesc('cover_fetched_at')->limit(8)->get(),
        ];
    }

    /** Reiht Bestandstitel zum Nachladen der Cover ein (auf Wunsch auch die, bei denen schon ergebnislos gesucht wurde). */
    public function queueCovers(Request $request, AuditRecorder $audit): RedirectResponse
    {
        $retry = $request->boolean('retry_missing');

        try {
            $code = Artisan::call('catalog:covers:queue', ['--limit' => 200] + ($retry ? ['--retry-missing' => true] : []));
            $output = trim(Artisan::output());
        } catch (Throwable $exception) {
            report($exception);

            return redirect()->route('administration.system.index')->with('system_error', 'Die Cover konnten nicht eingereiht werden: '.$exception->getMessage());
        }

        $audit->record('system.covers.queued', 'Cover-Nachladen von Hand angestoßen'.($retry ? ' (auch erfolglos gesuchte Titel).' : '.'));

        return redirect()->route('administration.system.index')->with($code === 0 ? 'system_success' : 'system_error', $code === 0
            ? 'Die Cover-Suche ist eingereiht. Sie läuft über den Cron im Hintergrund; „Cron-Lauf jetzt auslösen“ arbeitet gleich ein Stück davon ab. '.$output
            : ($output !== '' ? $output : 'Es ist kein Cover-Anbieter eingerichtet.'));
    }

    /** Erstellt jetzt eine Sicherung der Datenbank. */
    public function createBackup(AuditRecorder $audit): RedirectResponse
    {
        try {
            Artisan::call('backup:database');
        } catch (Throwable $exception) {
            report($exception);

            return redirect()->route('administration.system.index')->with('system_error', 'Die Sicherung ist fehlgeschlagen: '.$exception->getMessage());
        }

        $audit->record('system.backup.created', 'Datenbanksicherung von Hand erstellt.');

        return redirect()->route('administration.system.index')->with('system_success', 'Die Sicherung wurde erstellt. Du kannst sie jetzt herunterladen.');
    }

    /** Lädt eine Sicherung herunter (enthält alle personenbezogenen Daten, deshalb protokolliert). */
    public function downloadBackup(string $file, AuditRecorder $audit): BinaryFileResponse
    {
        abort_unless(preg_match('/^datenbank-[0-9_\-]+\.(sql\.gz|sqlite)$/', $file) === 1, 404);

        $path = storage_path('app/backups/'.$file);
        abort_unless(is_file($path), 404);

        $audit->record('system.backup.downloaded', 'Datenbanksicherung heruntergeladen.', null, ['file' => $file]);

        return response()->download($path)->deleteFileAfterSend(false);
    }

    /** @return list<array{name: string, size_kb: int, created: string}> */
    private function backups(): array
    {
        $directory = storage_path('app/backups');

        if (! is_dir($directory)) {
            return [];
        }

        return collect(File::files($directory))
            ->filter(static fn ($file): bool => preg_match('/^datenbank-[0-9_\-]+\.(sql\.gz|sqlite)$/', $file->getFilename()) === 1)
            ->sortByDesc(static fn ($file): string => $file->getFilename())
            ->take(15)
            ->map(static fn ($file): array => [
                'name' => $file->getFilename(),
                'size_kb' => (int) ceil($file->getSize() / 1024),
                'created' => date('d.m.Y H:i', $file->getMTime()),
            ])
            ->values()
            ->all();
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
