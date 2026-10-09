<?php

declare(strict_types=1);

namespace App\Foundation\Support;

use App\Foundation\Update\UpdateManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Throwable;

/**
 * Zustand der Installation für die Seite „Systemzustand“: Version, PHP, Umgebung, Datenbank, offene Migrationen, Schreibrechte und
 * Update-Pakete. Jede Zeile hat einen Zustand (ok, warn, fail), damit man nach einem Upload sofort sieht, ob alles stimmt.
 */
final class InstallationInfo
{
    public function __construct(private readonly UpdateManager $updates) {}

    /** @return list<array{label: string, value: string, state: string, hint: ?string}> */
    public function rows(): array
    {
        $rows = [];
        $add = static function (string $label, string $value, string $state = 'ok', ?string $hint = null) use (&$rows): void {
            $rows[] = ['label' => $label, 'value' => $value, 'state' => $state, 'hint' => $hint];
        };

        $version = $this->updates->currentVersion();
        $add('Installierte Version', $version, $version === 'unbekannt' ? 'warn' : 'ok', $version === 'unbekannt' ? 'Im Paket steht keine VERSION-Datei (Entwicklungsstand oder Paket ohne build-release.ps1).' : null);

        $phpOk = version_compare(PHP_VERSION, '8.4.0', '>=');
        $add('PHP', PHP_VERSION, $phpOk ? 'ok' : 'fail', $phpOk ? null : 'Die Anwendung braucht PHP 8.4 oder neuer.');

        $production = app()->environment('production');
        $debug = (bool) config('app.debug');
        $add('Umgebung', (string) config('app.env').($debug ? ', Debug an' : ', Debug aus'), $production && $debug ? 'fail' : 'ok', $production && $debug ? 'Im Echtbetrieb muss APP_DEBUG=false sein, sonst zeigen Fehlerseiten interne Angaben.' : null);

        $add('Adresse', (string) config('app.url'), $production && ! str_starts_with((string) config('app.url'), 'https://') ? 'warn' : 'ok', $production && ! str_starts_with((string) config('app.url'), 'https://') ? 'APP_URL sollte mit https:// beginnen.' : null);

        try {
            DB::connection()->getPdo();
            $add('Datenbank', DB::connection()->getDriverName().' · '.DB::connection()->getDatabaseName(), 'ok');
        } catch (Throwable $exception) {
            $add('Datenbank', 'nicht erreichbar', 'fail', $exception->getMessage());
        }

        [$pending, $migrationsKnown] = $this->pendingMigrations();
        $add('Datenbank-Aktualisierung', $migrationsKnown ? ($pending === 0 ? 'auf dem neuesten Stand' : $pending.' Migration(en) offen') : 'nicht prüfbar', $migrationsKnown && $pending === 0 ? 'ok' : 'warn', $pending > 0 ? 'Nach einem Update läuft der Abschluss das automatisch; sonst auf /_setup „Migrationen ausführen“.' : null);

        foreach (['storage' => storage_path(), 'storage/logs' => storage_path('logs'), 'bootstrap/cache' => base_path('bootstrap/cache'), 'public/covers' => public_path('covers')] as $label => $path) {
            if ($label === 'public/covers' && ! is_dir($path)) {
                $add('Schreibrechte '.$label, 'Ordner fehlt noch (wird beim ersten Cover angelegt)', 'ok');

                continue;
            }

            $writable = is_dir($path) && is_writable($path);
            $add('Schreibrechte '.$label, $writable ? 'beschreibbar' : 'nicht beschreibbar', $writable ? 'ok' : 'fail', $writable ? null : 'Der Ordner muss für PHP beschreibbar sein (FTP: Rechte 775).');
        }

        $add('Mailversand', (string) config('mail.default'), in_array((string) config('mail.default'), ['log', 'array'], true) && $production ? 'warn' : 'ok', in_array((string) config('mail.default'), ['log', 'array'], true) && $production ? 'Mails werden nicht zugestellt, sondern nur ins Log geschrieben.' : null);
        $add('Warteschlange', (string) config('queue.default'), 'ok');

        $packages = count(File::glob($this->updates->directory().'/*.zip') ?: []);
        $add('Update-Pakete', $packages === 0 ? 'keine bereit' : $packages.' bereit', 'ok');
        $add('Wartungsmodus', app()->isDownForMaintenance() ? 'AN: Besucher sehen die Wartungsseite' : 'aus', app()->isDownForMaintenance() ? 'warn' : 'ok');

        return $rows;
    }

    /** @return array{0: int, 1: bool} offene Migrationen und ob sich das prüfen ließ */
    private function pendingMigrations(): array
    {
        try {
            $migrator = app('migrator');

            if (! $migrator->repositoryExists()) {
                return [0, false];
            }

            $files = $migrator->getMigrationFiles(array_merge($migrator->paths(), [database_path('migrations')]));
            $ran = $migrator->getRepository()->getRan();

            return [count(array_diff(array_keys($files), $ran)), true];
        } catch (Throwable) {
            return [0, false];
        }
    }
}
