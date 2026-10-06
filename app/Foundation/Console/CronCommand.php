<?php

declare(strict_types=1);

namespace App\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

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

        Artisan::call('queue:work', [
            '--stop-when-empty' => true,
            '--max-time' => max(5, (int) $this->option('queue-seconds')),
            '--tries' => 3,
            '--quiet' => true,
        ]);

        Cache::forever(self::HEARTBEAT_KEY, now()->toIso8601String());

        $this->line($schedule !== '' ? $schedule : 'Keine Zeitplan-Aufgabe war fällig.');
        $this->info('Warteschlange abgearbeitet.');

        return self::SUCCESS;
    }
}
