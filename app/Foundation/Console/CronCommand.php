<?php

declare(strict_types=1);

namespace App\Foundation\Console;

use App\Foundation\Support\AlertService;
use App\Foundation\Update\UpdateException;
use App\Foundation\Update\UpdateManager;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Throwable;

/**
 * Ein Einstiegspunkt für Hosting ohne dauerhaft laufende Prozesse: Ein Cronjob ruft `php artisan app:cron` auf
 * (am besten jede Minute, mindestens alle 5 Minuten). Der Befehl führt fällige Zeitplan-Aufgaben aus und arbeitet
 * danach die Warteschlange ab (Cover-Downloads), ohne dauerhaft zu laufen.
 */
final class CronCommand extends Command
{
    public const HEARTBEAT_KEY = 'app.cron.last_run';

    protected $signature = 'app:cron {--queue-seconds=40 : Höchste Laufzeit für die Warteschlange in Sekunden}';

    protected $description = 'Führt fällige Zeitplan-Aufgaben aus und arbeitet die Warteschlange ab (für Cronjobs ohne Queue Worker).';

    public function handle(): int
    {
        $this->reportGap();

        // Ein eingespieltes Update wartet auf seinen Abschluss (neuer Code, neue Anfrage): zuerst das.
        $updates = app(UpdateManager::class);

        if ($updates->pending() !== null) {
            try {
                $this->info($updates->finish(null)['message']);
            } catch (UpdateException $exception) {
                $this->error($exception->getMessage());
            }

            return self::SUCCESS;
        }

        Artisan::call('schedule:run');
        $schedule = trim(Artisan::output());

        // Das nächtliche Update hat Dateien ausgetauscht: Dieser Prozess lädt keinen weiteren Code mehr, der Abschluss folgt beim nächsten Aufruf.
        if (UpdateManager::$applied) {
            $this->line($schedule);
            $this->info('Update eingespielt; der Abschluss folgt beim nächsten Cron-Aufruf.');

            return self::SUCCESS;
        }

        $before = $this->waiting();

        Artisan::call('queue:work', [
            '--stop-when-empty' => true,
            '--max-time' => max(5, (int) $this->option('queue-seconds')),
            '--tries' => 3,
            '--quiet' => true,
        ]);

        Cache::forever(self::HEARTBEAT_KEY, now()->toIso8601String());

        $this->line($schedule !== '' ? $schedule : 'Keine Zeitplan-Aufgabe war fällig.');
        $this->info('Warteschlange abgearbeitet.');

        $after = $this->waiting();

        if ($before !== null && $after !== null) {
            $this->line('Jobs: '.max(0, $before - $after).' abgearbeitet, '.$after.' wartend, '.$this->failed().' fehlgeschlagen (insgesamt).');
        }

        return self::SUCCESS;
    }

    /** Lief der Cron lange nicht, geht eine Meldung an die Administration, sobald er wieder aufgerufen wird. */
    private function reportGap(): void
    {
        $previous = Cache::get(self::HEARTBEAT_KEY);

        if (! is_string($previous)) {
            return;
        }

        $minutes = (int) CarbonImmutable::parse($previous)->diffInMinutes(now());
        $limit = max(1, (int) config('hosting.cron_gap_minutes', 15));

        if ($minutes > $limit) {
            app(AlertService::class)->notify('cron-gap', 'Der Cron ist ausgefallen gewesen', [
                'Der Cron lief '.$minutes.' Minuten nicht (erlaubt: '.$limit.').',
                'Zeitplan, Erinnerungen und Warteschlange standen in dieser Zeit still. Jetzt läuft er wieder.',
                'Zeit: '.now()->toDateTimeString(),
            ]);
        }
    }

    /** Anzahl der Jobs in der Warteschlange; null, wenn sie sich nicht zählen lassen (zum Beispiel bei „sync“). */
    private function waiting(): ?int
    {
        try {
            return Queue::size();
        } catch (Throwable) {
            return null;
        }
    }

    private function failed(): int
    {
        try {
            return DB::table('failed_jobs')->count();
        } catch (Throwable) {
            return 0;
        }
    }
}
