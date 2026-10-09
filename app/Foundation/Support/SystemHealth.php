<?php

declare(strict_types=1);

namespace App\Foundation\Support;

use App\Foundation\Console\CronCommand;
use App\Foundation\Models\SystemErrorEvent;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Zustand des laufenden Systems in einem Blick: Cron, Warteschlange, Sicherung, Datenbank, Mail, Fehler, Speicherplatz.
 * Die Prüfungen sind für Menschen (Seite „Systemzustand“) und Maschinen (JSON unter /_status) dieselben.
 */
final class SystemHealth
{
    public const OK = 'ok';

    public const WARN = 'warn';

    public const FAIL = 'fail';

    /** @return array{status: string, checked_at: string, checks: list<array{key: string, label: string, state: string, detail: string}>} */
    public function snapshot(): array
    {
        $checks = [
            $this->database(),
            $this->cron(),
            $this->queue(),
            $this->backup(),
            $this->offsiteCopy(),
            $this->errors(),
            $this->mail(),
            $this->disk(),
        ];

        $states = array_column($checks, 'state');
        $status = in_array(self::FAIL, $states, true) ? self::FAIL : (in_array(self::WARN, $states, true) ? self::WARN : self::OK);

        return ['status' => $status, 'checked_at' => now()->toIso8601String(), 'checks' => $checks];
    }

    /** @return array{key: string, label: string, state: string, detail: string} */
    private function database(): array
    {
        try {
            DB::select('select 1');

            return $this->row('database', 'Datenbank', self::OK, 'erreichbar');
        } catch (Throwable) {
            return $this->row('database', 'Datenbank', self::FAIL, 'nicht erreichbar');
        }
    }

    /** @return array{key: string, label: string, state: string, detail: string} */
    private function cron(): array
    {
        $heartbeat = Cache::get(CronCommand::HEARTBEAT_KEY);
        $lastRun = is_string($heartbeat) ? CarbonImmutable::parse($heartbeat) : null;
        $production = app()->isProduction();

        if ($lastRun === null) {
            return $this->row('cron', 'Cron (Zeitplan und Warteschlange)', $production ? self::FAIL : self::WARN, 'noch nie gelaufen. /_cron oder app:cron muss regelmäßig aufgerufen werden');
        }

        $minutes = (int) $lastRun->diffInMinutes(now());
        $gap = max(1, (int) config('hosting.cron_gap_minutes', 15));
        $state = $minutes > 120 ? self::FAIL : ($minutes > $gap ? self::WARN : self::OK);

        return $this->row('cron', 'Cron (Zeitplan und Warteschlange)', $state, 'zuletzt vor '.$minutes.' Minute(n) ('.$lastRun->timezone(config('app.timezone'))->format('d.m.Y H:i').')');
    }

    /** @return array{key: string, label: string, state: string, detail: string} */
    private function queue(): array
    {
        if (! Schema::hasTable('jobs')) {
            return $this->row('queue', 'Warteschlange', self::WARN, 'Tabelle fehlt (Migrationen ausführen)');
        }

        $waiting = DB::table('jobs')->count();
        $failed = Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : 0;
        $oldest = DB::table('jobs')->min('created_at');
        $oldestMinutes = is_numeric($oldest) ? (int) floor((time() - (int) $oldest) / 60) : 0;

        $state = $failed > 0 || $oldestMinutes > 30 ? self::WARN : self::OK;
        $detail = $waiting.' wartend, '.$failed.' fehlgeschlagen'.($waiting > 0 ? ', ältester vor '.$oldestMinutes.' Minute(n)' : '');

        return $this->row('queue', 'Warteschlange', $state, $detail);
    }

    /** @return array{key: string, label: string, state: string, detail: string} */
    private function backup(): array
    {
        $files = glob(storage_path('app/backups/*')) ?: [];
        $newest = $files === [] ? null : max(array_map(static fn (string $file): int => (int) filemtime($file), $files));

        if ($newest === null) {
            return $this->row('backup', 'Datensicherung', app()->isProduction() ? self::FAIL : self::WARN, 'noch keine Sicherung vorhanden');
        }

        $hours = (int) floor((time() - $newest) / 3600);
        $state = $hours > 72 ? self::FAIL : ($hours > 36 ? self::WARN : self::OK);

        return $this->row('backup', 'Datensicherung', $state, 'zuletzt vor '.$hours.' Stunde(n), '.count($files).' Sicherung(en) gespeichert');
    }

