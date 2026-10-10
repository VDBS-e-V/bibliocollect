<?php

declare(strict_types=1);

namespace App\Foundation\Support;

use Illuminate\Support\Facades\Artisan;
use Throwable;

/**
 * Aufgaben, die nicht im Zeitplan stehen, aber auf Webspace ohne Konsole trotzdem auslösbar sein müssen (Seite „Cron und Aufgaben“).
 * Jede Aufgabe ist ein vorhandener Artisan-Befehl mit festen Parametern; es gibt keine freie Eingabe von Befehlen.
 */
final class ManualTasks
{
    /**
     * @return array<string, array{label: string, description: string, command: string, parameters: array<string, mixed>, confirm: ?string}>
     */
    public function all(): array
    {
        return [
            'search-benchmark' => [
                'label' => 'Katalogsuche messen',
                'description' => 'Misst, wie schnell die Suche auf diesem Server ist (Titel, Autor:in, Relevanz, „Meintest du“, Vorschläge). Dauert wenige Sekunden. Unter etwa 300 ms im Mittel ist alles in Ordnung.',
                'command' => 'catalog:search:benchmark',
                'parameters' => ['--runs' => 3],
                'confirm' => null,
            ],
            'series-sync' => [
                'label' => 'Reihen neu zuordnen',
                'description' => 'Ordnet alle Ausgaben anhand der Reihenangabe neu einer Reihe und Bandnummer zu. Sinnvoll nach einem Import oder vielen Änderungen.',
                'command' => 'catalog:series:sync',
                'parameters' => [],
                'confirm' => null,
            ],
            'quality-scan' => [
                'label' => 'Katalogqualität prüfen',
                'description' => 'Sucht im ganzen Katalog nach fehlenden oder auffälligen Angaben und legt Prüffälle an (ändert keine Katalogdaten).',
                'command' => 'catalog:quality:scan',
                'parameters' => [],
                'confirm' => null,
            ],
            'privacy-preview' => [
                'label' => 'Anonymisierung: Vorschau',
                'description' => 'Zählt, welche Daten der nächste Anonymisierungslauf anfassen würde. Ändert nichts.',
                'command' => 'privacy:anonymize',
                'parameters' => ['--dry-run' => true],
                'confirm' => null,
            ],
            'doctor' => [
                'label' => 'Einrichtung prüfen',
                'description' => 'Prüft PHP, Erweiterungen, Schlüssel, Datenbank, Rechte, Warteschlange, Zeitplan, Mail und Cover und zeigt jeden Befund.',
                'command' => 'app:doctor',
                'parameters' => [],
                'confirm' => null,
            ],
        ];
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->all());
    }

    /** @return array{ok: bool, output: string, seconds: float} */
    public function run(string $key): array
    {
        $task = $this->all()[$key] ?? null;

        if ($task === null) {
            return ['ok' => false, 'output' => 'Diese Aufgabe gibt es nicht.', 'seconds' => 0.0];
        }

        $started = microtime(true);

        try {
            $exitCode = Artisan::call($task['command'], $task['parameters']);
            $output = trim(Artisan::output());
        } catch (Throwable $exception) {
            report($exception);

            return ['ok' => false, 'output' => 'Die Aufgabe ist mit einem Fehler beendet worden: '.$exception->getMessage(), 'seconds' => round(microtime(true) - $started, 1)];
        }

        return ['ok' => $exitCode === 0, 'output' => $output !== '' ? $output : 'Fertig.', 'seconds' => round(microtime(true) - $started, 1)];
    }
}
