<?php

declare(strict_types=1);

namespace App\Foundation\Console;

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
        Artisan::call('schedule:run');
        $schedule = trim(Artisan::output());

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
