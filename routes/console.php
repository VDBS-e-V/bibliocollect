<?php

use App\Foundation\Support\AlertService;
use App\Foundation\Support\SystemErrorLog;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * Zeitplan-Aufgaben laufen im selben Prozess (Schedule::call) und nicht als eigener `php artisan`-Prozess. Nur so
 * funktionieren sie auch, wenn der Zeitplan über die Web-Cron-Adresse (/_cron) ausgelöst wird, wo es keinen PHP-Befehl
 * zum Starten eines zweiten Prozesses gibt.
 */
$command = static fn (string $name, array $parameters = []): Closure => static function () use ($name, $parameters): void {
    Artisan::call($name, $parameters);
};

// Holt Cover für Bestandstitel nach (benötigt einen laufenden Queue Worker). Bereits ergebnislos geprüfte Ausgaben werden übersprungen.
Schedule::call($command('catalog:covers:queue', ['--limit' => 50]))
    ->name('catalog:covers:queue')
    ->dailyAt('03:30')
    ->withoutOverlapping()
    ->onOneServer();

// Erinnerungen an bald fällige und überfällige Ausleihen sowie abholbereite Vormerkungen.
Schedule::call($command('reminders:send'))
    ->name('reminders:send')
    ->dailyAt('07:00')
    ->withoutOverlapping()
    ->onOneServer();

// Beendet abgelaufene Abholfristen und legt Exemplare für die nächste Vormerkung zurück.
Schedule::call($command('circulation:reservations:expire'))
    ->name('circulation:reservations:expire')
    ->dailyAt('04:00')
    ->withoutOverlapping()
    ->onOneServer();

// Tägliche Datensicherung nach storage/app/backups. Scheitert sie, geht eine Betriebsmeldung an die Administration.
Schedule::call(static function (): void {
    try {
        Artisan::call('backup:database');
    } catch (Throwable $exception) {
        report($exception);
        app(AlertService::class)->notify('backup-failed', 'Die Datensicherung ist fehlgeschlagen', [
            'Die tägliche Sicherung konnte nicht geschrieben werden: '.$exception->getMessage(),
            'Zeit: '.now()->toDateTimeString(),
        ]);
    }
})
    ->name('backup:database')
    ->dailyAt('01:30')
    ->withoutOverlapping()
    ->onOneServer();

// Anonymisiert abgelaufene Daten nach der Aufbewahrungsfrist (config/privacy.php).
Schedule::call($command('privacy:anonymize'))
    ->name('privacy:anonymize')
    ->weeklyOn(7, '02:00')
    ->withoutOverlapping()
    ->onOneServer();

// Räumt die Fehlerliste der Betriebsüberwachung nach 30 Tagen auf.
Schedule::call(static fn () => app(SystemErrorLog::class)->prune(30))
    ->name('system:prune-errors')
    ->dailyAt('02:30')
    ->withoutOverlapping()
    ->onOneServer();
