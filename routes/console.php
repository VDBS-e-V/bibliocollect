<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Holt Cover für Bestandstitel nach (benötigt einen laufenden Queue Worker). Bereits ergebnislos geprüfte Ausgaben werden übersprungen.
Schedule::command('catalog:covers:queue --limit=50')
    ->dailyAt('03:30')
    ->withoutOverlapping()
    ->onOneServer();

// Erinnerungen an bald fällige und überfällige Ausleihen sowie abholbereite Vormerkungen.
Schedule::command('reminders:send')
    ->dailyAt('07:00')
    ->withoutOverlapping()
    ->onOneServer();

// Beendet abgelaufene Abholfristen und legt Exemplare für die nächste Vormerkung zurück.
Schedule::command('circulation:reservations:expire')
    ->dailyAt('04:00')
    ->withoutOverlapping()
    ->onOneServer();

// Anonymisiert abgelaufene Daten nach der Aufbewahrungsfrist (config/privacy.php).
Schedule::command('privacy:anonymize')
    ->weeklyOn(7, '02:00')
    ->withoutOverlapping()
    ->onOneServer();