    /**
     * Eine Sicherung nur auf dem Server hilft nicht, wenn der Server ausfällt: Der Download ist die Kopie außerhalb.
     *
     * @return array{key: string, label: string, state: string, detail: string}
     */
    private function offsiteCopy(): array
    {
        try {
            $last = DB::table('audit_events')->where('action', 'system.backup.downloaded')->max('occurred_at');
        } catch (Throwable) {
            return $this->row('offsite', 'Sicherung außerhalb des Servers', self::OK, 'nicht prüfbar');
        }

        if ($last === null) {
            return $this->row('offsite', 'Sicherung außerhalb des Servers', self::WARN, 'Noch nie heruntergeladen. Bitte unter „Systemzustand“ eine Sicherung herunterladen und sicher ablegen.');
        }

        $days = max(0, (int) floor(CarbonImmutable::parse((string) $last, config('app.timezone'))->diffInDays(now(), true)));

        return $days > 14
            ? $this->row('offsite', 'Sicherung außerhalb des Servers', self::WARN, 'Zuletzt vor '.$days.' Tagen heruntergeladen. Bitte eine aktuelle Sicherung herunterladen.')
            : $this->row('offsite', 'Sicherung außerhalb des Servers', self::OK, 'zuletzt vor '.$days.' Tag(en) heruntergeladen');
    }

    /** @return array{key: string, label: string, state: string, detail: string} */
    private function errors(): array
    {
        if (! Schema::hasTable('system_error_events')) {
            return $this->row('errors', 'Fehler der letzten 24 Stunden', self::WARN, 'Tabelle fehlt (Migrationen ausführen)');
        }

        $count = (int) SystemErrorEvent::query()->where('last_seen_at', '>=', now()->subDay())->sum('occurrences');
        $kinds = SystemErrorEvent::query()->where('last_seen_at', '>=', now()->subDay())->count();

        $state = $count === 0 ? self::OK : ($count >= 20 ? self::FAIL : self::WARN);

        return $this->row('errors', 'Fehler der letzten 24 Stunden', $state, $count === 0 ? 'keine' : $count.' Fehler in '.$kinds.' Art(en)');
    }

    /** @return array{key: string, label: string, state: string, detail: string} */
    private function mail(): array
    {
        $mailer = (string) config('mail.default');
        $alert = config('hosting.alert_email');
        $hasAlert = is_string($alert) && trim($alert) !== '';

        if (in_array($mailer, ['log', 'array'], true)) {
            return $this->row('mail', 'Mail und Betriebsmeldungen', app()->isProduction() ? self::WARN : self::OK, 'Versand über „'.$mailer.'“: Mails werden nur ins Log geschrieben');
        }

        return $this->row('mail', 'Mail und Betriebsmeldungen', $hasAlert ? self::OK : self::WARN, $mailer.($hasAlert ? ', Meldungen an die Administration aktiv' : ', aber keine ALERT_EMAIL gesetzt: Fehler werden nicht gemeldet'));
    }

    /** @return array{key: string, label: string, state: string, detail: string} */
    private function disk(): array
    {
        $free = @disk_free_space(storage_path());

        if ($free === false) {
            return $this->row('disk', 'Speicherplatz', self::OK, 'nicht ermittelbar');
        }

        $megabytes = (int) floor($free / 1048576);

        return $this->row('disk', 'Speicherplatz', $megabytes < 100 ? self::FAIL : ($megabytes < 500 ? self::WARN : self::OK), $megabytes.' MB frei');
    }

    /** @return array{key: string, label: string, state: string, detail: string} */
    private function row(string $key, string $label, string $state, string $detail): array
    {
        return ['key' => $key, 'label' => $label, 'state' => $state, 'detail' => $detail];
    }
}
