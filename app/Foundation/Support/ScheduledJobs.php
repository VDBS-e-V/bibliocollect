<?php

declare(strict_types=1);

namespace App\Foundation\Support;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Contracts\Container\Container;
use Throwable;

/**
 * Die Zeitplan-Aufgaben (routes/console.php) für die Seite „Systemzustand“: auflisten und einzeln einmal ausführen,
 * zum Beispiel wenn der Cron nicht läuft oder man eine Aufgabe sofort braucht.
 */
final class ScheduledJobs
{
    /** Kurze Erklärungen für die Anzeige; Aufgaben ohne Eintrag erscheinen mit ihrem Namen. */
    private const DESCRIPTIONS = [
        'catalog:covers:queue' => 'Cover für Bestandstitel nachladen (stellt Jobs in die Warteschlange).',
        'reminders:send' => 'Erinnerungen an bald fällige und überfällige Ausleihen und abholbereite Vormerkungen verschicken.',
        'circulation:reservations:expire' => 'Abgelaufene Abholfristen beenden und Exemplare für die nächste Vormerkung zurücklegen.',
        'backup:database' => 'Datenbank sichern (storage/app/backups).',
        'privacy:anonymize' => 'Daten nach der Aufbewahrungsfrist anonymisieren. Das lässt sich nicht rückgängig machen.',
        'system:prune-errors' => 'Fehlerliste der Betriebsüberwachung nach 30 Tagen aufräumen.',
    ];

    public function __construct(private readonly Schedule $schedule, private readonly Container $container, private readonly Kernel $kernel) {}

    /**
     * Die Aufgaben stehen in routes/console.php, und die Datei wird nur beim Start der Konsole geladen. Für eine Browser-Anfrage
     * holen wir das hier nach (läuft nur einmal und ändert nichts an der bereits gestarteten Anwendung).
     *
     * @return list<Event>
     */
    private function events(): array
    {
        $this->kernel->bootstrap();

        return $this->schedule->events();
    }

    /** @return list<array{name: string, description: string, expression: string, next_run: ?string}> */
    public function all(): array
    {
        $jobs = [];

        foreach ($this->events() as $event) {
            $name = $this->nameOf($event);

            if ($name === null) {
                continue;
            }

            $jobs[] = [
                'name' => $name,
                'description' => self::DESCRIPTIONS[$name] ?? $name,
                'expression' => $event->expression,
                'next_run' => $event->nextRunDate()->timezone((string) config('app.timezone'))->format('d.m.Y H:i'),
            ];
        }

        return $jobs;
    }

    public function has(string $name): bool
    {
        return $this->find($name) instanceof Event;
    }

    /**
     * Führt eine Aufgabe jetzt einmal aus, unabhängig von der Uhrzeit. Läuft sie gerade schon, wird sie nicht doppelt gestartet.
     *
     * @return array{ok: bool, message: string, seconds: float}
     */
    public function run(string $name): array
    {
        $event = $this->find($name);

        if (! $event instanceof Event) {
            return ['ok' => false, 'message' => 'Diese Aufgabe gibt es nicht.', 'seconds' => 0.0];
        }

        $started = microtime(true);

        try {
            $event->run($this->container);
        } catch (Throwable $exception) {
            report($exception);

            return ['ok' => false, 'message' => 'Die Aufgabe ist mit einem Fehler beendet worden: '.$exception->getMessage(), 'seconds' => round(microtime(true) - $started, 1)];
        }

        return ['ok' => true, 'message' => 'Die Aufgabe ist durchgelaufen.', 'seconds' => round(microtime(true) - $started, 1)];
    }

    private function find(string $name): ?Event
    {
        foreach ($this->events() as $event) {
            if ($this->nameOf($event) === $name) {
                return $event;
            }
        }

        return null;
    }

    private function nameOf(Event $event): ?string
    {
        return is_string($event->description) && $event->description !== '' ? $event->description : null;
    }
}
